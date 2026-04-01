<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSlotException extends Model
{
    protected $fillable = [
        'company_id',
        'exception_date',
        'exception_type',
        'is_available',
        'starts_at_time',
        'ends_at_time',
        'reason',
        'meta',
    ];

    protected $casts = [
        'exception_date' => 'date',
        'is_available' => 'boolean',
        'meta' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

