<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobSkill extends Model
{
    use HasFactory;

    protected $fillable = ['job_listing_id', 'skill', 'level'];

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }
}
