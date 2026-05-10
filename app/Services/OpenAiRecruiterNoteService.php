<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAiRecruiterNoteService
{
    public function isConfigured(): bool
    {
        return filled(config('openai.api_key'));
    }

    public function generateRecruiterNote(array $context): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = (string) env('AI_NOTE_MODEL', env('AI_ANALYSIS_MODEL', 'gpt-4o-mini'));
        $maxTokens = max(180, (int) env('AI_NOTE_MAX_TOKENS', 420));

        $response = OpenAI::chat()->create([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert recruiter. Return plain text only (no markdown).',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($context),
                ],
            ],
            'temperature' => 0.25,
            'max_tokens' => $maxTokens,
        ]);

        $content = trim((string) ($response->choices[0]->message->content ?? ''));

        return [
            'content' => $content,
            'model' => $model,
            'usage' => [
                'input_tokens' => (int) ($response->usage->promptTokens ?? 0),
                'output_tokens' => (int) ($response->usage->completionTokens ?? 0),
                'total_tokens' => (int) ($response->usage->totalTokens ?? 0),
            ],
            'raw' => $response->toArray(),
        ];
    }

    private function buildPrompt(array $context): string
    {
        $candidateName = trim((string) ($context['candidate_name'] ?? 'Candidate'));
        $role = trim((string) ($context['role'] ?? 'the role'));
        $score = (int) ($context['score'] ?? 0);
        $decision = trim((string) ($context['decision'] ?? 'shortlist'));
        $tone = trim((string) ($context['tone'] ?? 'professional and constructive'));

        return <<<PROMPT
Write a concise recruiter note in {$tone} tone.

Candidate: {$candidateName}
Role: {$role}
Score: {$score}/100
Decision: {$decision}

Requirements:
- 2 short paragraphs.
- Mention one strength and one suggested next step.
- Keep wording clear and respectful.
- Do not include markdown.
PROMPT;
    }
}

