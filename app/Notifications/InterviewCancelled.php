<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Interview $interview)
    {
    }

    public function via($notifiable): array
    {
        if (method_exists($notifiable, 'notificationChannelsFor')) {
            return $notifiable->notificationChannelsFor('interview_cancelled', ['mail', 'database', 'broadcast']);
        }

        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        $application = $this->interview->application;
        $jobTitle = $application?->jobListing?->title ?? 'your application';
        $start = $this->interview->starts_at?->timezone($this->interview->timezone);

        $mail = (new MailMessage)
            ->subject("Interview Update: {$jobTitle}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Your scheduled interview for {$jobTitle} has been cancelled.")
            ->line('Originally scheduled: ' . ($start?->format('d M Y H:i') ?? '-') . " ({$this->interview->timezone})");

        if ($this->interview->cancelled_reason) {
            $mail->line("Reason: {$this->interview->cancelled_reason}");
        }

        return $mail
            ->action('View Applications', $this->destinationUrlFor($notifiable))
            ->line('The recruiter may send a new schedule shortly.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'interview_cancelled',
            'interview_id' => $this->interview->id,
            'application_id' => $this->interview->application_id,
            'job_title' => $this->interview->application?->jobListing?->title,
            'starts_at' => optional($this->interview->starts_at)->toIso8601String(),
            'timezone' => $this->interview->timezone,
            'reason' => $this->interview->cancelled_reason,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    private function destinationUrlFor($notifiable): string
    {
        if (method_exists($notifiable, 'hasRole')) {
            if ($notifiable->hasRole('candidate')) {
                return route('candidate.applications');
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
