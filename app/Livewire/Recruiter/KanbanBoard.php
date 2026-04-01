<?php

namespace App\Livewire\Recruiter;

use App\Models\Application;
use App\Models\JobListing;
use App\Notifications\ApplicationStatusChanged;
use App\Jobs\ProcessCvAnalysis;
use Livewire\Component;

class KanbanBoard extends Component
{
    public int $jobId;
    public string $search = '';
    public bool $showNoteModal = false;
    public ?int $noteAppId = null;
    public string $noteText = '';
    public bool $showCardModal = false;
    public ?int $focusedAppId = null;
    public bool $isUpdating = false;

    // Kanban columns config
    public array $columns = [
        'applied' => ['label' => 'Applied', 'icon' => '📥', 'color' => 'border-gray-400'],
        'screening' => ['label' => 'Screening', 'icon' => '🔍', 'color' => 'border-blue-400'],
        'shortlisted' => ['label' => 'Shortlisted', 'icon' => '⭐', 'color' => 'border-purple-400'],
        'interview' => ['label' => 'Interview', 'icon' => '💬', 'color' => 'border-yellow-400'],
        'offer' => ['label' => 'Offer', 'icon' => '🤝', 'color' => 'border-green-400'],
        'hired' => ['label' => 'Hired', 'icon' => '🎉', 'color' => 'border-emerald-500'],
        'rejected' => ['label' => 'Rejected', 'icon' => '❌', 'color' => 'border-red-400'],
    ];

    protected $listeners = [
        'cardMoved' => 'handleCardMoved',
        'openNoteModal' => 'openNoteModal',
        'openCard' => 'openCard',
    ];

    public function mount(JobListing $job): void
    {
        $this->jobId = JobListing::myCompany()->findOrFail($job->id)->id;
    }

    // Called by JS when card is drag-dropped
    public function handleCardMoved(int $applicationId, string $newStatus): void
    {
        if (!array_key_exists($newStatus, $this->columns)) {
            return;
        }

        $application = Application::myCompany()->findOrFail($applicationId);
        $oldStatus = $application->status;

        if ($oldStatus === $newStatus)
            return;

        $this->isUpdating = true;
        try {
            $application->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
            ]);

            // Notify candidate
            if ($application->candidate && $application->candidate->user) {
                try {
                    $application->candidate->user->notify(
                        new ApplicationStatusChanged($application)
                    );
                } catch (\Throwable $exception) {
                    logger()->warning('Kanban notification delivery failed. Continuing status update.', [
                        'application_id' => $application->id,
                        'candidate_user_id' => $application->candidate->user->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $this->dispatch('kanban-updated');
            session()->flash('success', "Candidate moved to {$newStatus}.");
        } finally {
            $this->isUpdating = false;
        }
    }

    public function openNoteModal(int $applicationId): void
    {
        $application = Application::myCompany()->findOrFail($applicationId);
        $this->noteAppId = $applicationId;
        $this->noteText = (string) ($application->recruiter_notes ?? '');
        $this->showNoteModal = true;
    }

    public function saveNote(): void
    {
        Application::myCompany()
            ->findOrFail($this->noteAppId)
            ->update(['recruiter_notes' => $this->noteText]);

        $this->showNoteModal = false;
        $this->noteText = '';
    }

    public function openCard(int $applicationId): void
    {
        $this->focusedAppId = $applicationId;
        $this->showCardModal = true;
    }

    public function quickMove(int $applicationId, string $direction): void
    {
        $stages = array_keys($this->columns);
        $application = Application::myCompany()->findOrFail($applicationId);
        $currentIndex = array_search($application->status, $stages);

        $newIndex = $direction === 'forward'
            ? min($currentIndex + 1, count($stages) - 1)
            : max($currentIndex - 1, 0);

        $this->handleCardMoved($applicationId, $stages[$newIndex]);
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
        $job = JobListing::myCompany()->findOrFail($this->jobId);

        $list = Application::with(['candidate', 'aiAnalysis'])
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
            ->orderByDesc('ai_score')
            ->get();

        $applications = $list->groupBy('status');

        // Column counts (synced with active filters/search)
        $counts = $list
            ->groupBy('status')
            ->map(fn($group) => $group->count());

        $boardStats = [
            'total' => $list->count(),
            'screening' => (int) ($counts->get('screening', 0)),
            'interview' => (int) ($counts->get('interview', 0)),
            'hired' => (int) ($counts->get('hired', 0)),
        ];

        // Focused card data
        $focusedApp = $this->focusedAppId
            ? Application::myCompany()->with(['candidate', 'aiAnalysis', 'jobListing'])
                ->find($this->focusedAppId)
            : null;

        return view(
            'livewire.recruiter.kanban-board',
            compact('job', 'applications', 'counts', 'focusedApp', 'boardStats')
        );
    }
}
