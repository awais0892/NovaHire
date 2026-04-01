<?php

namespace App\Jobs;

use App\Mail\CandidateDecisionMail;
use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDecisionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $emailLogId)
    {
    }

    public function backoff(): array
    {
        $configured = (array) config('recruitment.phase3.retry_backoff_seconds', [60, 180, 540]);
        $clean = collect($configured)
            ->map(fn($seconds) => max(1, (int) $seconds))
            ->filter()
            ->values()
            ->all();

        return $clean === [] ? [60, 180, 540] : $clean;
    }

    public function handle(): void
    {
        $emailLog = EmailLog::query()
            ->with(['application.candidate', 'application.jobListing.company'])
            ->find($this->emailLogId);

        if (!$emailLog || $emailLog->status === 'sent') {
            return;
        }

        $application = $emailLog->application;
        if (!$application) {
            $emailLog->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => 'Application record was not found for this email log.',
            ]);
            return;
        }

        $meta = is_array($emailLog->meta) ? $emailLog->meta : [];
        $decision = trim((string) ($meta['decision'] ?? $application->status));
        if (!in_array($decision, ['rejected', 'shortlisted', 'interview'], true)) {
            $decision = in_array($application->status, ['rejected', 'shortlisted', 'interview'], true)
                ? $application->status
                : 'shortlisted';
        }

        $note = trim((string) ($meta['note_content'] ?? $application->recruiter_notes ?? ''));

        try {
            Mail::to($emailLog->recipient_email)->send(new CandidateDecisionMail($application, $decision, $note));

            $emailLog->update([
                'status' => 'sent',
                'provider' => (string) config('mail.default'),
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $exception) {
            $attemptErrors = collect($meta['attempt_errors'] ?? [])
                ->push([
                    'attempt' => $this->attempts(),
                    'error' => $exception->getMessage(),
                    'at' => now()->toIso8601String(),
                ])
                ->take(-10)
                ->values()
                ->all();

            $emailLog->update([
                'status' => 'queued',
                'error_message' => $exception->getMessage(),
                'meta' => array_merge($meta, [
                    'attempt_errors' => $attemptErrors,
                ]),
            ]);

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        EmailLog::query()
            ->where('id', $this->emailLogId)
            ->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
    }
}

