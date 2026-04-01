<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Interview;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\InterviewScheduled;
use App\Services\AiCvAnalyserService;
use App\Services\CandidateDecisionEmailService;
use App\Services\InterviewSlotEngineService;
use App\Services\ScoreBasedProcessingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Smalot\PdfParser\Parser;

class ProcessCvAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    // Allow longer processing time for PDF parsing + AI requests on slower machines
    public int $timeout = 300;
    public bool $failOnTimeout = true;

    public function __construct(public Application $application)
    {
    }

    public static function dispatchSmart(Application $application): void
    {
        $queue = (string) config('queue.default', 'sync');

        if ($queue === 'sync' && !app()->runningInConsole()) {
            // Never block browser requests while parsing CV + running AI.
            static::dispatchAfterResponse($application);
            return;
        }

        if ($queue === 'sync') {
            static::dispatchSync($application);
            return;
        }

        static::dispatch($application);
    }

    public function handle(
        AiCvAnalyserService $aiService,
        ScoreBasedProcessingEngine $scoreEngine,
        CandidateDecisionEmailService $decisionEmailService,
        InterviewSlotEngineService $slotEngine
    ): void
    {
        // Extend the max execution time for this job execution context
        @set_time_limit(600);
        $candidate = $this->application->candidate;
        $candidate->update(['cv_status' => 'processing']);

        try {
            if (empty($candidate->cv_raw_text) && $candidate->cv_path) {
                $fullPath = storage_path('app/private/' . $candidate->cv_path);
                $parser = new Parser();
                $pdf = $parser->parseFile($fullPath);
                $cvText = $pdf->getText();

                $extracted = $aiService->extractCvData($cvText);

                $candidate->update([
                    'cv_raw_text' => $cvText,
                    'extracted_skills' => $extracted['skills'] ?? [],
                    'extracted_experience' => $extracted['experience'] ?? [],
                    'extracted_education' => $extracted['education'] ?? [],
                    'cv_status' => 'processed',
                ]);
            }

            $analysis = $aiService->analyse($this->application);
            $processed = $scoreEngine->process(
                $this->application->fresh(['candidate', 'candidate.user', 'jobListing', 'aiAnalysis']),
                (int) ($analysis?->match_score ?? 0)
            );
            $this->autoScheduleInterviewIfNeeded(
                $this->application->fresh(['candidate', 'candidate.user', 'jobListing']),
                $processed,
                $slotEngine
            );
            $this->dispatchCandidateStatusNotification(
                $this->application->fresh(['candidate', 'candidate.user', 'jobListing']),
                $processed
            );
            $this->dispatchDecisionEmail(
                $this->application->fresh(['candidate', 'jobListing', 'notes']),
                $processed,
                $decisionEmailService
            );

            // Always close the processing loop for UI state.
            $candidate->update(['cv_status' => 'processed']);
        } catch (\Throwable $e) {
            logger()->error('CV analysis job failed.', [
                'application_id' => $this->application->id,
                'error' => $e->getMessage(),
            ]);
            $candidate->update(['cv_status' => 'failed']);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->application->candidate->update(['cv_status' => 'failed']);
    }

    private function dispatchCandidateStatusNotification(Application $application, array $processed): void
    {
        if (!(bool) ($processed['status_applied'] ?? false)) {
            return;
        }

        $candidate = $application->candidate;
        $user = $candidate?->user;
        if (!$candidate || !$user) {
            return;
        }

        $preferredChannels = method_exists($user, 'notificationChannelsFor')
            ? $user->notificationChannelsFor('application_status_changed', ['database', 'broadcast'])
            : ['database', 'broadcast'];
        $channels = array_values(array_intersect($preferredChannels, ['database', 'broadcast']));
        if ($channels === []) {
            $channels = ['database'];
        }

        try {
            $user->notify(new ApplicationStatusChanged($application, $channels));
        } catch (\Throwable $exception) {
            logger()->warning('Phase 2 candidate status notification failed.', [
                'application_id' => $application->id,
                'candidate_user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function dispatchDecisionEmail(
        Application $application,
        array $processed,
        CandidateDecisionEmailService $decisionEmailService
    ): void
    {
        if (!(bool) ($processed['status_applied'] ?? false)) {
            return;
        }

        $status = (string) ($processed['status_after'] ?? '');
        if (!in_array($status, ['rejected', 'shortlisted', 'interview'], true)) {
            return;
        }

        try {
            $decisionEmailService->queueForDecision($application, $status);
        } catch (\Throwable $exception) {
            logger()->warning('Phase 3 candidate decision email dispatch failed to queue.', [
                'application_id' => $application->id,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function autoScheduleInterviewIfNeeded(
        Application $application,
        array $processed,
        InterviewSlotEngineService $slotEngine
    ): void {
        if (!(bool) ($processed['status_applied'] ?? false)) {
            return;
        }

        if ((string) ($processed['status_after'] ?? '') !== 'interview') {
            return;
        }

        $hasUpcomingInterview = Interview::query()
            ->where('application_id', $application->id)
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', now()->utc())
            ->exists();
        if ($hasUpcomingInterview) {
            return;
        }

        try {
            $interview = $slotEngine->bookNextAvailableSlotForApplication(
                application: $application,
                scheduledByUserId: null,
                overrides: [
                    'notes' => 'Auto-scheduled by NovaHire scoring engine.',
                ]
            );

            if ($interview && $application->candidate?->user) {
                $application->candidate->user->notify(
                    new InterviewScheduled($interview->loadMissing('application.jobListing'))
                );
            }
        } catch (\Throwable $exception) {
            logger()->warning('Phase 4 auto interview scheduling failed.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
