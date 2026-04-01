<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationNote extends Model
{
    protected $fillable = [
        'company_id',
        'application_id',
        'candidate_id',
        'author_user_id',
        'note_type',
        'source',
        'subject',
        'content',
        'meta',
        'sent_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
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

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}

