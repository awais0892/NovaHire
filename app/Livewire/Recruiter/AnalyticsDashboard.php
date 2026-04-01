<?php

namespace App\Livewire\Recruiter;

use App\Models\Application;
use App\Models\AiAnalysis;
use App\Models\JobListing;
use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public string $dateRange = '30'; // days
    public string $jobFilter = 'all';
    public string $activeTab = 'overview';

    public function updatedDateRange(): void
    {
        $this->dispatch('charts-refresh');
    }
    public function updatedJobFilter(): void
    {
        $this->dispatch('charts-refresh');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->dispatch('charts-refresh');
    }

    // ── KPI Cards ────────────────────────────────────────────
    public function getKpis(): array
    {
        $from = now()->subDays((int) $this->dateRange);
        $compId = auth()->user()->company_id;

        $totalJobs = JobListing::where('company_id', $compId)->count();
        $activeJobs = JobListing::where('company_id', $compId)->where('status', 'active')->count();
        $totalApps = Application::where('company_id', $compId)->where('created_at', '>=', $from)->count();
        $totalHired = Application::where('company_id', $compId)->where('status', 'hired')->where('updated_at', '>=', $from)->count();
        $totalRejected = Application::where('company_id', $compId)->where('status', 'rejected')->where('updated_at', '>=', $from)->count();
        $totalShortlisted = Application::where('company_id', $compId)->where('status', 'shortlisted')->where('updated_at', '>=', $from)->count();
        $avgScore = AiAnalysis::whereHas('application', fn($q) => $q->where('company_id', $compId))->avg('match_score');
        $totalAiRuns = AiAnalysis::whereHas('application', fn($q) => $q->where('company_id', $compId))->where('created_at', '>=', $from)->count();
        $totalTokens = AiAnalysis::whereHas('application', fn($q) => $q->where('company_id', $compId))->sum('tokens_used');

        // Time to hire — avg days from applied to hired
        $timeToHire = Application::where('company_id', $compId)
            ->where('status', 'hired')
            ->whereNotNull('status_changed_at')
            ->selectRaw('AVG(DATEDIFF(status_changed_at, created_at)) as avg_days')
            ->value('avg_days');

        // Conversion rate
        $conversionRate = $totalApps > 0
            ? round(($totalHired / $totalApps) * 100, 1)
            : 0;

        return compact(
            'totalJobs',
            'activeJobs',
            'totalApps',
            'totalHired',
            'totalRejected',
            'totalShortlisted',
            'avgScore',
            'totalAiRuns',
            'totalTokens',
            'timeToHire',
            'conversionRate'
        );
    }

    // ── Hiring Funnel ────────────────────────────────────────
    public function getFunnelData(): array
    {
        $compId = auth()->user()->company_id;
        $from = now()->subDays((int) $this->dateRange);

        $stages = ['applied', 'screening', 'shortlisted', 'interview', 'offer', 'hired'];
        $data = [];

        foreach ($stages as $stage) {
            $query = Application::where('company_id', $compId)
                ->where('created_at', '>=', $from);

            if ($this->jobFilter !== 'all') {
                $query->where('job_listing_id', $this->jobFilter);
            }

            $data[$stage] = $query->where(function ($q) use ($stage, $stages) {
                // Count candidates who reached this stage or beyond
                $q->whereIn('status', array_slice($stages, array_search($stage, $stages)));
            })->count();
        }

        return $data;
    }

    // ── Applications Over Time ───────────────────────────────
    public function getApplicationsOverTime(): array
    {
        $compId = auth()->user()->company_id;
        $from = now()->subDays((int) $this->dateRange);

        $results = Application::where('company_id', $compId)
            ->where('created_at', '>=', $from)
            ->when(
                $this->jobFilter !== 'all',
                fn($q) =>
                $q->where('job_listing_id', $this->jobFilter)
            )
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $results->pluck('date')->toArray(),
            'data' => $results->pluck('count')->toArray(),
        ];
    }

    // ── AI Score Distribution ────────────────────────────────
    public function getScoreDistribution(): array
    {
        $compId = auth()->user()->company_id;

        $buckets = [
            '90-100' => [90, 100],
            '80-89' => [80, 89],
            '70-79' => [70, 79],
            '60-69' => [60, 69],
            '50-59' => [50, 59],
            '0-49' => [0, 49],
        ];

        $data = [];
        foreach ($buckets as $label => [$min, $max]) {
            $data[$label] = Application::where('company_id', $compId)
                ->whereBetween('ai_score', [$min, $max])
                ->count();
        }

        return $data;
    }

    // ── Top Skills In Market ─────────────────────────────────
    public function getTopSkills(): array
    {
        $compId = auth()->user()->company_id;

        // From matched skills JSON arrays
        $analyses = AiAnalysis::whereHas(
            'application',
            fn($q) =>
            $q->where('company_id', $compId)
        )->get();

        $skillCounts = [];
        foreach ($analyses as $analysis) {
            foreach ($analysis->matched_skills ?? [] as $skill) {
                $key = strtolower(trim($skill));
                $skillCounts[$key] = ($skillCounts[$key] ?? 0) + 1;
            }
        }

        arsort($skillCounts);
        return array_slice($skillCounts, 0, 10, true);
    }

    // ── Top Missing Skills (Gap Analysis) ───────────────────
    public function getTopMissingSkills(): array
    {
        $compId = auth()->user()->company_id;

        $analyses = AiAnalysis::whereHas(
            'application',
            fn($q) =>
            $q->where('company_id', $compId)
        )->get();

        $skillCounts = [];
        foreach ($analyses as $analysis) {
            foreach ($analysis->missing_skills ?? [] as $skill) {
                $key = strtolower(trim($skill));
                $skillCounts[$key] = ($skillCounts[$key] ?? 0) + 1;
            }
        }

        arsort($skillCounts);
        return array_slice($skillCounts, 0, 10, true);
    }

    // ── Applications By Job ──────────────────────────────────
    public function getApplicationsByJob(): array
    {
        $compId = auth()->user()->company_id;
        $from = now()->subDays((int) $this->dateRange);

        return JobListing::where('company_id', $compId)
            ->withCount(['applications' => fn($q) => $q->where('created_at', '>=', $from)])
            ->orderByDesc('applications_count')
            ->limit(8)
            ->get()
            ->map(fn($j) => [
                'title' => str($j->title)->limit(25),
                'count' => $j->applications_count,
            ])
            ->toArray();
    }

    // ── Recommendation Breakdown ─────────────────────────────
    public function getRecommendationBreakdown(): array
    {
        $compId = auth()->user()->company_id;

        return AiAnalysis::whereHas(
            'application',
            fn($q) =>
            $q->where('company_id', $compId)
        )
            ->selectRaw('recommendation, COUNT(*) as count')
            ->groupBy('recommendation')
            ->pluck('count', 'recommendation')
            ->toArray();
    }

    // ── Time To Hire By Job ──────────────────────────────────
    public function getTimeToHireByJob(): array
    {
        $compId = auth()->user()->company_id;

        return Application::where('company_id', $compId)
            ->where('status', 'hired')
            ->whereNotNull('status_changed_at')
            ->with('jobListing:id,title')
            ->selectRaw('
                job_listing_id,
                AVG(DATEDIFF(status_changed_at, created_at)) as avg_days,
                COUNT(*) as hired_count
            ')
            ->groupBy('job_listing_id')
            ->orderBy('avg_days')
            ->get()
            ->map(fn($a) => [
                'title' => str($a->jobListing?->title ?? 'Unknown')->limit(20),
                'avg_days' => round($a->avg_days, 1),
                'hired_count' => $a->hired_count,
            ])
            ->toArray();
    }

    // ── Jobs Dropdown ────────────────────────────────────────
    public function getJobs()
    {
        return JobListing::where('company_id', auth()->user()->company_id)
            ->select('id', 'title')
            ->orderByDesc('created_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.recruiter.analytics-dashboard', [
            'kpis' => $this->getKpis(),
            'funnelData' => $this->getFunnelData(),
            'applicationsOverTime' => $this->getApplicationsOverTime(),
            'scoreDistribution' => $this->getScoreDistribution(),
            'topSkills' => $this->getTopSkills(),
            'topMissingSkills' => $this->getTopMissingSkills(),
            'applicationsByJob' => $this->getApplicationsByJob(),
            'recommendationData' => $this->getRecommendationBreakdown(),
            'timeToHireData' => $this->getTimeToHireByJob(),
            'jobs' => $this->getJobs(),
        ]);
    }
}
