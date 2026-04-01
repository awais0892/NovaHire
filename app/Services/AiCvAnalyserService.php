<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AiAnalysis;
use OpenAI\Laravel\Facades\OpenAI;

class AiCvAnalyserService
{
    public function analyse(Application $application): AiAnalysis
    {
        // Keep bounded in local/dev to avoid queue worker hard timeouts.
        @set_time_limit((int) env('AI_ANALYSIS_TIME_LIMIT', 45));

        $candidate = $application->candidate;
        $job = $application->jobListing()->with('skills')->first();
        $cvText = $this->prepareText((string) $candidate->cv_raw_text, (int) env('AI_MAX_CV_CHARS', 3500));
        $jobDescription = $this->prepareText((string) ($job->description ?? ''), (int) env('AI_MAX_JOB_DESC_CHARS', 1800));
        $jobSkillsList = $job->skills->pluck('skill')->filter()->values();
        $jobSkills = $jobSkillsList->implode(', ');

        if ($this->shouldForceFallback() || !$this->hasOpenAiKey()) {
            return $this->buildFallbackAnalysis($application, $jobSkillsList->all(), $cvText);
        }

        $prompt = $this->buildPrompt($cvText, $job, $jobSkills, $jobDescription);

        $model = (string) env('AI_ANALYSIS_MODEL', 'gpt-4o-mini');
        $maxTokens = max(600, (int) env('AI_ANALYSIS_MAX_TOKENS', 900));

        try {
            $response = OpenAI::chat()->create([
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert technical recruiter and talent acquisition specialist. Always return a valid JSON object only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
                'max_tokens' => $maxTokens,
            ]);

            $raw = (string) ($response->choices[0]->message->content ?? '');
            $tokens = $response->usage->totalTokens ?? 0;
            $data = $this->parseJsonPayload($raw);

            // Second pass: ask model to repair invalid JSON instead of immediately falling back.
            if (!$this->looksLikeAnalysisPayload($data) && $raw !== '') {
                $repair = OpenAI::chat()->create([
                    'model' => $model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Return valid JSON object only. Do not add markdown or comments.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Repair and normalize this into valid JSON object with keys: match_score, matched_skills, missing_skills, strengths, weaknesses, reasoning, recommendation, interview_questions.\n\nText:\n{$raw}",
                        ],
                    ],
                    'temperature' => 0.0,
                    'max_tokens' => max(400, (int) floor($maxTokens * 0.8)),
                ]);

                $repairRaw = (string) ($repair->choices[0]->message->content ?? '');
                $tokens += ($repair->usage->totalTokens ?? 0);
                $data = $this->parseJsonPayload($repairRaw);
            }

            if (!$this->looksLikeAnalysisPayload($data)) {
                throw new \RuntimeException('OpenAI analyse response was not valid JSON payload.');
            }

