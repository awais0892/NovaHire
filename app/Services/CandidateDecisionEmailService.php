<?php

namespace App\Services;

use App\Jobs\SendDecisionEmailJob;
use App\Models\Application;
use App\Models\EmailLog;
use Illuminate\Support\Str;

class CandidateDecisionEmailService
{
    public function queueForDecision(
        Application $application,
        string $status,
        ?string $noteOverride = null,
        bool $force = false
    ): ?EmailLog
    {
        if (!in_array($status, ['rejected', 'shortlisted', 'interview'], true)) {
            return null;
        }

        $application->loadMissing(['candidate', 'jobListing.company']);

        $recipientEmail = trim((string) ($application->candidate?->email ?? ''));
        if ($recipientEmail === '') {
            return null;
        }

        $template = $this->templateFor($status);
        if (!$force && $this->recentlySent($application, $template)) {
            return null;
        }

        $note = trim((string) $noteOverride);
        if ($note === '') {
            $note = $this->resolveNoteContent($application);
        }

        $subject = $this->subjectFor($application, $status);

        $emailLog = EmailLog::query()->create([
            'company_id' => $application->company_id,
            'application_id' => $application->id,
            'candidate_id' => $application->candidate_id,
            'template' => $template,
            'channel' => 'email',
            'direction' => 'outbound',
            'recipient_email' => $recipientEmail,
            'subject' => $subject,
            'provider' => (string) config('mail.default'),
            'status' => 'queued',
            'meta' => [
                'phase' => 'phase3',
                'decision' => $status,
                'forced' => $force,
                'tracking_id' => (string) Str::uuid(),
                'note_content' => $note,
                'note_excerpt' => Str::limit(preg_replace('/\s+/', ' ', $note) ?? '', 240),
            ],
        ]);

        SendDecisionEmailJob::dispatch($emailLog->id);

        return $emailLog;
    }

    private function templateFor(string $status): string
    {
        return match ($status) {
            'rejected' => 'candidate_rejection',
            'shortlisted' => 'candidate_shortlist',
            default => 'candidate_interview',
        };
    }

    private function subjectFor(Application $application, string $status): string
    {
        $title = (string) ($application->jobListing?->title ?? 'Your Application');

        return match ($status) {
            'rejected' => "NovaHire Update: {$title}",
            'shortlisted' => "NovaHire Shortlist Update: {$title}",
            default => "NovaHire Interview Update: {$title}",
        };
    }

    private function resolveNoteContent(Application $application): string
    {
        $note = trim((string) ($application->recruiter_notes ?? ''));
        if ($note !== '') {
            return $note;
        }

        return trim((string) $application->notes()->latest('id')->value('content'));
    }

    private function recentlySent(Application $application, string $template): bool
    {
        $minutes = max(1, (int) config('recruitment.phase3.dedupe_minutes', 30));

        return EmailLog::query()
            ->where('application_id', $application->id)
            ->where('template', $template)
            ->where('status', 'sent')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();
    }
}
