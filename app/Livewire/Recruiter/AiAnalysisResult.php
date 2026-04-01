<?php

namespace App\Livewire\Recruiter;

use App\Models\Application;
use App\Jobs\ProcessCvAnalysis;
use Livewire\Component;

class AiAnalysisResult extends Component
{
    public int $applicationId;
    public string $activeTab = 'analysis';
    public string $notesDraft = '';
    public bool $notesSaved = false;
    public bool $autoRefreshEnabled = false;

    public function mount(?int $application = null, ?int $applicationId = null): void
    {
        $resolved = $applicationId ?? $application;

        if (!$resolved) {
            abort(404, 'Application not found.');
        }

        $this->applicationId = (int) $resolved;
        $applicationModel = Application::myCompany()->findOrFail($this->applicationId);
        $this->notesDraft = (string) ($applicationModel->recruiter_notes ?? '');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function saveNotes(): void
    {
        $application = Application::myCompany()->findOrFail($this->applicationId);
        $application->update(['recruiter_notes' => $this->notesDraft]);
        $this->notesSaved = true;
    }

    public function reanalyse(): void
    {
        $application = Application::myCompany()->findOrFail($this->applicationId);
        $application->candidate->update(['cv_status' => 'pending']);
        $this->autoRefreshEnabled = true;

        try {
            ProcessCvAnalysis::dispatchSmart($application);
            session()->flash(
                'success',
                config('queue.default') === 'sync'
                ? 'Re-analysis complete!'
                : 'Re-analysis started. Results will update shortly.'
            );
        } catch (\Throwable $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    public function runAnalysisNow(): void
    {
        $application = Application::myCompany()->findOrFail($this->applicationId);
        $application->candidate->update(['cv_status' => 'pending']);
        $this->autoRefreshEnabled = true;

        try {
            ProcessCvAnalysis::dispatchSmart($application);
            session()->flash(
                'success',
                config('queue.default') === 'sync'
                ? 'AI analysis complete!'
                : 'AI analysis started for this application.'
            );
        } catch (\Throwable $e) {
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    public function updateStatus(string $status): void
    {
        $application = Application::myCompany()->findOrFail($this->applicationId);
        $application->update([
            'status' => $status,
            'status_changed_at' => now(),
        ]);
        session()->flash('success', 'Status updated to ' . ucfirst($status));
    }

    public function render()
    {
        $application = Application::myCompany()
            ->with(['candidate', 'jobListing.skills', 'aiAnalysis'])
            ->findOrFail($this->applicationId);

        $isProcessing = in_array((string) ($application->candidate->cv_status ?? 'pending'), ['pending', 'processing'], true);
        if (!$isProcessing) {
            $this->autoRefreshEnabled = false;
        }

        return view('livewire.recruiter.ai-analysis-result', compact('application'));
    }
}
