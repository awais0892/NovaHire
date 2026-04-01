<?php

namespace App\Livewire\Jobs;

use App\Models\JobListing;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class JobIndex extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public string $locationFilter = '';
    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';
    public bool $showDeleteModal = false;
    public ?int $deleteId = null;

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'typeFilter'   => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        $this->sortDir = $this->sortBy === $column
            ? ($this->sortDir === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->sortBy = $column;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $job = JobListing::myCompany()->findOrFail($this->deleteId);
        $this->authorize('delete', $job);
        $job->delete();
        $this->showDeleteModal = false;
        session()->flash('success', 'Job listing deleted successfully.');
    }

    public function updateStatus(int $id, string $status): void
    {
        $job = JobListing::myCompany()->findOrFail($id);
        $this->authorize('update', $job);
        $job->update([
            'status'       => $status,
            'published_at' => $status === 'active' ? now() : null,
        ]);
        session()->flash('success', 'Job status updated.');
    }

    public function render()
    {
        $jobs = JobListing::myCompany()
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('title', 'like', "%{$this->search}%")
                       ->orWhere('location', 'like', "%{$this->search}%")
                       ->orWhere('department', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn($q) => $q->where('job_type', $this->typeFilter))
            ->when($this->locationFilter, fn($q) => $q->where('location_type', $this->locationFilter))
            ->withCount('applications')
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(10);

        return view('livewire.jobs.job-index', compact('jobs'));
    }
}
