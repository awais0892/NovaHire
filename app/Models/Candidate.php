<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Candidate extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id', 'company_id', 'name', 'email', 'phone',
        'location', 'linkedin', 'github', 'portfolio',
        'cv_path', 'cv_original_name', 'cv_raw_text',
        'extracted_skills', 'extracted_experience',
        'extracted_education', 'cv_status',
    ];

    protected $casts = [
        'extracted_skills'     => 'array',
        'extracted_experience' => 'array',
        'extracted_education'  => 'array',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aiAnalyses()
    {
        return $this->hasMany(AiAnalysis::class);
    }

    public function applicationNotes()
    {
        return $this->hasMany(ApplicationNote::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function scopeMyCompany($query)
    {
        return $query->where('company_id', auth()->user()->company_id ?? null);
    }
}
