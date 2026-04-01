<?php

namespace App\Livewire\Candidate;

use App\Models\JobListing;
use App\Models\Application;
use App\Models\Candidate;
use App\Services\LocationSearchService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class JobBoard extends Component
{
    use WithPagination;

    private const ALLOWED_SORTS = ['published_at', 'salary_max', 'distance_km'];
    private const ALLOWED_RADII = ['10', '25', '50', '100'];

    public string $search = '';
    public string $locationSearch = '';
    public string $locationPlaceId = '';
    public ?float $locationLatitude = null;
    public ?float $locationLongitude = null;
    public string $radiusKm = '';
    public string $typeFilter = '';
    public string $locationTypeFilter = '';
    public string $experienceFilter = '';
    public string $salaryMin = '';
    public string $salaryMax = '';
    public string $postedWithinDays = '';
    public string $sortBy = 'published_at';
    public ?int $selectedMapJobId = null;
    public bool $locationFromBrowser = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'locationSearch' => ['except' => ''],
        'radiusKm' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'locationTypeFilter' => ['except' => ''],
        'experienceFilter' => ['except' => ''],
        'salaryMin' => ['except' => ''],
        'salaryMax' => ['except' => ''],
        'postedWithinDays' => ['except' => ''],
        'sortBy' => ['except' => 'published_at'],
    ];

    public function mount(): void
    {
        if (auth()->user()?->hasRole('candidate') && request()->routeIs('jobs.index')) {
            $this->redirectRoute('candidate.jobs.index', navigate: true);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLocationSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRadiusKm(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingExperienceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSalaryMin(): void
    {
        $this->resetPage();
    }

    public function updatingSalaryMax(): void
    {
        $this->resetPage();
    }

    public function updatingPostedWithinDays(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(string $value): void
    {
        if ($value === 'distance_km' && ($this->locationLatitude === null || $this->locationLongitude === null)) {
            $this->sortBy = 'published_at';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->locationSearch = '';
        $this->locationPlaceId = '';
        $this->locationLatitude = null;
        $this->locationLongitude = null;
        $this->radiusKm = '';
        $this->typeFilter = '';
        $this->locationTypeFilter = '';
        $this->experienceFilter = '';
        $this->salaryMin = '';
        $this->salaryMax = '';
        $this->postedWithinDays = '';
        $this->sortBy = 'published_at';
        $this->selectedMapJobId = null;
        $this->locationFromBrowser = false;
        $this->resetPage();
    }

    public function selectSearchLocation(
        string $label,
        ?string $placeId = null,
        ?string $sessionToken = null,
        mixed $latitude = null,
        mixed $longitude = null,
        ?string $city = null,
        ?string $region = null,
        ?string $countryCode = null,
    ): void {
        $this->locationSearch = $label;
        $this->locationPlaceId = $placeId ?? '';
        $this->locationLatitude = null;
        $this->locationLongitude = null;
        $this->selectedMapJobId = null;
        $this->locationFromBrowser = false;

        if ($latitude !== null && $longitude !== null) {
            $this->locationLatitude = (float) $latitude;
            $this->locationLongitude = (float) $longitude;
            $this->sortBy = 'distance_km';
            $this->resetPage();
            return;
        }

        if (blank($placeId)) {
            $this->resetPage();
            return;
        }

        /** @var LocationSearchService $locationSearch */
        $locationSearch = app(LocationSearchService::class);
        $details = $locationSearch->placeDetails($placeId, $sessionToken);

        if ($details) {
            $this->locationSearch = $details['label'] ?: $label;
            $this->locationPlaceId = $details['place_id'] ?: $placeId;
            $this->locationLatitude = $details['latitude'];
            $this->locationLongitude = $details['longitude'];
            $this->sortBy = 'distance_km';
        }

        $this->resetPage();
    }

    public function clearSelectedSearchLocation(bool $clearInput = true): void
    {
        $this->locationPlaceId = '';
        $this->locationLatitude = null;
        $this->locationLongitude = null;
        $this->selectedMapJobId = null;
        $this->locationFromBrowser = false;

        if ($clearInput) {
            $this->locationSearch = '';
        }

        if ($this->sortBy === 'distance_km') {
            $this->sortBy = 'published_at';
        }

        $this->resetPage();
    }

    public function useBrowserLocation(float $latitude, float $longitude): string
    {
        /** @var LocationSearchService $locationSearch */
        $locationSearch = app(LocationSearchService::class);
        $details = $locationSearch->reverseGeocode($latitude, $longitude);
        $label = $details['label'] ?? sprintf('Current location (%.4f, %.4f)', $latitude, $longitude);

        $this->locationLatitude = $latitude;
        $this->locationLongitude = $longitude;
        $this->locationSearch = $label;
        $this->locationPlaceId = $details['place_id'] ?? '';
        $this->sortBy = 'distance_km';
        $this->selectedMapJobId = null;
        $this->locationFromBrowser = true;

        $this->resetPage();

        return $label;
    }

    public function selectMapJob(int $jobId): void
    {
        $this->selectedMapJobId = $jobId;
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $hasSearchCoordinates = $this->locationLatitude !== null && $this->locationLongitude !== null;
        $sortBy = in_array($this->sortBy, self::ALLOWED_SORTS, true)
            ? $this->sortBy
            : 'published_at';
        if ($sortBy === 'distance_km' && !$hasSearchCoordinates) {
            $this->sortBy = 'published_at';
            $sortBy = 'published_at';
        }
        $radiusKm = in_array($this->radiusKm, self::ALLOWED_RADII, true)
            ? (float) $this->radiusKm
            : null;

        // Already applied job IDs for current user
        $appliedJobIds = [];
        if (auth()->check()) {
            $userId = (int) auth()->id();
            $userEmail = (string) auth()->user()->email;
            $cacheKey = sprintf(
                'candidate:applied-job-ids:%d:%s',
                $userId,
                md5(strtolower($userEmail))
            );

            $appliedJobIds = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId, $userEmail) {
                $candidateIds = Candidate::query()
                    ->where('user_id', $userId)
                    ->orWhere('email', $userEmail)
                    ->pluck('id');

                if ($candidateIds->isEmpty()) {
                    return [];
                }

                Candidate::query()
                    ->whereIn('id', $candidateIds)
                    ->whereNull('user_id')
                    ->update(['user_id' => $userId]);

                return Application::query()
                    ->whereIn('candidate_id', $candidateIds)
                    ->pluck('job_listing_id')
                    ->all();
            });
        }

        $jobs = JobListing::query()
            ->select([
                'id',
                'company_id',
                'slug',
                'title',
                'location',
                'location_label',
                'location_latitude',
                'location_longitude',
                'location_city',
                'location_region',
                'location_country_code',
                'location_type',
                'job_type',
                'experience_level',
                'salary_min',
                'salary_max',
                'salary_visible',
                'published_at',
                'description',
                'status',
            ])
            ->with([
                'company:id,name',
                'skills:id,job_listing_id,skill',
            ])
            ->active()
            ->when(
                $this->search,
                fn($q) =>
                $q->where(function ($subQuery) {
                    $subQuery->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('location', 'like', "%{$this->search}%")
                        ->orWhere('location_label', 'like', "%{$this->search}%")
                        ->orWhereHas(
                            'company',
                            fn($companyQuery) =>
                            $companyQuery->where('name', 'like', "%{$this->search}%")
                        );
                })
            )
            ->when(
                $this->locationSearch && $this->locationLatitude === null && $this->locationLongitude === null,
                fn($q) =>
                $q->where(function ($locationQuery) {
                    $locationQuery
                        ->where('location', 'like', '%' . $this->locationSearch . '%')
                        ->orWhere('location_label', 'like', '%' . $this->locationSearch . '%');
                })
            )
            ->when(
                $this->typeFilter,
                fn($q) =>
                $q->where('job_type', $this->typeFilter)
            )
            ->when(
                $this->locationTypeFilter,
                fn($q) =>
                $q->where('location_type', $this->locationTypeFilter)
            )
            ->when(
                $this->experienceFilter,
                fn($q) =>
                $q->where('experience_level', $this->experienceFilter)
            )
            ->when(
                $this->salaryMin,
                fn($q) =>
                $q->where('salary_min', '>=', $this->salaryMin)
            )
            ->when(
                $this->salaryMax,
                fn($q) =>
                $q->where(function ($salaryQuery) {
                    $salaryQuery
                        ->where('salary_max', '<=', $this->salaryMax)
                        ->orWhere(function ($fallbackQuery) {
                            $fallbackQuery
                                ->whereNull('salary_max')
                                ->where('salary_min', '<=', $this->salaryMax);
                        });
                })
            )
            ->when(
                $this->postedWithinDays !== '' && ctype_digit($this->postedWithinDays),
                fn($q) =>
                $q->where('published_at', '>=', now()->subDays((int) $this->postedWithinDays))
            )
            ->when(
                $radiusKm && $this->locationLatitude !== null && $this->locationLongitude !== null,
                function ($query) use ($radiusKm) {
                    $latitude = $this->locationLatitude;
                    $longitude = $this->locationLongitude;
                    $latRange = $radiusKm / 111.045;
                    $lngRange = $radiusKm / max(111.045 * cos(deg2rad($latitude)), 0.01);
                    $distanceSql = '(6371 * acos(cos(radians(?)) * cos(radians(location_latitude)) * cos(radians(location_longitude) - radians(?)) + sin(radians(?)) * sin(radians(location_latitude))))';

                    $query->selectRaw(
                        $distanceSql . ' as distance_km',
                        [$latitude, $longitude, $latitude]
                    );

                    if ($this->locationTypeFilter === 'remote') {
                        if ($this->sortBy === 'distance_km') {
                            $query->orderBy('published_at', 'desc');
                        }
                        return;
                    }

                    $query->where(function ($locationQuery) use (
                        $latitude,
                        $longitude,
                        $latRange,
                        $lngRange,
                        $distanceSql,
                        $radiusKm
                    ) {
                        if ($this->locationTypeFilter === '') {
                            $locationQuery->where('location_type', 'remote')
                                ->orWhere(function ($nearbyQuery) use (
                                    $latitude,
                                    $longitude,
                                    $latRange,
                                    $lngRange,
                                    $distanceSql,
                                    $radiusKm
                                ) {
                                    $nearbyQuery
                                        ->whereIn('location_type', ['onsite', 'hybrid'])
                                        ->whereBetween('location_latitude', [$latitude - $latRange, $latitude + $latRange])
                                        ->whereBetween('location_longitude', [$longitude - $lngRange, $longitude + $lngRange])
                                        ->whereNotNull('location_latitude')
                                        ->whereNotNull('location_longitude')
                                        ->whereRaw($distanceSql . ' <= ?', [
                                            $latitude,
                                            $longitude,
                                            $latitude,
                                            $radiusKm,
                                        ]);
                                });
                            return;
                        }

                        $locationQuery
                            ->whereBetween('location_latitude', [$latitude - $latRange, $latitude + $latRange])
                            ->whereBetween('location_longitude', [$longitude - $lngRange, $longitude + $lngRange])
                            ->whereNotNull('location_latitude')
                            ->whereNotNull('location_longitude')
                            ->whereRaw($distanceSql . ' <= ?', [
                                $latitude,
                                $longitude,
                                $latitude,
                                $radiusKm,
                            ]);
                    });

                    if ($this->sortBy === 'distance_km') {
                        $query->orderByRaw(
                            'CASE WHEN location_type = ? THEN 1 ELSE 0 END ASC',
                            ['remote']
                        );
                        $query->orderBy('distance_km');
                    }
                }
            )
            ->when(
                $sortBy === 'distance_km' && !$hasSearchCoordinates,
                fn ($query) => $query->orderBy('published_at', 'desc'),
            )
            ->when(
                $sortBy === 'published_at',
                fn ($query) => $query->orderBy('published_at', 'desc'),
            )
            ->when(
                $sortBy === 'salary_max',
                fn ($query) => $query->orderBy('salary_max', 'desc'),
            )
            ->simplePaginate(12);

        $selectedMapJob = null;
        if ($this->selectedMapJobId !== null) {
            $selectedMapJob = $jobs->getCollection()->firstWhere('id', $this->selectedMapJobId);

            if (!$selectedMapJob || !$selectedMapJob->has_precise_location) {
                $selectedMapJob = null;
                $this->selectedMapJobId = null;
            }
        }

        // If no map pin is selected and no search coordinates exist, keep the previous
        // "focused job" behavior by default. When coordinates exist (selected place or
        // browser location), preserve the map center on those coordinates instead.
        if ($selectedMapJob === null && !$hasSearchCoordinates) {
            $selectedMapJob = $jobs->getCollection()->first(
                fn (JobListing $job) => $job->has_precise_location
            );
        }

        $this->selectedMapJobId = $selectedMapJob?->id;

        $isCandidateDashboard = request()->routeIs('candidate.jobs.*');
        $jobsIndexRoute = $isCandidateDashboard ? 'candidate.jobs.index' : 'jobs.index';
        $jobsShowRoute = $isCandidateDashboard ? 'candidate.jobs.show' : 'jobs.show';
        $applyRoute = $isCandidateDashboard ? 'candidate.apply' : 'jobs.apply';
        $isCandidateUser = auth()->check() && auth()->user()->hasRole('candidate');

        $view = view(
            'livewire.candidate.job-board',
            compact(
                'jobs',
                'appliedJobIds',
                'selectedMapJob',
                'jobsIndexRoute',
                'jobsShowRoute',
                'applyRoute',
                'isCandidateUser'
            )
        );

        if ($isCandidateDashboard) {
            return $view->layout('layouts.app', [
                'title' => 'Browse Jobs',
            ]);
        }

        return $view->layout('layouts.public', [
            'title' => 'Job Board',
            'metaDescription' => 'Discover active opportunities and apply in a few clicks.',
            'metaImage' => asset('images/og/product.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Jobs', 'url' => route($jobsIndexRoute)],
            ],
        ]);
    }
}
