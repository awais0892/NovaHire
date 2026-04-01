<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSlot extends Model
{
    protected $fillable = [
        'company_id',
        'slot_date',
        'starts_at',
        'ends_at',
        'timezone',
        'duration_minutes',
        'buffer_minutes',
        'mode',
        'is_available',
        'is_bank_holiday',
        'holiday_name',
        'interviewer_user_id',
        'booked_application_id',
        'location',
        'meeting_link',
        'meta',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_available' => 'boolean',
        'is_bank_holiday' => 'boolean',
        'meta' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_user_id');
    }

    public function bookedApplication()
    {
        return $this->belongsTo(Application::class, 'booked_application_id');
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class, 'interview_slot_id');
    }
}

