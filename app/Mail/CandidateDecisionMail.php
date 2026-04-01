<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public string $decision,
        public string $note
    ) {
    }

    public function envelope(): Envelope
    {
        $jobTitle = (string) ($this->application->jobListing?->title ?? 'Your Application');

        $subject = match ($this->decision) {
            'rejected' => "NovaHire Update: {$jobTitle}",
            'shortlisted' => "NovaHire Shortlist Update: {$jobTitle}",
            default => "NovaHire Interview Update: {$jobTitle}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $view = match ($this->decision) {
            'rejected' => 'emails.candidate.rejection',
            'shortlisted' => 'emails.candidate.shortlist',
            default => 'emails.candidate.interview',
        };

        return new Content(
            view: $view,
            with: [
                'application' => $this->application,
                'candidate' => $this->application->candidate,
                'job' => $this->application->jobListing,
                'company' => $this->application->jobListing?->company,
                'note' => $this->note,
                'decision' => $this->decision,
            ]
        );
    }
}

