<?php

namespace App\Livewire\Recruiter;

use App\Jobs\ProcessCvAnalysis;
use App\Models\Application;
use App\Models\JobListing;
use Livewire\Component;
use Livewire\WithPagination;

class CandidateRankings extends Component
{
    use WithPagination;

    public int $jobId;
    public string $search = '';
    public string $statusFilter = '';
    public string $scoreFilter = '';
    public string $sortBy = 'ai_score';
    public string $sortDir = 'desc';
    public bool $showRejectModal = false;
    public ?int $rejectId = null;
    public string $rejectReason = '';

    public function mount(JobListing $job): void
    {
        $this->jobId = JobListing::myCompany()->findOrFail($job->id)->id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        $this->sortDir = $this->sortBy === $column
            ? ($this->sortDir === 'asc' ? 'desc' : 'asc')
            : 'desc';
        $this->sortBy = $column;
    }

    public function updateStatus(int $applicationId, string $status): void
    {
        $application = Application::myCompany()->findOrFail($applicationId);
        $application->update([
            'status' => $status,
            'status_changed_at' => now(),
        ]);
        $this->dispatch('status-updated');
        session()->flash('success', 'Candidate status updated.');
    }

    public function shortlist(int $applicationId): void
    {
        $this->updateStatus($applicationId, 'shortlisted');
    }

    public function confirmReject(int $applicationId): void
    {
        $this->rejectId = $applicationId;
        $this->showRejectModal = true;
    }

    public function reject(): void
    {
        $application = Application::myCompany()->findOrFail($this->rejectId);
        $application->update([
            'status' => 'rejected',
            'recruiter_notes' => $this->rejectReason,
            'status_changed_at' => now(),
        ]);
        $this->showRejectModal = false;
        $this->rejectReason = '';
        session()->flash('success', 'Candidate rejected.');
    }

    public function runAnalysis(int $applicationId): void
    {
        $application = Application::myCompany()
            ->with('candidate')
            ->findOrFail($applicationId);

        $application->candidate->update([
            'cv_status' => 'pending',
            'cv_raw_text' => null,
        ]);

        ProcessCvAnalysis::dispatchSmart($application);

        session()->flash('success', 'AI analysis started for selected candidate.');
    }

    public function render()
    {
        $job = JobListing::myCompany()->with('skills')->findOrFail($this->jobId);

        $applications = Application::with(['candidate', 'aiAnalysis'])
            ->where('job_listing_id', $this->jobId)
            ->myCompany()
            ->when(
                $this->search,
                fn($q) =>
                $q->whereHas(
                    'candidate',
                    fn($c) =>
                    $c->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                )
            )
            ->when(
                $this->statusFilter,
                fn($q) =>
                $q->where('status', $this->statusFilter)
            )
            ->when($this->scoreFilter === 'high', fn($q) => $q->where('ai_score', '>=', 80))
            ->when($this->scoreFilter === 'medium', fn($q) => $q->whereBetween('ai_score', [50, 79]))
            ->when($this->scoreFilter === 'low', fn($q) => $q->where('ai_score', '<', 50))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(10);

        // Stats for header cards
        $statsBaseQuery = Application::myCompany()->where('job_listing_id', $this->jobId);
        $stats = [
            'total' => (clone $statsBaseQuery)->count(),
            'screened' => (clone $statsBaseQuery)->whereNotNull('ai_score')->count(),
            'shortlisted' => (clone $statsBaseQuery)->where('status', 'shortlisted')->count(),
            'avg_score' => (clone $statsBaseQuery)->avg('ai_score'),
            'top_score' => (clone $statsBaseQuery)->max('ai_score'),
        ];

        return view('livewire.recruiter.candidate-rankings', compact('applications', 'job', 'stats'));
    }
}
