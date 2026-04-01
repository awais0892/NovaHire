<?php

namespace App\Livewire\Candidate;

use App\Models\JobListing;
use App\Models\Application;
use Livewire\Component;

class JobDetail extends Component
{
    public JobListing $job;
    public bool $hasApplied = false;

    public function mount(JobListing $job): void
    {
        if (auth()->user()?->hasRole('candidate') && request()->routeIs('jobs.show')) {
            $this->redirectRoute('candidate.jobs.show', ['job' => $job->slug], navigate: true);
            return;
        }

        $this->job = $job->load(['company', 'skills']);

        if (auth()->check()) {
            $this->hasApplied = Application::query()
                ->where('job_listing_id', $job->id)
                ->whereHas('candidate', function ($query) {
                    $query
                        ->where('user_id', auth()->id())
                        ->orWhere('email', auth()->user()->email);
                })
                ->exists();
        }
    }

    public function render()
    {
        // Related jobs
        $relatedJobs = JobListing::active()
            ->where('id', '!=', $this->job->id)
            ->where('company_id', $this->job->company_id)
            ->limit(3)
            ->get();

        $isCandidateDashboard = request()->routeIs('candidate.jobs.*');
        $jobsIndexRoute = $isCandidateDashboard ? 'candidate.jobs.index' : 'jobs.index';
        $jobsShowRoute = $isCandidateDashboard ? 'candidate.jobs.show' : 'jobs.show';
        $applyRoute = $isCandidateDashboard ? 'candidate.apply' : 'jobs.apply';

        $view = view(
            'livewire.candidate.job-detail',
            compact('relatedJobs', 'jobsIndexRoute', 'jobsShowRoute', 'applyRoute')
        );

        if ($isCandidateDashboard) {
            return $view->layout('layouts.app', [
                'title' => $this->job->title,
            ]);
        }

        return $view->layout('layouts.public', [
            'title' => $this->job->title,
            'metaDescription' => 'Review role details and apply for this opportunity.',
            'metaImage' => asset('images/og/product.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Jobs', 'url' => route($jobsIndexRoute)],
                ['name' => $this->job->title, 'url' => route($jobsShowRoute, $this->job->slug)],
            ],
        ]);
    }
}
