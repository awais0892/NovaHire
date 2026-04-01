<?php

namespace App\Notifications;

use App\Models\Application;
use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    private bool $noteResolved = false;
    private ?string $resolvedNote = null;

    public function __construct(
        public Application $application,
        private array $channelsOverride = []
    ) {
    }

    public function via($notifiable): array
    {
        if (!empty($this->channelsOverride)) {
            return $this->channelsOverride;
        }

        if (method_exists($notifiable, 'notificationChannelsFor')) {
            return $notifiable->notificationChannelsFor('application_status_changed', ['mail', 'database', 'broadcast']);
        }

        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        $status = ucfirst($this->application->status);
        $jobTitle = $this->application->jobListing->title;

        $mail = (new MailMessage)
            ->subject("Application Update: {$jobTitle}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Your application for **{$jobTitle}** has been updated.")
            ->line("New Status: **{$status}**");

        if ($this->application->status === 'interview') {
            $mail->line('Congratulations! You have been selected for an interview.');
        } elseif ($this->application->status === 'offer') {
            $mail->line('Excellent news! An offer is being prepared for you.');
        } elseif ($this->application->status === 'rejected') {
            $mail->line('Thank you for your interest. We will keep your profile on file.');
        }

        $note = $this->resolveNoteContent();
        if ($note !== '') {
            $mail->line('AI Recruiter Note:')
                ->line($note);
        }

        $mail->action('View Application', $this->destinationUrlFor($notifiable))
            ->line('Thank you for applying!');

        return $mail;
    }

    public function toArray($notifiable): array
    {
        $noteExcerpt = $this->resolveNoteExcerpt();

        return [
            'application_id' => $this->application->id,
            'job_title' => $this->application->jobListing->title,
            'status' => $this->application->status,
            'note_excerpt' => $noteExcerpt,
            'has_note' => $noteExcerpt !== '',
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
                if ($this->application->status === 'interview') {
                    $interview = Interview::query()
                        ->where('application_id', $this->application->id)
                        ->where('status', 'scheduled')
                        ->latest('starts_at')
                        ->first();

                    if ($interview) {
                        return route('candidate.interviews.invitation.show', $interview);
                    }
                }

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

    private function resolveNoteContent(): string
    {
        if ($this->noteResolved) {
            return $this->resolvedNote ?? '';
        }

        $this->noteResolved = true;

        $note = trim((string) ($this->application->recruiter_notes ?? ''));
        if ($note !== '') {
            return $this->resolvedNote = $note;
        }

        try {
            $latestNote = trim((string) $this->application->notes()->latest('id')->value('content'));
            return $this->resolvedNote = $latestNote;
        } catch (\Throwable) {
            return $this->resolvedNote = '';
        }
    }

    private function resolveNoteExcerpt(): string
    {
        $note = $this->resolveNoteContent();
        if ($note === '') {
            return '';
        }

        $flat = preg_replace('/\s+/', ' ', $note) ?? '';

        return Str::limit(trim($flat), 220);
    }
}
