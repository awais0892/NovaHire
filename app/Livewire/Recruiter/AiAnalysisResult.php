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
    public bool $analysisModalOpen = false;
    public string $analysisActionLabel = 'analysis';
    public bool $isProcessingState = false;
    public bool $hasAnalysisState = false;
    public bool $isFailedState = false;

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
        $this->analysisModalOpen = true;
        $this->analysisActionLabel = 're-analysis';

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
        $this->analysisModalOpen = true;
        $this->analysisActionLabel = 'analysis';

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

    public function openAnalysisModal(): void
    {
        $this->analysisModalOpen = true;
    }

    public function closeAnalysisModal(): void
    {
        $this->analysisModalOpen = false;
    }

    public function render()
    {
        $application = Application::myCompany()
            ->with(['candidate', 'jobListing.skills', 'aiAnalysis'])
            ->findOrFail($this->applicationId);

        $analysis = $application->aiAnalysis;
        $cvStatus = (string) ($application->candidate->cv_status ?? 'pending');
        $isProcessing = in_array($cvStatus, ['pending', 'processing'], true);
        $isFailed = $cvStatus === 'failed';
        $isProcessed = $cvStatus === 'processed';
        $reasoningText = trim((string) ($analysis->reasoning ?? ''));
        $hasReasoning = filled($reasoningText)
            && !in_array($reasoningText, ['""', "''", '[]', '{}', 'null', '-'], true);
        $hasAnalysis = !$isProcessing && (bool) (
            $analysis
            && (
                $hasReasoning
                || (int) ($analysis->match_score ?? 0) > 0
                || !empty($analysis->matched_skills ?? [])
                || !empty($analysis->missing_skills ?? [])
                || $isProcessed
            )
        );

        $this->isProcessingState = $isProcessing;
        $this->isFailedState = $isFailed;
        $this->hasAnalysisState = $hasAnalysis;

        if (!$isProcessing) {
            $this->autoRefreshEnabled = false;
        }

        return view('livewire.recruiter.ai-analysis-result', compact('application'));
    }
}
