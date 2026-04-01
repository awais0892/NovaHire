<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationNote;
use Illuminate\Support\Facades\DB;

class ScoreBasedProcessingEngine
{
    public function __construct(
        private readonly OpenAiRecruiterNoteService $noteService
    ) {
    }

    public function process(Application $application, ?int $score = null): array
    {
        $application->loadMissing(['candidate', 'jobListing']);

        $resolvedScore = $this->resolveScore($application, $score);
        $targetStatus = $this->resolveDecisionStatus($resolvedScore);
        $locked = $this->isFinalLockedStatus($application->status);
        $notePayload = null;

        if (!$locked) {
            $notePayload = $this->buildNotePayload(
                candidateName: (string) ($application->candidate?->name ?? 'Candidate'),
                role: (string) ($application->jobListing?->title ?? 'the role'),
                score: $resolvedScore,
                status: $targetStatus
            );
        }

        $statusApplied = !$locked;
        $statusBefore = (string) $application->status;

        DB::transaction(function () use (
            $application,
            $resolvedScore,
            $targetStatus,
            $statusApplied,
            $notePayload
        ): void {
            $application->ai_score = $resolvedScore;

            if ($statusApplied) {
                $application->status = $targetStatus;
                $application->status_changed_at = now();
            }

            if ($statusApplied && is_array($notePayload)) {
                // Keep the latest generated note mirrored for legacy UI surfaces.
                $application->recruiter_notes = (string) $notePayload['content'];
            }
            $application->save();

            if ($statusApplied && is_array($notePayload)) {
                ApplicationNote::query()->create([
                    'company_id' => $application->company_id,
                    'application_id' => $application->id,
                    'candidate_id' => $application->candidate_id,
                    'author_user_id' => null,
                    'note_type' => $this->resolveNoteType($targetStatus),
                    'source' => 'ai',
                    'subject' => $this->buildSubject($application, $targetStatus),
                    'content' => (string) $notePayload['content'],
                    'meta' => [
                        'phase' => 'phase2',
                        'score' => $resolvedScore,
                        'decision' => $targetStatus,
                        'openai_model' => $notePayload['model'] ?? null,
                        'usage' => $notePayload['usage'] ?? [],
                        'fallback_used' => (bool) ($notePayload['fallback_used'] ?? false),
                    ],
                ]);
            }
        });

        return [
            'application_id' => $application->id,
            'score' => $resolvedScore,
            'status_before' => $statusBefore,
            'status_after' => $statusApplied ? $targetStatus : $statusBefore,
            'status_applied' => $statusApplied,
            'note_created' => $statusApplied && is_array($notePayload),
            'note_fallback_used' => (bool) ($notePayload['fallback_used'] ?? false),
        ];
    }

    public function resolveDecisionStatus(int $score): string
    {
        $rejectMax = (int) config('recruitment.phase2.reject_max_score', 50);
        $shortlistMax = (int) config('recruitment.phase2.shortlist_max_score', 70);

        if ($score <= $rejectMax) {
            return 'rejected';
        }

        if ($score <= $shortlistMax) {
            return 'shortlisted';
        }

        return 'interview';
    }

    private function buildNotePayload(
        string $candidateName,
        string $role,
        int $score,
        string $status
    ): array {
        $context = [
            'candidate_name' => $candidateName,
            'role' => $role,
            'score' => $score,
            'decision' => $status,
            'tone' => 'professional, concise, and action-oriented',
        ];

        try {
            $generated = $this->noteService->generateRecruiterNote($context);
            $content = trim((string) ($generated['content'] ?? ''));
            if ($content === '') {
                throw new \RuntimeException('OpenAI note content was empty.');
            }

            return [
                'content' => $content,
                'model' => $generated['model'] ?? null,
                'usage' => $generated['usage'] ?? [],
                'fallback_used' => false,
            ];
        } catch (\Throwable $exception) {
            logger()->warning('Phase 2 note generation failed, using deterministic fallback note.', [
                'error' => $exception->getMessage(),
                'score' => $score,
                'status' => $status,
            ]);

            return [
                'content' => $this->buildFallbackNote($candidateName, $role, $score, $status),
                'model' => null,
                'usage' => [],
                'fallback_used' => true,
            ];
        }
    }

    private function buildFallbackNote(string $candidateName, string $role, int $score, string $status): string
    {
        $statusSummary = match ($status) {
            'rejected' => 'The profile is not moving forward for this role at this stage.',
            'shortlisted' => 'The profile meets baseline criteria and is moving to shortlist review.',
            default => 'The profile is strong and is moving to interview planning.',
        };

        $nextStep = match ($status) {
            'rejected' => 'Encourage a future application after strengthening missing role requirements.',
            'shortlisted' => 'HR should review details and confirm progression actions.',
            default => 'Coordinate an interview schedule with the hiring team.',
        };

        return trim("Candidate {$candidateName} for {$role} scored {$score}/100. {$statusSummary}\n\nNext step: {$nextStep}");
    }

    private function resolveScore(Application $application, ?int $score): int
    {
        $candidateScore = $score ?? $application->ai_score ?? $application->aiAnalysis?->match_score ?? 0;

        return max(0, min(100, (int) $candidateScore));
    }

    private function resolveNoteType(string $status): string
    {
        return match ($status) {
            'rejected' => 'rejection',
            'shortlisted' => 'shortlist',
            default => 'interview',
        };
    }

    private function buildSubject(Application $application, string $status): string
    {
        $title = (string) ($application->jobListing?->title ?? 'Application');

        return match ($status) {
            'rejected' => "Application review update for {$title}",
            'shortlisted' => "Shortlist update for {$title}",
            default => "Interview progression update for {$title}",
        };
    }

    private function isFinalLockedStatus(string $status): bool
    {
        return in_array(
            $status,
            (array) config('recruitment.phase2.final_statuses', ['offer', 'hired']),
            true
        );
    }
}