            return AiAnalysis::updateOrCreate(
                ['application_id' => $application->id],
                [
                    'candidate_id' => $candidate->id,
                    'job_listing_id' => $job->id,
                    'match_score' => $data['match_score'] ?? 0,
                    'matched_skills' => $data['matched_skills'] ?? [],
                    'missing_skills' => $data['missing_skills'] ?? [],
                    'reasoning' => $data['reasoning'] ?? '',
                    'strengths' => $data['strengths'] ?? '',
                    'weaknesses' => $data['weaknesses'] ?? '',
                    'interview_questions' => $data['interview_questions'] ?? [],
                    'recommendation' => $data['recommendation'] ?? 'maybe',
                    'tokens_used' => $tokens,
                ]
            );
        } catch (\Throwable $e) {
            logger()->warning('OpenAI analyse failed, using fallback scoring.', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return $this->buildFallbackAnalysis($application, $jobSkillsList->all(), $cvText);
        }
    }

    public function extractCvData(string $cvText): array
    {
        @set_time_limit((int) env('AI_EXTRACT_TIME_LIMIT', 30));
        $cvText = $this->prepareText($cvText, (int) env('AI_MAX_CV_CHARS', 3500));

        // Extraction via GPT is optional; by default use deterministic local extractor to save tokens.
        if (
            !filter_var((string) env('AI_USE_OPENAI_EXTRACT', false), FILTER_VALIDATE_BOOL)
            || $this->shouldForceFallback()
            || !$this->hasOpenAiKey()
        ) {
            return $this->extractCvDataFallback($cvText);
        }

        try {
            $response = OpenAI::chat()->create([
                'model' => (string) env('AI_EXTRACT_MODEL', env('AI_ANALYSIS_MODEL', 'gpt-4o-mini')),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Extract structured data from this CV. Return valid JSON object only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract the following from this CV and return as JSON:\n{\n    \"name\": \"\",\n    \"email\": \"\",\n    \"phone\": \"\",\n    \"location\": \"\",\n    \"linkedin\": \"\",\n    \"github\": \"\",\n    \"skills\": [],\n    \"experience\": [{\"title\": \"\", \"company\": \"\", \"duration\": \"\", \"description\": \"\"}],\n    \"education\": [{\"degree\": \"\", \"institution\": \"\", \"year\": \"\"}]\n}\n\nCV Text:\n{$cvText}",
                    ],
                ],
                'temperature' => 0.1,
                'max_tokens' => max(250, (int) env('AI_EXTRACT_MAX_TOKENS', 450)),
            ]);

            $raw = $response->choices[0]->message->content;
            $data = $this->parseJsonPayload((string) $raw);
            if (empty($data)) {
                return $this->extractCvDataFallback($cvText);
            }
            return $data;
        } catch (\Throwable $e) {
            logger()->warning('OpenAI extract failed, using fallback extraction.', [
                'error' => $e->getMessage(),
            ]);
            return $this->extractCvDataFallback($cvText);
        }
    }

    private function buildPrompt(string $cvText, $job, string $jobSkills, string $jobDescription): string
    {
        return "Analyse this candidate's CV against the job description and return a valid JSON object in this exact format:\n{
    \"match_score\": <integer 0-100>,
    \"matched_skills\": [\"skill1\", \"skill2\"],
    \"missing_skills\": [\"skill3\", \"skill4\"],
    \"strengths\": \"<2-3 sentence summary of candidate strengths>\",
    \"weaknesses\": \"<2-3 sentence summary of gaps or concerns>\",
    \"reasoning\": \"<3-4 sentence overall assessment explaining the score>\",
    \"recommendation\": \"<one of: strong_yes, yes, maybe, no>\",
    \"interview_questions\": [
        {\"type\": \"technical\",    \"question\": \"...\"},
        {\"type\": \"technical\",    \"question\": \"...\"},
        {\"type\": \"behavioural\",  \"question\": \"...\"},
        {\"type\": \"gap_probing\",  \"question\": \"...\"},
        {\"type\": \"gap_probing\",  \"question\": \"...\"}
    ]
}

JOB TITLE: {$job->title}
EXPERIENCE REQUIRED: {$job->experience_level}
JOB TYPE: {$job->job_type}
REQUIRED SKILLS: {$jobSkills}

JOB DESCRIPTION:
{$jobDescription}

CANDIDATE CV:
{$cvText}";
    }

    private function hasOpenAiKey(): bool
    {
        return filled(config('openai.api_key'));
    }

    private function shouldForceFallback(): bool
    {
        // Explicit override: always attempt GPT if this is enabled.
        if ($this->forceOpenAiEnabled()) {
            return false;
        }

        $queueIsSync = (string) config('queue.default', 'sync') === 'sync';
        $allowOpenAiOnSyncQueue = filter_var(
            (string) env('AI_ALLOW_OPENAI_WITH_SYNC_QUEUE', false),
            FILTER_VALIDATE_BOOL
        );

        if ($queueIsSync && !$allowOpenAiOnSyncQueue) {
            return true;
        }

        // Default local behavior: avoid unstable network-bound AI calls while developing.
        // Set AI_FORCE_OPENAI=true in .env to use full GPT analysis in local.
        if (!app()->environment('local')) {
            return false;
        }

        return true;
    }

    private function forceOpenAiEnabled(): bool
    {
        return filter_var((string) env('AI_FORCE_OPENAI', false), FILTER_VALIDATE_BOOL);
    }

    private function extractCvDataFallback(string $cvText): array
    {
        $email = '';
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $cvText, $matches)) {
            $email = $matches[0];
        }

        $phone = '';
        if (preg_match('/(\+?\d[\d\-\s()]{7,}\d)/', $cvText, $matches)) {
            $phone = trim($matches[0]);
        }

        return [
            'email' => $email,
            'phone' => $phone,
            'skills' => $this->extractSkillsFromText($cvText),
            'experience' => [],
            'education' => [],
        ];
    }

    private function buildFallbackAnalysis(Application $application, array $jobSkills, string $cvText): AiAnalysis
    {
        $candidate = $application->candidate;
        $job = $application->jobListing;

        $candidateSkills = collect($candidate->extracted_skills ?: $this->extractSkillsFromText($cvText))
            ->map(fn($s) => strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->values();

        $requiredSkills = collect($jobSkills)
            ->map(fn($s) => strtolower(trim((string) $s)))
            ->filter()
            ->unique()
            ->values();

        $matched = $requiredSkills->intersect($candidateSkills)->values();
        $missing = $requiredSkills->diff($matched)->values();

        $coverage = $requiredSkills->count() > 0
            ? (int) round(($matched->count() / $requiredSkills->count()) * 100)
            : 50;

        $score = max(35, min(95, 40 + (int) round($coverage * 0.55)));

        $recommendation = match (true) {
            $score >= 85 => 'strong_yes',
            $score >= 70 => 'yes',
            $score >= 55 => 'maybe',
            default => 'no',
        };

        $reasoning = 'Automated fallback scoring was used because AI service is unavailable. '
            . "Skill coverage is approximately {$coverage}% against role requirements.";

        return AiAnalysis::updateOrCreate(
            ['application_id' => $application->id],
            [
                'candidate_id' => $candidate->id,
                'job_listing_id' => $job->id,
                'match_score' => $score,
                'matched_skills' => $matched->all(),
                'missing_skills' => $missing->all(),
                'reasoning' => $reasoning,
                'strengths' => $matched->isNotEmpty()
                    ? 'Candidate profile includes relevant skills for this role.'
                    : 'Limited overlap found against listed role requirements.',
                'weaknesses' => $missing->isNotEmpty()
                    ? 'Some required skills are not clearly present in the CV text.'
                    : 'No critical missing skills detected from listed requirements.',
                'interview_questions' => [
                    ['type' => 'technical', 'question' => 'Walk through a recent project relevant to this role.'],
                    ['type' => 'behavioural', 'question' => 'Describe how you handle tight deadlines and changing priorities.'],
                    ['type' => 'gap_probing', 'question' => 'Which role requirements would you need support to ramp up on quickly?'],
                ],
                'recommendation' => $recommendation,
                'tokens_used' => 0,
            ]
        );
    }

    private function extractSkillsFromText(string $text): array
    {
        $dictionary = [
            'php', 'laravel', 'symfony', 'mysql', 'postgresql', 'redis', 'docker', 'kubernetes',
            'javascript', 'typescript', 'react', 'vue', 'node', 'python', 'java', 'c#', 'aws',
            'azure', 'gcp', 'html', 'css', 'tailwind', 'git', 'rest', 'graphql'
        ];

        $lower = strtolower($text);

        return collect($dictionary)
            ->filter(fn($skill) => str_contains($lower, strtolower($skill)))
            ->values()
            ->all();
    }

    private function parseJsonPayload(string $raw): array
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Handle common model output style: ```json ... ```
        $withoutFence = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $trimmed);
        $withoutFence = trim((string) $withoutFence);
        $decoded = json_decode($withoutFence, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback: extract first JSON object from mixed text.
        if (preg_match('/\{.*\}/s', $trimmed, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function looksLikeAnalysisPayload(array $data): bool
    {
        return array_key_exists('match_score', $data)
            || array_key_exists('reasoning', $data)
            || array_key_exists('recommendation', $data)
            || array_key_exists('matched_skills', $data)
            || array_key_exists('missing_skills', $data);
    }

    private function prepareText(string $text, int $maxChars): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($maxChars <= 0 || mb_strlen($clean) <= $maxChars) {
            return $clean;
        }

        return mb_substr($clean, 0, $maxChars);
    }
}
