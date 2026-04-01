<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'candidate_id', 'job_listing_id',
        'match_score', 'matched_skills', 'missing_skills',
        'reasoning', 'strengths', 'weaknesses',
        'interview_questions', 'recommendation', 'tokens_used',
    ];

    protected $casts = [
        'matched_skills'      => 'array',
        'missing_skills'      => 'array',
        'interview_questions' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function getScoreBadgeColorAttribute(): string
    {
        return match (true) {
            $this->match_score >= 80 => 'badge-success',
            $this->match_score >= 60 => 'badge-warning',
            default                  => 'badge-error',
        };
    }

    // Backward-compatible aliases for templates/components that still expect
    // legacy AI analysis keys.
    public function getSummaryAttribute(): ?string
    {
        return $this->firstFilledText([
            $this->reasoning,
            $this->strengths,
            $this->weaknesses,
        ]);
    }

    public function getKeyStrengthsAttribute(): ?string
    {
        return $this->firstFilledText([$this->strengths]);
    }

    public function getKeyConcernsAttribute(): ?string
    {
        return $this->firstFilledText([$this->weaknesses]);
    }

    private function firstFilledText(array $values): ?string
    {
        foreach ($values as $value) {
            $text = trim((string) $value);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }
}
