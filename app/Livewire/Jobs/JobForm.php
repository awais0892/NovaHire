<?php

namespace App\Livewire\Jobs;

use App\Models\JobListing;
use App\Services\LocationSearchService;
use App\Support\AuditLogger;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class JobForm extends Component
{
    use AuthorizesRequests;
    public ?JobListing $job = null;
    public bool $isEditing = false;

    public string $title = '';
    public string $location = '';
    public string $location_label = '';
    public string $location_place_id = '';
    public ?float $location_latitude = null;
    public ?float $location_longitude = null;
    public string $location_city = '';
    public string $location_region = '';
    public string $location_country_code = '';
    public string $location_type = 'onsite';
    public string $job_type = 'full_time';
    public string $department = '';
    public string $experience_level = '';
    public ?float $salary_min = null;
    public ?float $salary_max = null;
    public string $salary_currency = 'GBP';
    public bool $salary_visible = true;
    public string $description = '';
    public string $requirements = '';
    public string $benefits = '';
    public string $status = 'draft';
    public int $vacancies = 1;
    public string $expires_at = '';

    public array $skills = [];
    public string $newSkill = '';
    public string $newSkillLevel = 'required';

    protected function rules(): array
    {
        $locationRules = $this->location_type === 'remote'
            ? ['nullable', 'string', 'max:150']
            : ['required', 'string', 'max:150'];

        return [
            'title' => 'required|string|min:3|max:150',
            'location' => $locationRules,
            'location_type' => 'required|in:onsite,remote,hybrid',
            'job_type' => 'required|in:full_time,part_time,contract,internship',
            'department' => 'nullable|string|max:100',
            'experience_level' => 'nullable|string|max:50',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|gte:salary_min',
            'description' => 'required|string|min:50',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'status' => 'required|in:draft,active,paused,closed',
            'vacancies' => 'required|integer|min:1',
            'expires_at' => 'nullable|date|after:today',
        ];
    }

    public function mount(?JobListing $job = null): void
    {
        if ($job && $job->exists) {
            $this->authorize('view', $job);
            $this->isEditing = true;
            $this->job = $job;

            // Normalize nullable DB columns before assigning to typed properties.
            $this->title = (string) ($job->title ?? '');
            $this->location = (string) ($job->location ?? '');
            $this->location_label = (string) ($job->location_label ?? '');
            $this->location_place_id = (string) ($job->location_place_id ?? '');
            $this->location_latitude = $job->location_latitude !== null ? (float) $job->location_latitude : null;
            $this->location_longitude = $job->location_longitude !== null ? (float) $job->location_longitude : null;
            $this->location_city = (string) ($job->location_city ?? '');
            $this->location_region = (string) ($job->location_region ?? '');
            $this->location_country_code = (string) ($job->location_country_code ?? '');
            $this->location_type = (string) ($job->location_type ?? 'onsite');
            $this->job_type = (string) ($job->job_type ?? 'full_time');
            $this->department = (string) ($job->department ?? '');
            $this->experience_level = (string) ($job->experience_level ?? '');
            $this->salary_min = $job->salary_min !== null ? (float) $job->salary_min : null;
            $this->salary_max = $job->salary_max !== null ? (float) $job->salary_max : null;
            $this->salary_currency = (string) ($job->salary_currency ?? 'GBP');
            $this->salary_visible = (bool) ($job->salary_visible ?? true);
            $this->description = (string) ($job->description ?? '');
            $this->requirements = (string) ($job->requirements ?? '');
            $this->benefits = (string) ($job->benefits ?? '');
            $this->status = (string) ($job->status ?? 'draft');
            $this->vacancies = (int) ($job->vacancies ?? 1);
            $this->expires_at = optional($job->expires_at)?->format('Y-m-d') ?? '';
            $this->skills = $job->skills->map(fn($s) => [
                'skill' => $s->skill,
                'level' => $s->level,
            ])->toArray();
        }
    }

    public function addSkill(): void
    {
        $this->validate(['newSkill' => 'required|string|max:50']);
        $this->skills[] = [
            'skill' => $this->newSkill,
            'level' => $this->newSkillLevel,
        ];
        $this->newSkill = '';
        $this->newSkillLevel = 'required';
    }

    public function removeSkill(int $index): void
    {
        array_splice($this->skills, $index, 1);
    }

    public function updatedLocation(string $value): void
    {
        if (trim($value) !== trim($this->location_label)) {
            $this->clearSelectedLocation();
            $this->location = $value;
        }
    }

    public function updatedLocationType(string $value): void
    {
        if ($value === 'remote' && blank(trim($this->location))) {
            $this->location = 'Remote';
            $this->location_label = 'Remote';
        }

        if ($value !== 'remote' && trim($this->location) === 'Remote' && blank($this->location_place_id)) {
            $this->location = '';
            $this->location_label = '';
        }
    }

    public function selectLocationSuggestion(
        string $label,
        ?string $placeId = null,
        ?string $sessionToken = null,
        mixed $latitude = null,
        mixed $longitude = null,
        ?string $city = null,
        ?string $region = null,
        ?string $countryCode = null,
    ): void {
        $this->resetValidation('location');

        if ($latitude !== null && $longitude !== null) {
            $this->location = $label;
            $this->location_label = $label;
            $this->location_place_id = $placeId ?: '';
            $this->location_latitude = (float) $latitude;
            $this->location_longitude = (float) $longitude;
            $this->location_city = $city ?: '';
            $this->location_region = $region ?: '';
            $this->location_country_code = $countryCode ?: '';
            return;
        }

        if (blank($placeId)) {
            $this->location = $label;
            $this->location_label = $label;
            $this->location_place_id = '';
            $this->location_latitude = null;
            $this->location_longitude = null;
            $this->location_city = '';
            $this->location_region = '';
            $this->location_country_code = '';
            return;
        }

        /** @var LocationSearchService $locationSearch */
        $locationSearch = app(LocationSearchService::class);
        $details = $locationSearch->placeDetails($placeId, $sessionToken);

        $this->location = $label;
        $this->location_label = $label;
        $this->location_place_id = $placeId;

        if (!$details) {
            $this->location_latitude = null;
            $this->location_longitude = null;
            $this->location_city = '';
            $this->location_region = '';
            $this->location_country_code = '';
            return;
        }

        $this->location = $details['label'] ?: $label;
        $this->location_label = $details['label'] ?: $label;
        $this->location_place_id = $details['place_id'] ?: $placeId;
        $this->location_latitude = $details['latitude'];
        $this->location_longitude = $details['longitude'];
        $this->location_city = $details['city'] ?? '';
        $this->location_region = $details['region'] ?? '';
        $this->location_country_code = $details['country_code'] ?? '';
    }

    public function clearSelectedLocation(bool $clearInput = true): void
    {
        $this->location_place_id = '';
        $this->location_latitude = null;
        $this->location_longitude = null;
        $this->location_city = '';
        $this->location_region = '';
        $this->location_country_code = '';
        $this->location_label = '';

        if ($clearInput) {
            $this->location = '';
        }
    }

    public function save(): void
    {
        $this->validate();

        $locationLabel = trim($this->location_label ?: $this->location);
        if ($this->location_type === 'remote' && $locationLabel === '') {
            $locationLabel = 'Remote';
        }

        $data = [
            'title'            => $this->title,
            'location'         => $locationLabel,
            'location_label'   => $locationLabel,
            'location_place_id' => $this->location_place_id ?: null,
            'location_latitude' => $this->location_latitude,
            'location_longitude' => $this->location_longitude,
            'location_city' => $this->location_city ?: null,
            'location_region' => $this->location_region ?: null,
            'location_country_code' => $this->location_country_code ?: null,
            'location_type'    => $this->location_type,
            'job_type'         => $this->job_type,
            'department'       => $this->department,
            'experience_level' => $this->experience_level,
            'salary_min'       => $this->salary_min,
            'salary_max'       => $this->salary_max,
            'salary_currency'  => $this->salary_currency,
            'salary_visible'   => $this->salary_visible,
            'description'      => $this->description,
            'requirements'     => $this->requirements,
            'benefits'         => $this->benefits,
            'status'           => $this->status,
            'vacancies'        => $this->vacancies,
            'expires_at'       => $this->expires_at ?: null,
            'published_at'     => $this->status === 'active' ? now() : null,
        ];

        if ($this->isEditing) {
            $this->authorize('update', $this->job);
            $this->job->update($data);
            $job = $this->job;
            AuditLogger::log('recruiter.job.updated', $job, [
                'status' => $job->status,
                'title' => $job->title,
            ]);
        } else {
            $this->authorize('create', JobListing::class);
            $job = JobListing::create($data);
            AuditLogger::log('recruiter.job.created', $job, [
                'status' => $job->status,
                'title' => $job->title,
            ]);
        }

        $job->skills()->delete();
        foreach ($this->skills as $skill) {
            $job->skills()->create($skill);
        }

        session()->flash('success', $this->isEditing ? 'Job updated successfully!' : 'Job created successfully!');

        redirect()->route('recruiter.jobs.index');
    }

    public function render()
    {
        return view('livewire.jobs.job-form');
    }
}
