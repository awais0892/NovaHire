<?php

namespace App\Livewire\Jobs;

use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Support\Collection;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class JobShow extends Component
{
    use AuthorizesRequests;

    private const PIPELINE_STAGES = [
        'sourced_applied' => [
            'label' => 'Sourced / Applied',
            'statuses' => ['applied', 'shortlisted'],
            'accent_class' => 'border-brand-500',
            'empty_message' => 'No newly sourced or applied candidates yet.',
        ],
        'screening' => [
            'label' => 'Screening',
            'statuses' => ['screening'],
            'accent_class' => 'border-orange-400',
            'empty_message' => 'No candidates in screening right now.',
        ],
        'interviewing' => [
            'label' => 'Interviewing',
            'statuses' => ['interview'],
            'accent_class' => 'border-blue-500',
            'empty_message' => 'No interviews scheduled yet.',
        ],
        'offered' => [
            'label' => 'Offered',
            'statuses' => ['offer'],
            'accent_class' => 'border-purple-500',
            'empty_message' => 'No active offers yet.',
        ],
        'hired' => [
            'label' => 'Hired',
            'statuses' => ['hired'],
            'accent_class' => 'border-green-500',
            'empty_message' => 'No hires on this role yet.',
        ],
        'rejected' => [
            'label' => 'Rejected / Withdrawn',
            'statuses' => ['rejected'],
            'accent_class' => 'border-red-400',
            'empty_message' => 'No rejected candidates yet.',
        ],
    ];

    public JobListing $job;

    public function mount(JobListing $job): void
    {
        $this->job = $job;
        $this->authorize('view', $this->job);
    }

    public function render()
    {
        $applications = Application::query()
            ->where('job_listing_id', $this->job->id)
            ->where('company_id', auth()->user()->company_id)
            ->with([
                'candidate' => fn($query) => $query
                    ->withTrashed()
                    ->select(['id', 'user_id', 'name', 'email', 'cv_status', 'deleted_at']),
                'upcomingInterview' => fn($query) => $query->select([
                    'interviews.id',
                    'interviews.application_id',
                    'interviews.starts_at',
                    'interviews.timezone',
                    'interviews.mode',
                    'interviews.status',
                    'interviews.meeting_link',
                    'interviews.location',
                ]),
            ])
            ->latest('created_at')
            ->get();

        $pipelineColumns = $this->buildPipelineColumns($applications);

        return view('livewire.jobs.job-show', [
            'job' => $this->job->loadMissing('creator:id,name'),
            'pipelineColumns' => $pipelineColumns,
            'pipelineTotal' => $applications->count(),
        ]);
    }

    private function buildPipelineColumns(Collection $applications): Collection
    {
        return collect(self::PIPELINE_STAGES)
            ->map(function (array $stageConfig, string $stageKey) use ($applications) {
                $stageApplications = $applications
                    ->filter(fn(Application $application) => in_array($application->status, $stageConfig['statuses'], true))
                    ->values();

                return [
                    'key' => $stageKey,
                    'label' => $stageConfig['label'],
                    'accent_class' => $stageConfig['accent_class'],
                    'empty_message' => $stageConfig['empty_message'],
                    'count' => $stageApplications->count(),
                    'applications' => $stageApplications,
                ];
            })
            ->values();
    }
}
