<?php

namespace App\Mail;

use App\Models\Company;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HrDailyEmailDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public array $summary,
        public CarbonInterface $windowFrom,
        public CarbonInterface $windowTo
    ) {
    }

    public function envelope(): Envelope
    {
        $dateLabel = $this->windowTo->timezone(config('recruitment.uk_timezone', 'Europe/London'))->format('d M Y');

        return new Envelope(
            subject: "NovaHire Daily Email Digest - {$dateLabel}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hr.daily-digest',
            with: [
                'company' => $this->company,
                'summary' => $this->summary,
                'from' => $this->windowFrom,
                'to' => $this->windowTo,
            ]
        );
    }
}
