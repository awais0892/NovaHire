<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'job_listing_id', 'candidate_id', 'company_id',
        'status', 'cover_letter', 'recruiter_notes',
        'ai_score', 'status_changed_at',
    ];

    protected $casts = [
        'status_changed_at' => 'datetime',
    ];

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function aiAnalysis()
    {
        return $this->hasOne(AiAnalysis::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function notes()
    {
        return $this->hasMany(ApplicationNote::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function bookedInterviewSlot()
    {
        return $this->hasOne(InterviewSlot::class, 'booked_application_id');
    }

    public function upcomingInterview()
    {
        return $this->hasOne(Interview::class)
            ->where('interviews.status', 'scheduled')
            ->where('interviews.starts_at', '>=', now())
            ->orderBy('interviews.starts_at')
            ->orderBy('interviews.id')
            ->select('interviews.*');
    }

    public function scopeMyCompany($query)
    {
        return $query->where('company_id', auth()->user()->company_id ?? null);
    }
}
