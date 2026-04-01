<?php

namespace App\Http\Controllers;

class RecruiterController extends Controller
{
    public function dashboard()
    {
        $compId = auth()->user()->company_id;

        $metrics = [
            'open_jobs' => \App\Models\JobListing::where('company_id', $compId)->where('status', 'active')->count(),
            'total_candidates' => \App\Models\Candidate::where('company_id', $compId)->count(),
            'ai_matches' => \App\Models\Application::where('company_id', $compId)->where('ai_score', '>=', 80)->count(),
            'interviews' => \App\Models\Application::where('company_id', $compId)->where('status', 'interview')->count()
        ];

        $recent_applications = \App\Models\Application::with(['candidate', 'jobListing'])
            ->where('company_id', $compId)
            ->latest()
            ->take(5)
            ->get();

        // Data for Apps Over Time Chart
        $apps_over_time = \App\Models\Application::where('company_id', $compId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Data for Score Distribution
        $score_dist = [
            '90+' => \App\Models\Application::where('company_id', $compId)->where('ai_score', '>=', 90)->count(),
            '70-89' => \App\Models\Application::where('company_id', $compId)->whereBetween('ai_score', [70, 89])->count(),
            '50-69' => \App\Models\Application::where('company_id', $compId)->whereBetween('ai_score', [50, 69])->count(),
            '<50' => \App\Models\Application::where('company_id', $compId)->where('ai_score', '<', 50)->count(),
        ];

        return view('pages.dashboard.recruiter', [
            'title' => 'Recruiter Dashboard',
            'metrics' => $metrics,
            'recent_applications' => $recent_applications,
            'chart_data' => [
                'labels' => $apps_over_time->pluck('date'),
                'data' => $apps_over_time->pluck('count'),
            ],
            'score_dist' => $score_dist
        ]);
    }
}
