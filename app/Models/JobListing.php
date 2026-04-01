<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobListing extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'company_id', 'created_by', 'title', 'slug', 'location', 'location_label',
        'location_place_id', 'location_latitude', 'location_longitude',
        'location_city', 'location_region', 'location_country_code',
        'location_type', 'job_type', 'department', 'experience_level',
        'salary_min', 'salary_max', 'salary_currency', 'salary_visible',
        'description', 'requirements', 'benefits', 'status',
        'published_at', 'expires_at', 'vacancies', 'applications_count',
    ];

    protected $casts = [
        'published_at'   => 'datetime',
        'expires_at'     => 'datetime',
        'salary_visible' => 'boolean',
        'location_latitude' => 'float',
        'location_longitude' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($job) {
            $job->slug = Str::slug($job->title) . '-' . Str::random(6);
            $job->company_id = auth()->user()->company_id ?? $job->company_id;
            $job->created_by = auth()->id() ?? $job->created_by;
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function skills()
    {
        return $this->hasMany(JobSkill::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function scopeMyCompany($query)
    {
        return $query->where('company_id', auth()->user()->company_id ?? null);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getSalaryRangeAttribute(): string
    {
        if (!$this->salary_visible) return 'Competitive';
        if ($this->salary_min && $this->salary_max) {
            return "GBP {$this->salary_min} - GBP {$this->salary_max}";
        }
        return 'Not specified';
    }

    public function getDisplayLocationAttribute(): string
    {
        return $this->location_label ?: ($this->location ?: 'Not specified');
    }

    public function getHasPreciseLocationAttribute(): bool
    {
        return $this->location_latitude !== null && $this->location_longitude !== null;
    }

    public function getMapEmbedUrlAttribute(): ?string
    {
        if (!$this->has_precise_location) {
            return null;
        }

        $bbox = $this->map_bbox;

        return sprintf(
            'https://www.openstreetmap.org/export/embed.html?bbox=%s,%s,%s,%s&layer=mapnik&marker=%s,%s',
            $bbox['min_lng'],
            $bbox['min_lat'],
            $bbox['max_lng'],
            $bbox['max_lat'],
            $this->location_latitude,
            $this->location_longitude
        );
    }

    public function getMapOpenUrlAttribute(): ?string
    {
        if (!$this->has_precise_location) {
            return null;
        }

        return sprintf(
            'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=13/%s/%s',
            $this->location_latitude,
            $this->location_longitude,
            $this->location_latitude,
            $this->location_longitude
        );
    }

    public function getMapDirectionsUrlAttribute(): ?string
    {
        return $this->map_open_url;
    }

    public function getMapBboxAttribute(): ?array
    {
        if (!$this->has_precise_location) {
            return null;
        }

        $latDelta = 0.08;
        $lngDelta = max(0.08, 0.08 * cos(deg2rad($this->location_latitude ?: 0)));

        return [
            'min_lat' => $this->location_latitude - $latDelta,
            'max_lat' => $this->location_latitude + $latDelta,
            'min_lng' => $this->location_longitude - $lngDelta,
            'max_lng' => $this->location_longitude + $lngDelta,
        ];
    }
}

