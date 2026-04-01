<?php

namespace App\Livewire\Candidates;

use App\Jobs\ProcessCvAnalysis;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobListing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class JobApply extends Component
{
    use WithFileUploads;

    public JobListing|int|string|null $job = null;

    public $resume;
    public $cvFile;
    public string $fullName = '';
    public string $email = '';
    public string $phone = '';
    public string $location = '';
    public string $linkedin = '';
    public string $github = '';
    public string $portfolio = '';
    public string $coverLetter = '';
    public bool $isSubmitted = false;
    public bool $alreadyApplied = false;

    public function mount(JobListing|int|string $job): void
    {
        $job = $this->resolveJobListing($job);
        if (!$job) {
            abort(404, 'Job not found.');
        }

        if (auth()->check() && auth()->user()->hasRole('candidate') && request()->routeIs('jobs.apply')) {
            $this->redirectRoute('candidate.apply', ['job' => $job->slug], navigate: true);
            return;
        }

        $this->job = $job->load(['company', 'skills']);

        if (!auth()->check()) {
            return;
        }

        if (!auth()->user()->hasRole('candidate')) {
            abort(403, 'Only candidate accounts can apply for jobs.');
        }

        $user = auth()->user();
        $candidate = $this->resolveCandidate();

        if ($candidate && empty($candidate->user_id)) {
            $candidate->update(['user_id' => $user->id]);
        }

        $this->fullName = (string) ($candidate?->name ?? $user->name ?? '');
        $this->email = (string) ($candidate?->email ?? $user->email ?? '');
        $this->phone = (string) ($candidate?->phone ?? '');
        $this->location = (string) ($candidate?->location ?? '');
        $this->linkedin = (string) ($candidate?->linkedin ?? '');
        $this->github = (string) ($candidate?->github ?? '');
        $this->portfolio = (string) ($candidate?->portfolio ?? '');

        $this->isSubmitted = $candidate
            ? Application::query()
                ->where('candidate_id', $candidate->id)
                ->where('job_listing_id', $job->id)
                ->exists()
            : false;
        $this->alreadyApplied = $this->isSubmitted;
    }

    public function submitApplication(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login', navigate: true);
            return;
        }

        if (!auth()->user()->hasRole('candidate')) {
            abort(403, 'Only candidate accounts can apply for jobs.');
        }

        if (!$this->resume && $this->cvFile) {
            $this->resume = $this->cvFile;
        }

        $user = auth()->user();
        $candidate = $this->resolveCandidate();

        $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('candidates', 'email')->ignore($candidate?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'github' => ['nullable', 'string', 'max:255'],
            'portfolio' => ['nullable', 'string', 'max:255'],
            'resume' => 'required|file|mimes:pdf|max:5120',
            'coverLetter' => 'nullable|string|max:3000',
        ]);

        if (!$candidate) {
            $candidate = Candidate::create([
                'user_id' => $user->id,
                'company_id' => $this->job->company_id,
                'name' => $this->fullName,
                'email' => $this->email,
                'phone' => trim($this->phone) ?: null,
                'location' => trim($this->location) ?: null,
                'linkedin' => trim($this->linkedin) ?: null,
                'github' => trim($this->github) ?: null,
                'portfolio' => trim($this->portfolio) ?: null,
                'cv_status' => 'pending',
            ]);
        } else {
            $candidate->update([
                'user_id' => $candidate->user_id ?: $user->id,
                'company_id' => $candidate->company_id ?: $this->job->company_id,
                'name' => $this->fullName,
                'email' => $this->email,
                'phone' => trim($this->phone) ?: null,
                'location' => trim($this->location) ?: null,
                'linkedin' => trim($this->linkedin) ?: null,
                'github' => trim($this->github) ?: null,
                'portfolio' => trim($this->portfolio) ?: null,
            ]);
        }

        if (
            Application::query()
                ->where('candidate_id', $candidate->id)
                ->where('job_listing_id', $this->job->id)
                ->exists()
        ) {
            $this->isSubmitted = true;
            $this->alreadyApplied = true;
            session()->flash('success', 'You have already applied to this role.');
            $this->redirectRoute('candidate.applications', navigate: true);
            return;
        }

        $path = $this->resume->store('cvs/' . $user->id, 'private');

        $application = Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_id' => $candidate->id,
            'company_id' => $this->job->company_id,
            'status' => 'applied',
            'cover_letter' => $this->coverLetter,
        ]);

        $candidate->update([
            'cv_path' => $path,
            'cv_original_name' => $this->resume->getClientOriginalName(),
            'cv_status' => 'pending',
            'cv_raw_text' => null,
            'extracted_skills' => null,
            'extracted_experience' => null,
            'extracted_education' => null,
        ]);

        try {
            ProcessCvAnalysis::dispatchSmart($application);
        } catch (\Throwable $exception) {
            logger()->warning('CV analysis dispatch failed during job apply.', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);
            $candidate->update(['cv_status' => 'failed']);
            session()->flash('warning', 'Application submitted, but CV analysis could not start automatically. Please contact support.');
        }
        Cache::forget($this->appliedJobsCacheKey($user->id, (string) $user->email));

        $this->isSubmitted = true;
        $this->alreadyApplied = false;

        session()->flash('success', 'Application submitted successfully. Your CV is now being analyzed for this role.');
        $this->redirectRoute('candidate.applications', navigate: true);
    }

    public function submit(): void
    {
        $this->submitApplication();
    }

    private function resolveCandidate(): ?Candidate
    {
        if (!auth()->check()) {
            return null;
        }

        $user = auth()->user();
        $candidate = Candidate::withTrashed()
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if ($candidate?->trashed()) {
            $candidate->restore();
        }

        return $candidate;
    }

    private function appliedJobsCacheKey(int $userId, string $email): string
    {
        return sprintf(
            'candidate:applied-job-ids:%d:%s',
            $userId,
            md5(strtolower($email))
        );
    }

    private function resolveJobListing(JobListing|int|string $job): ?JobListing
    {
        if ($job instanceof JobListing) {
            return $job;
        }

        if (is_numeric($job)) {
            return JobListing::query()->find((int) $job);
        }

        if (is_string($job) && $job !== '') {
            return JobListing::query()->where('slug', $job)->first();
        }

        return null;
    }

    public function render()
    {
        $isCandidateRoute = request()->routeIs('candidate.*');
        $jobShowRoute = $isCandidateRoute ? 'candidate.jobs.show' : 'jobs.show';

        return view('livewire.candidates.job-apply', compact('jobShowRoute'))
            ->layout('layouts.app', [
                'title' => 'Apply for ' . $this->job->title,
            ]);
    }
}
