<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'application_id',
        'interview_slot_id',
        'scheduled_by',
        'starts_at',
        'ends_at',
        'timezone',
        'mode',
        'meeting_link',
        'google_calendar_event_id',
        'google_calendar_event_link',
        'google_calendar_synced_at',
        'location',
        'notes',
        'status',
        'candidate_response',
        'candidate_responded_at',
        'reminder_24h_sent_at',
        'reminder_1h_sent_at',
        'cancelled_at',
        'cancelled_reason',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'candidate_responded_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
        'reminder_1h_sent_at' => 'datetime',
        'google_calendar_synced_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function slot()
    {
        return $this->belongsTo(InterviewSlot::class, 'interview_slot_id');
    }
}
