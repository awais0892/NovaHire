<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'company_id',
        'application_id',
        'candidate_id',
        'template',
        'channel',
        'direction',
        'recipient_email',
        'subject',
        'provider',
        'provider_message_id',
        'status',
        'error_message',
        'meta',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}

