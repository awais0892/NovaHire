<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Interview $interview,
        public string $window
    ) {
    }

    public function via($notifiable): array
    {
        if (method_exists($notifiable, 'notificationChannelsFor')) {
            return $notifiable->notificationChannelsFor('interview_reminder', ['mail', 'database', 'broadcast']);
        }

        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        $jobTitle = $this->interview->application?->jobListing?->title ?? 'your interview';
        $start = $this->interview->starts_at?->timezone($this->interview->timezone);
        $windowLabel = $this->window === '1h' ? 'in 1 hour' : 'in 24 hours';

        $mail = (new MailMessage)
            ->subject("Interview Reminder: {$jobTitle}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Reminder: your interview for {$jobTitle} starts {$windowLabel}.")
            ->line('Scheduled: ' . ($start?->format('d M Y H:i') ?? '-') . " ({$this->interview->timezone})");

        if ($this->interview->meeting_link) {
            $mail->action('Join Meeting', $this->interview->meeting_link);
        } else {
            $mail->action('View Invitation', route('candidate.interviews.invitation.show', $this->interview));
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'interview_reminder',
            'interview_id' => $this->interview->id,
            'application_id' => $this->interview->application_id,
            'job_title' => $this->interview->application?->jobListing?->title,
            'starts_at' => optional($this->interview->starts_at)->toIso8601String(),
            'timezone' => $this->interview->timezone,
            'window' => $this->window,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
