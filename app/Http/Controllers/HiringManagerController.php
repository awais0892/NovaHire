<?php

namespace App\Http\Controllers;

class HiringManagerController extends Controller
{
    public function dashboard()
    {
        $compId = auth()->user()->company_id;

        $metrics = [
            'open_reqs' => \App\Models\JobListing::where('company_id', $compId)->where('status', 'active')->count(),
            'candidates_to_review' => \App\Models\Application::where('company_id', $compId)->where('status', 'screening')->count(),
            'upcoming_interviews' => \App\Models\Application::where('company_id', $compId)->where('status', 'interview')->count(),
            'recent_hires' => \App\Models\Application::where('company_id', $compId)->where('status', 'hired')->where('updated_at', '>=', now()->subDays(30))->count()
        ];

        $shortlisted_candidates = \App\Models\Application::with(['candidate', 'jobListing'])
            ->where('company_id', $compId)
            ->whereIn('status', ['shortlisted', 'interview'])
            ->latest()
            ->take(5)
            ->get();

        $requisitions = \App\Models\JobListing::where('company_id', $compId)
            ->withCount('applications')
            ->latest()
            ->take(4)
            ->get();

        $interviews_today = \App\Models\Application::with(['candidate', 'jobListing'])
            ->where('company_id', $compId)
            ->where('status', 'interview')
            ->whereDate('updated_at', now())
            ->get();

        return view('pages.dashboard.hiring-manager', [
            'title' => 'Hiring Manager Dashboard',
            'metrics' => $metrics,
            'shortlisted_candidates' => $shortlisted_candidates,
            'requisitions' => $requisitions,
            'interviews_today' => $interviews_today
        ]);
    }

    public function shortlisted()
    {
        $compId = auth()->user()->company_id;

        $shortlistedCandidates = \App\Models\Application::with(['candidate', 'jobListing'])
            ->where('company_id', $compId)
            ->whereIn('status', ['shortlisted', 'interview', 'offer'])
            ->latest()
            ->paginate(15);

        return view('pages.manager.shortlisted', [
            'title' => 'Shortlisted Candidates',
            'shortlistedCandidates' => $shortlistedCandidates,
        ]);
    }
}
