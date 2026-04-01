<?php

namespace App\Livewire\Candidate;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\Candidate;
use App\Models\EmailLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class MyApplications extends Component
{
    use WithPagination;

    private static ?bool $interviewsTableExists = null;

    private const STATUS_STAGES = [
        'applied',
        'screening',
        'shortlisted',
        'interview',
        'offer',
    ];

    private const ALLOWED_SORTS = [
        'recent',
        'oldest',
        'status',
    ];

    public string $statusFilter = '';
    public string $search = '';
    public string $sortBy = 'recent';
    public bool $showWithdrawModal = false;
    public ?int $withdrawId = null;

    protected $queryString = [
        'statusFilter' => ['except' => ''],
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'recent'],
    ];

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(string $value): void
    {
        if (!in_array($value, self::ALLOWED_SORTS, true)) {
            $this->sortBy = 'recent';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->search = '';
        $this->sortBy = 'recent';
        $this->resetPage();
    }

    public function confirmWithdraw(int $id): void
    {
        $this->withdrawId = $id;
        $this->showWithdrawModal = true;
    }

    public function withdraw(): void
    {
        $candidate = $this->resolveCandidate();

        if ($candidate && $this->withdrawId !== null) {
            Application::query()
                ->where('id', $this->withdrawId)
                ->where('candidate_id', $candidate->id)
                ->whereIn('status', ['applied', 'screening'])
                ->delete();
        }

        $this->withdrawId = null;
        $this->showWithdrawModal = false;
        $this->resetPage();
        session()->flash('success', 'Application withdrawn successfully.');
    }

    public function acceptOffer(int $id): void
    {
        $candidate = $this->resolveCandidate();

        if (!$candidate) {
            session()->flash('error', 'Candidate profile not found.');
            return;
        }

        $application = Application::query()
            ->where('id', $id)
            ->where('candidate_id', $candidate->id)
            ->where('status', 'offer')
            ->first();

        if (!$application) {
            session()->flash('error', 'This offer is no longer available.');
            return;
        }

        $application->update([
            'status' => 'hired',
            'status_changed_at' => now(),
        ]);

        $this->resetPage();
        session()->flash('success', 'Offer accepted successfully.');
    }

    public function render()
    {
        $interviewsEnabled = $this->interviewsEnabled();
        $candidate = $this->resolveCandidate();

        $sortBy = in_array($this->sortBy, self::ALLOWED_SORTS, true)
            ? $this->sortBy
            : 'recent';
        if ($sortBy !== $this->sortBy) {
            $this->sortBy = $sortBy;
        }

        $applications = $this->buildApplicationsPaginator(
            candidate: $candidate,
            interviewsEnabled: $interviewsEnabled,
            sortBy: $sortBy,
        );

        if ($applications->count() > 0) {
            $applicationCollection = $applications->getCollection();
            $this->hydratePortalContext($applicationCollection, $candidate);
            $applications->setCollection(
                $applicationCollection->map(function (Application $application) {
                    $application->timeline = $this->buildTimeline($application->status);
                    return $application;
                })
            );
        }

        $statusCounts = $this->buildStatusCounts($candidate);
        $total = (int) collect($statusCounts)->sum();
        $active = max(
            0,
            $total - ((int) ($statusCounts['hired'] ?? 0) + (int) ($statusCounts['rejected'] ?? 0))
        );

        $stats = [
            'total' => $total,
            'active' => $active,
            'interviews' => (int) ($statusCounts['interview'] ?? 0),
            'offers' => (int) ($statusCounts['offer'] ?? 0),
        ];

        $activeFilterCount = collect([
            $this->statusFilter !== '',
            trim($this->search) !== '',
            $sortBy !== 'recent',
        ])->filter()->count();

        $isProcessing = in_array($candidate?->cv_status, ['pending', 'processing'], true);

        return view(
            'livewire.candidate.my-applications',
            compact(
                'applications',
                'stats',
                'statusCounts',
                'isProcessing',
                'interviewsEnabled',
                'activeFilterCount',
                'sortBy',
            )
        )->layout('layouts.app');
    }

    private function buildApplicationsPaginator(?Candidate $candidate, bool $interviewsEnabled, string $sortBy): LengthAwarePaginator
    {
        if (!$candidate) {
            return new LengthAwarePaginator(
                [],
                0,
                9,
                1,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }

        $relations = [
            'jobListing:id,company_id,title,slug,location,location_label,job_type,location_type,salary_min,salary_max,salary_currency,salary_visible,published_at',
            'jobListing.company:id,name',
        ];

        if ($interviewsEnabled) {
            $relations[] = 'upcomingInterview';
            $relations[] = 'upcomingInterview.scheduler:id,name';
        }

        $query = Application::query()
            ->select([
                'id',
                'job_listing_id',
                'candidate_id',
                'status',
                'recruiter_notes',
                'created_at',
                'updated_at',
                'status_changed_at',
            ])
            ->with($relations)
            ->where('candidate_id', $candidate->id)
            ->when(
                $this->statusFilter !== '',
                fn($q) => $q->where('status', $this->statusFilter)
            )
            ->when(
                trim($this->search) !== '',
                function ($q) {
                    $term = '%' . trim($this->search) . '%';
                    $q->where(function ($subQuery) use ($term) {
                        $subQuery
                            ->where('status', 'like', $term)
                            ->orWhereHas(
                                'jobListing',
                                fn($jobQuery) => $jobQuery->where('title', 'like', $term)
                                    ->orWhereHas('company', fn($companyQuery) => $companyQuery->where('name', 'like', $term))
                            );
                    });
                }
            );

        $this->applySorting($query, $sortBy);

        return $query->paginate(9);
    }

    private function hydratePortalContext(Collection $applications, ?Candidate $candidate): void
    {
        if (!$candidate || $applications->isEmpty()) {
            return;
        }

        $applicationIds = $applications
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($applicationIds->isEmpty()) {
            return;
        }

        $notesByApplication = ApplicationNote::query()
            ->select([
                'id',
                'application_id',
                'author_user_id',
                'source',
                'subject',
                'content',
                'created_at',
            ])
            ->with('author:id,name')
            ->whereIn('application_id', $applicationIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('application_id')
            ->map(fn(Collection $notes) => $notes->take(5)->values());

        $emailsByApplication = EmailLog::query()
            ->select([
                'id',
                'application_id',
                'candidate_id',
                'template',
                'subject',
                'status',
                'recipient_email',
                'created_at',
                'sent_at',
                'failed_at',
            ])
            ->whereIn('application_id', $applicationIds)
            ->where('candidate_id', $candidate->id)
            ->where('direction', 'outbound')
            ->orderByDesc('id')
            ->get()
            ->groupBy('application_id')
            ->map(fn(Collection $emails) => $emails->take(5)->values());

        $applications->each(function (Application $application) use ($notesByApplication, $emailsByApplication): void {
            $application->notesThread = $notesByApplication->get($application->id, collect());
            $application->emailHistory = $emailsByApplication->get($application->id, collect());
        });
    }

    private function applySorting($query, string $sortBy): void
    {
        if ($sortBy === 'oldest') {
            $query->orderBy('created_at');
            return;
        }

        if ($sortBy === 'status') {
            $query
                ->orderByRaw("
                    CASE status
                        WHEN 'applied' THEN 1
                        WHEN 'screening' THEN 2
                        WHEN 'shortlisted' THEN 3
                        WHEN 'interview' THEN 4
                        WHEN 'offer' THEN 5
                        WHEN 'hired' THEN 6
                        WHEN 'rejected' THEN 7
                        ELSE 8
                    END
                ")
                ->orderByDesc('created_at');
            return;
        }

        $query->orderByDesc('created_at');
    }

    private function buildStatusCounts(?Candidate $candidate): array
    {
        if (!$candidate) {
            return [];
        }

        return Application::query()
            ->where('candidate_id', $candidate->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn($count) => (int) $count)
            ->all();
    }

    private function interviewsEnabled(): bool
    {
        if (self::$interviewsTableExists !== null) {
            return self::$interviewsTableExists;
        }

        return self::$interviewsTableExists = Schema::hasTable('interviews');
    }

    private function buildTimeline(string $status): array
    {
        if ($status === 'rejected') {
            return [
                ['key' => 'applied', 'label' => 'Applied', 'state' => 'complete'],
                ['key' => 'screening', 'label' => 'Screening', 'state' => 'complete'],
                ['key' => 'rejected', 'label' => 'Rejected', 'state' => 'current'],
            ];
        }

        if ($status === 'hired') {
            return [
                ['key' => 'applied', 'label' => 'Applied', 'state' => 'complete'],
                ['key' => 'screening', 'label' => 'Screening', 'state' => 'complete'],
                ['key' => 'interview', 'label' => 'Interview', 'state' => 'complete'],
                ['key' => 'offer', 'label' => 'Offer', 'state' => 'complete'],
                ['key' => 'hired', 'label' => 'Hired', 'state' => 'current'],
            ];
        }

        $currentIndex = array_search($status, self::STATUS_STAGES, true);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        return collect(self::STATUS_STAGES)->map(function (string $stage, int $index) use ($currentIndex) {
            $state = 'upcoming';
            if ($index < $currentIndex) {
                $state = 'complete';
            } elseif ($index === $currentIndex) {
                $state = 'current';
            }

            return [
                'key' => $stage,
                'label' => ucfirst($stage),
                'state' => $state,
            ];
        })->all();
    }

    private function resolveCandidate(): ?Candidate
    {
        $user = auth()->user();

        $candidate = Candidate::query()
            ->select(['id', 'user_id', 'email', 'cv_status'])
            ->where('user_id', $user->id)
            ->first()
            ?? Candidate::query()
                ->select(['id', 'user_id', 'email', 'cv_status'])
                ->where('email', $user->email)
                ->first();

        if ($candidate && empty($candidate->user_id)) {
            $candidate->update(['user_id' => $user->id]);
            $candidate->user_id = $user->id;
        }

        return $candidate;
    }
}
