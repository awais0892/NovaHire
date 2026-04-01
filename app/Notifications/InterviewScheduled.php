<?php

namespace App\Notifications;

use App\Models\Interview;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Interview $interview)
    {
    }

    public function via($notifiable): array
    {
        if (method_exists($notifiable, 'notificationChannelsFor')) {
            return $notifiable->notificationChannelsFor('interview_scheduled', ['mail', 'database', 'broadcast']);
        }

        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        $application = $this->interview->application;
        $jobTitle = $application?->jobListing?->title ?? 'your application';
        $start = $this->interview->starts_at?->timezone($this->interview->timezone);
        $end = $this->interview->ends_at?->timezone($this->interview->timezone);

        $mail = (new MailMessage)
            ->subject("Interview Scheduled: {$jobTitle}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Your interview has been scheduled for {$jobTitle}.")
            ->line('Date: ' . ($start?->format('d M Y') ?? '-'))
            ->line('Time: ' . ($start?->format('H:i') ?? '-') . ($end ? ' - ' . $end->format('H:i') : '') . " ({$this->interview->timezone})")
            ->line('Format: ' . ucfirst($this->interview->mode));

        if ($this->interview->meeting_link) {
            $mail->action('Join Meeting', $this->interview->meeting_link);
        }

        if ($this->interview->location) {
            $mail->line("Location: {$this->interview->location}");
        }

        $mail->action('View Invitation', $this->destinationUrlFor($notifiable));

        if ($this->interview->notes) {
            $mail->line("Notes: {$this->interview->notes}");
        }

        return $mail->line('A calendar invite is attached.')
            ->attachData(
                $this->toIcs(),
                'interview-invite.ics',
                ['mime' => 'text/calendar']
            );
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'interview_scheduled',
            'interview_id' => $this->interview->id,
            'application_id' => $this->interview->application_id,
            'job_title' => $this->interview->application?->jobListing?->title,
            'starts_at' => optional($this->interview->starts_at)->toIso8601String(),
            'timezone' => $this->interview->timezone,
            'meeting_link' => $this->interview->meeting_link,
            'location' => $this->interview->location,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    private function toIcs(): string
    {
        $startsAt = $this->interview->starts_at ?? now();
        $start = $this->formatUtc($startsAt);
        $endAt = $this->interview->ends_at ?? $startsAt->copy()->addHour();
        $end = $this->formatUtc($endAt);
        $timestamp = $this->formatUtc(now());
        $jobTitle = $this->escape($this->interview->application?->jobListing?->title ?? 'Interview');
        $description = $this->escape($this->interview->notes ?: 'Interview invitation');
        $location = $this->escape($this->interview->location ?: ($this->interview->meeting_link ?: 'Online'));
        $uid = "interview-{$this->interview->id}@novahire.local";

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//NovaHire//Interview Scheduler//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$timestamp}",
            "DTSTART:{$start}",
            "DTEND:{$end}",
            "SUMMARY:Interview - {$jobTitle}",
            "DESCRIPTION:{$description}",
            "LOCATION:{$location}",
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    private function formatUtc(DateTimeInterface $dateTime): string
    {
        return $dateTime->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    private function escape(string $value): string
    {
        return str_replace(
            ["\\", ";", ",", "\n", "\r"],
            ["\\\\", "\;", "\,", '\n', ''],
            $value
        );
    }

    private function destinationUrlFor($notifiable): string
    {
        if (method_exists($notifiable, 'hasRole')) {
            if ($notifiable->hasRole('candidate')) {
                return route('candidate.interviews.invitation.show', $this->interview);
            }
            if ($notifiable->hasRole('hr_admin')) {
                return route('recruiter.applications');
            }
            if ($notifiable->hasRole('hiring_manager')) {
                return route('manager.dashboard');
            }
            if ($notifiable->hasRole('super_admin')) {
                return route('admin.dashboard');
            }
        }

        return route('home');
    }
}
