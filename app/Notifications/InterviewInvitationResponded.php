<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewInvitationResponded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Interview $interview,
        public string $response
    ) {
    }

    public function via($notifiable): array
    {
        if (method_exists($notifiable, 'notificationChannelsFor')) {
            return $notifiable->notificationChannelsFor('interview_invitation_responded', ['database', 'mail', 'broadcast']);
        }

        return ['database', 'mail', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        $candidateName = $this->interview->application?->candidate?->name ?? 'Candidate';
        $jobTitle = $this->interview->application?->jobListing?->title ?? 'Job';
        $label = $this->response === 'accepted' ? 'accepted' : 'declined';

        return (new MailMessage)
            ->subject("Interview {$label}: {$candidateName}")
            ->line("{$candidateName} has {$label} the interview invitation.")
            ->line("Role: {$jobTitle}")
            ->action('View Applications', route('recruiter.applications'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'interview_invitation_responded',
            'interview_id' => $this->interview->id,
            'application_id' => $this->interview->application_id,
            'candidate_name' => $this->interview->application?->candidate?->name,
            'job_title' => $this->interview->application?->jobListing?->title,
            'response' => $this->response,
            'responded_at' => optional($this->interview->candidate_responded_at)->toIso8601String(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
