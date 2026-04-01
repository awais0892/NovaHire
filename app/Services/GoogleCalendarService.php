<?php

namespace App\Services;

use App\Models\Interview;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GOOGLE_CALENDAR_API_BASE = 'https://www.googleapis.com/calendar/v3';

    public function isConfigured(): bool
    {
        return $this->isEnabled()
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google_calendar.refresh_token'))
            && filled($this->calendarId());
    }

    public function upsertInterviewEvent(Interview $interview): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $interview->loadMissing(['application.candidate', 'application.jobListing', 'scheduler']);

        $endpoint = $this->eventEndpoint($interview->google_calendar_event_id);
        $method = filled($interview->google_calendar_event_id) ? 'patch' : 'post';
        $query = [
            'sendUpdates' => 'all',
        ];

        $shouldGenerateMeetLink = $this->shouldGenerateMeetLink($interview);
        if ($shouldGenerateMeetLink) {
            $query['conferenceDataVersion'] = 1;
        }

        $url = $endpoint . '?' . http_build_query($query);
        $response = $this->googleClient()
            ->{$method}($url, $this->buildEventPayload($interview, $shouldGenerateMeetLink, $interview->google_calendar_event_id));

        if (!$response->ok()) {
            throw new \RuntimeException(sprintf(
                'Google Calendar event sync failed with status %d: %s',
                $response->status(),
                (string) $response->body()
            ));
        }

        $payload = $response->json() ?: [];
        $hangoutLink = trim((string) ($payload['hangoutLink'] ?? ''));
        $htmlLink = trim((string) ($payload['htmlLink'] ?? ''));
        $eventId = trim((string) ($payload['id'] ?? ''));

        $updates = [
            'google_calendar_event_id' => $eventId !== '' ? $eventId : $interview->google_calendar_event_id,
            'google_calendar_event_link' => $htmlLink !== '' ? $htmlLink : $interview->google_calendar_event_link,
            'google_calendar_synced_at' => now(),
        ];

        if ($hangoutLink !== '' && blank($interview->meeting_link) && $interview->mode === 'video') {
            $updates['meeting_link'] = $hangoutLink;
        }

        $interview->update($updates);

        return [
            'event_id' => (string) ($updates['google_calendar_event_id'] ?? ''),
            'event_link' => (string) ($updates['google_calendar_event_link'] ?? ''),
            'meeting_link' => $hangoutLink,
        ];
    }

    public function cancelInterviewEvent(Interview $interview): void
    {
        if (!$this->isConfigured() || blank($interview->google_calendar_event_id)) {
            return;
        }

        $endpoint = $this->eventEndpoint((string) $interview->google_calendar_event_id);
        $response = $this->googleClient()->patch(
            $endpoint . '?' . http_build_query(['sendUpdates' => 'all']),
            ['status' => 'cancelled']
        );

        if (!$response->ok()) {
            throw new \RuntimeException(sprintf(
                'Google Calendar event cancellation failed with status %d: %s',
                $response->status(),
                (string) $response->body()
            ));
        }

        $interview->update([
            'google_calendar_synced_at' => now(),
        ]);
    }

    private function googleClient()
    {
        $token = $this->accessToken();

        return Http::baseUrl(self::GOOGLE_CALENDAR_API_BASE)
            ->acceptJson()
            ->timeout((int) config('services.google_calendar.timeout_seconds', 10))
            ->withToken($token);
    }

    private function accessToken(): string
    {
        $cacheKey = 'google_calendar_access_token:' . sha1((string) config('services.google_calendar.refresh_token'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('services.google_calendar.timeout_seconds', 10))
                ->post(self::GOOGLE_TOKEN_URL, [
                    'client_id' => (string) config('services.google.client_id'),
                    'client_secret' => (string) config('services.google.client_secret'),
                    'refresh_token' => (string) config('services.google_calendar.refresh_token'),
                    'grant_type' => 'refresh_token',
                ]);

            if (!$response->ok()) {
                throw new \RuntimeException(sprintf(
                    'Google OAuth token refresh failed with status %d: %s',
                    $response->status(),
                    (string) $response->body()
                ));
            }

            $accessToken = trim((string) data_get($response->json(), 'access_token', ''));
            if ($accessToken === '') {
                throw new \RuntimeException('Google OAuth token refresh response did not include access_token.');
            }

            return $accessToken;
        });
    }

    private function buildEventPayload(Interview $interview, bool $withConferenceData, ?string $eventId = null): array
    {
        $application = $interview->application;
        $job = $application?->jobListing;
        $candidate = $application?->candidate;
        $scheduler = $interview->scheduler;

        $summary = sprintf(
            'Interview: %s - %s',
            (string) ($candidate?->name ?? 'Candidate'),
            (string) ($job?->title ?? 'Role')
        );

        $descriptionParts = [
            'NovaHire interview schedule',
            'Candidate: ' . (string) ($candidate?->name ?? 'Unknown'),
            'Role: ' . (string) ($job?->title ?? 'Unknown'),
            'Mode: ' . ucfirst((string) $interview->mode),
        ];

        if (filled($interview->notes)) {
            $descriptionParts[] = 'Notes: ' . (string) $interview->notes;
        }

        $payload = [
            'summary' => Str::limit($summary, 250, ''),
            'description' => Str::limit(implode("\n", $descriptionParts), 4000, ''),
            'start' => [
                'dateTime' => $interview->starts_at?->copy()->timezone($interview->timezone)->toIso8601String(),
                'timeZone' => (string) $interview->timezone,
            ],
            'end' => [
                'dateTime' => $interview->ends_at?->copy()->timezone($interview->timezone)->toIso8601String(),
                'timeZone' => (string) $interview->timezone,
            ],
            'location' => $interview->location,
            'attendees' => collect([
                $candidate?->email,
                $scheduler?->email,
            ])->filter(fn(?string $email) => filled($email))
                ->unique()
                ->map(fn(string $email) => ['email' => $email])
                ->values()
                ->all(),
        ];

        if ($withConferenceData) {
            $payload['conferenceData'] = [
                'createRequest' => [
                    'requestId' => 'novahire-' . ($eventId ?: Str::uuid()->toString()),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ];
        }

        if (filled($interview->meeting_link)) {
            $payload['description'] .= "\nMeeting link: {$interview->meeting_link}";
        }

        return $payload;
    }

    private function shouldGenerateMeetLink(Interview $interview): bool
    {
        return $interview->mode === 'video' && blank($interview->meeting_link);
    }

    private function eventEndpoint(?string $eventId): string
    {
        $calendarId = rawurlencode($this->calendarId());
        if (filled($eventId)) {
            return "/calendars/{$calendarId}/events/" . rawurlencode((string) $eventId);
        }

        return "/calendars/{$calendarId}/events";
    }

    private function calendarId(): string
    {
        return (string) config('services.google_calendar.calendar_id', 'primary');
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.google_calendar.enabled', false);
    }
}
