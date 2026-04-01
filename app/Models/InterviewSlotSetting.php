<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSlotSetting extends Model
{
    protected $fillable = [
        'company_id',
        'timezone',
        'slot_duration_minutes',
        'buffer_minutes',
        'weekend_enabled',
        'auto_generate_days',
        'default_mode',
        'default_location',
        'default_meeting_link',
        'weekdays',
    ];

    protected $casts = [
        'weekdays' => 'array',
        'weekend_enabled' => 'boolean',
        'slot_duration_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'auto_generate_days' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public static function defaultWeekdays(): array
    {
        return [
            'mon' => ['enabled' => true, 'start' => '09:00', 'end' => '17:30'],
            'tue' => ['enabled' => true, 'start' => '09:00', 'end' => '17:30'],
            'wed' => ['enabled' => true, 'start' => '09:00', 'end' => '17:30'],
            'thu' => ['enabled' => true, 'start' => '09:00', 'end' => '17:30'],
            'fri' => ['enabled' => true, 'start' => '09:00', 'end' => '17:30'],
            'sat' => ['enabled' => false, 'start' => '09:00', 'end' => '13:00'],
            'sun' => ['enabled' => false, 'start' => '09:00', 'end' => '13:00'],
        ];
    }
}

