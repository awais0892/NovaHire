<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\AuditLog;
use App\Models\Interview;
use App\Models\InterviewSlot;
use App\Models\JobListing;
use App\Models\SavedFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Support\AuditLogger;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\InterviewCancelled;
use App\Notifications\InterviewScheduled;
use App\Services\CandidateDecisionEmailService;
use App\Services\GoogleCalendarService;
use App\Services\InterviewSlotEngineService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruiterApplicationController extends Controller
{
    private static ?bool $interviewsTableExists = null;

    private const STATUSES = [
        'applied',
        'screening',
        'shortlisted',
        'interview',
        'offer',
        'hired',
        'rejected',
    ];

    public function index(Request $request)
    {
        $this->ensureCanViewApplications();
        $interviewsEnabled = $this->interviewsEnabled();
        $companyId = auth()->user()->company_id;
        $filters = $this->resolveFilters($request);
        $canManageApplications = $this->userCanManageApplications();
        $canExportApplications = $this->userCanViewApplications();

        $applications = $this->buildApplicationsQuery($filters, $interviewsEnabled)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $metricsBase = Application::myCompany();
        $metrics = [
            'total' => (clone $metricsBase)->count(),
            'screening' => (clone $metricsBase)->where('status', 'screening')->count(),
            'interview' => (clone $metricsBase)->where('status', 'interview')->count(),
            'hired' => (clone $metricsBase)->where('status', 'hired')->count(),
        ];

        $jobs = JobListing::query()
            ->where('company_id', $companyId)
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('pages.recruiter.applications.index', [
            'title' => 'Applications Pipeline',
            'applications' => $applications,
            'interviewsEnabled' => $interviewsEnabled,
            'jobs' => $jobs,
            'metrics' => $metrics,
            'statuses' => self::STATUSES,
            'savedFilters' => SavedFilter::query()
                ->where('user_id', auth()->id())
                ->where('page_key', 'recruiter_applications')
                ->latest()
                ->get(),
            'filters' => $filters,
            'canManageApplications' => $canManageApplications,
            'canExportApplications' => $canExportApplications,
            'auditEvents' => AuditLog::query()
                ->with('user:id,name')
                ->where('company_id', $companyId)
                ->whereIn('action', [
                    'recruiter.application.status.updated',
                    'recruiter.application.note.overridden',
                    'recruiter.applications.export.csv',
                    'recruiter.applications.export.pdf',
                ])
                ->latest('id')
                ->limit(12)
                ->get(),
        ]);
    }

    public function saveFilter(Request $request)
    {
        $this->ensureCanViewApplications();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'job_id' => ['nullable', 'integer', 'min:1'],
            'min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $filters = [
            'q' => trim((string) ($validated['q'] ?? '')),
            'status' => (string) ($validated['status'] ?? ''),
            'job_id' => (string) ($validated['job_id'] ?? ''),
            'min_score' => (string) ($validated['min_score'] ?? ''),
        ];

        if (collect($filters)->every(fn($value) => $value === '')) {
            return back()->withErrors(['filters' => 'Add at least one filter before saving.']);
        }

        SavedFilter::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'page_key' => 'recruiter_applications',
                'name' => trim((string) $validated['name']),
            ],
            [
                'company_id' => auth()->user()->company_id,
                'filters' => $filters,
            ]
        );

        AuditLogger::log('recruiter.applications.filter.saved', null, [
            'name' => trim((string) $validated['name']),
            'filters' => $filters,
        ]);

        return back()->with('success', 'Filter saved.');
    }

    public function deleteFilter(SavedFilter $filter)
    {
        $this->ensureCanViewApplications();
        abort_unless($filter->user_id === auth()->id() && $filter->page_key === 'recruiter_applications', 404);

        $name = $filter->name;
        $filter->delete();

        AuditLogger::log('recruiter.applications.filter.deleted', null, [
            'name' => $name,
        ]);

        return back()->with('success', 'Saved filter removed.');
    }

    public function updateStatus(Request $request, Application $application)
    {
        abort_unless($application->company_id === (auth()->user()->company_id ?? null), 404);
        $this->ensureCanManageApplications();
        $application->loadMissing(['candidate.user', 'jobListing.company']);
        $fromStatus = (string) $application->status;

        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        if ($fromStatus === $validated['status']) {
            return back()->with('success', 'Application status is already up to date.');
        }

        $application->update([
            'status' => $validated['status'],
            'status_changed_at' => now(),
        ]);
        AuditLogger::log('recruiter.application.status.updated', $application, [
            'from' => $fromStatus,
            'to' => $validated['status'],
            'bulk' => false,
        ]);

        if ($application->candidate?->user) {
            $this->notifyUserSafely(
                $application->candidate->user,
                new ApplicationStatusChanged($application),
                'recruiter.application.status.updated',
                $application->id
            );
        }

        return back()->with('success', 'Application status updated.');
    }

    public function bulkUpdateStatus(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $this->ensureCanManageApplications();

        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['integer', 'exists:applications,id'],
        ]);

        $applicationIds = collect($validated['application_ids'])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $applications = Application::whereIn('id', $applicationIds)
            ->where('company_id', $companyId)
            ->get();

        if ($applications->isEmpty()) {
            return back()->withErrors(['bulk' => 'No valid applications were selected.']);
        }

        $statusBefore = $applications->mapWithKeys(function (Application $application) {
            return [$application->id => (string) $application->status];
        });

        Application::whereIn('id', $applications->pluck('id'))
            ->update([
                'status' => $validated['status'],
                'status_changed_at' => now(),
            ]);

        $applications->loadMissing(['candidate.user', 'jobListing']);
        foreach ($applications as $application) {
            $fromStatus = (string) ($statusBefore[$application->id] ?? '');
            $application->status = $validated['status'];
            AuditLogger::log('recruiter.application.status.updated', $application, [
                'from' => $fromStatus,
                'to' => $validated['status'],
                'bulk' => true,
            ]);

            if ($application->candidate?->user) {
                $this->notifyUserSafely(
                    $application->candidate->user,
                    new ApplicationStatusChanged($application),
                    'recruiter.applications.bulk.status.updated',
                    $application->id
                );
            }
        }

        AuditLogger::log('recruiter.applications.bulk.status.updated', null, [
            'application_ids' => $applications->pluck('id')->all(),
            'to' => $validated['status'],
        ]);

        return back()->with('success', "Updated {$applications->count()} applications to {$validated['status']}.");
    }

    public function scheduleInterview(
        Request $request,
        Application $application,
        InterviewSlotEngineService $slotEngine,
        GoogleCalendarService $googleCalendarService
    )
    {
        $this->ensureCanManageApplications();

        if (!$this->interviewsEnabled()) {
            return back()->withErrors(['interview' => 'Interview module is not ready. Run php artisan migrate.']);
        }

        abort_unless($application->company_id === (auth()->user()->company_id ?? null), 404);
        $application->loadMissing(['candidate.user', 'jobListing']);

        $validated = $request->validate([
            'slot_id' => ['nullable', 'integer'],
            'starts_at' => ['nullable', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'timezone' => ['nullable', 'timezone'],
            'mode' => ['nullable', Rule::in(['video', 'onsite', 'phone'])],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $slotId = isset($validated['slot_id']) ? (int) $validated['slot_id'] : null;

        if (!$slotId && blank($validated['starts_at'] ?? null)) {
            return back()->withErrors(['starts_at' => 'Start date/time is required when no slot is selected.']);
        }

        if (!$slotId && blank($validated['timezone'] ?? null)) {
            return back()->withErrors(['timezone' => 'Timezone is required when no slot is selected.']);
        }

        if (!$slotId && blank($validated['mode'] ?? null)) {
            return back()->withErrors(['mode' => 'Interview mode is required when no slot is selected.']);
        }

        if (($validated['mode'] ?? null) === 'video' && blank($validated['meeting_link'] ?? null)) {
            return back()->withErrors(['meeting_link' => 'Meeting link is required for video interviews.']);
        }

        if (($validated['mode'] ?? null) === 'onsite' && blank($validated['location'] ?? null)) {
            return back()->withErrors(['location' => 'Location is required for onsite interviews.']);
        }

        if ($slotId) {
            $slot = InterviewSlot::query()
                ->where('company_id', $application->company_id)
                ->where('id', $slotId)
                ->first();

            if (!$slot) {
                return back()->withErrors(['slot_id' => 'Selected slot was not found for your company.']);
            }

            $overrides = [];
            foreach (['mode', 'meeting_link', 'location', 'notes'] as $field) {
                if (array_key_exists($field, $validated) && filled($validated[$field])) {
                    $overrides[$field] = $validated[$field];
                }
            }

            $interview = $slotEngine->bookSlotForApplication(
                application: $application,
                slotId: $slotId,
                scheduledByUserId: auth()->id(),
                overrides: $overrides
            );
        } else {
            $startsAt = \Illuminate\Support\Carbon::parse($validated['starts_at'], $validated['timezone']);
            $endsAt = filled($validated['ends_at'] ?? null)
                ? \Illuminate\Support\Carbon::parse($validated['ends_at'], $validated['timezone'])
                : $startsAt->copy()->addHour();

            $existingScheduled = Interview::query()
                ->where('application_id', $application->id)
                ->where('status', 'scheduled')
                ->get();

            if ($existingScheduled->isNotEmpty()) {
                Interview::query()
                    ->whereIn('id', $existingScheduled->pluck('id'))
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_reason' => 'Replaced by new schedule',
                        'updated_at' => now(),
                    ]);

                $slotIdsToRelease = $existingScheduled->pluck('interview_slot_id')->filter()->unique()->values();
                if ($slotIdsToRelease->isNotEmpty()) {
                    InterviewSlot::query()
                        ->where('company_id', $application->company_id)
                        ->whereIn('id', $slotIdsToRelease)
                        ->update([
                            'booked_application_id' => null,
                            'is_available' => true,
                            'updated_at' => now(),
                        ]);
                }
            }

            $interview = Interview::create([
                'company_id' => $application->company_id,
                'application_id' => $application->id,
                'scheduled_by' => auth()->id(),
                'starts_at' => $startsAt->copy()->utc(),
                'ends_at' => $endsAt->copy()->utc(),
                'timezone' => $validated['timezone'],
                'mode' => $validated['mode'],
                'meeting_link' => $validated['meeting_link'] ?? null,
                'location' => $validated['location'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'scheduled',
                'candidate_response' => null,
                'candidate_responded_at' => null,
            ]);

            if ($application->status !== 'interview') {
                $application->update([
                    'status' => 'interview',
                    'status_changed_at' => now(),
                ]);
            }
        }

        AuditLogger::log('recruiter.interview.scheduled', $application, [
            'interview_id' => $interview->id,
            'starts_at' => $interview->starts_at?->toIso8601String(),
            'timezone' => $interview->timezone,
            'mode' => $interview->mode,
        ]);

        $this->syncInterviewWithGoogleCalendar($interview, $googleCalendarService);
        $interview->refresh();

        if ($application->candidate?->user) {
            $this->notifyUserSafely(
                $application->candidate->user,
                new InterviewScheduled($interview->loadMissing('application.jobListing')),
                'recruiter.interview.scheduled',
                $application->id
            );
        }

        return back()->with('success', 'Interview scheduled and candidate notified.');
    }

    public function cancelInterview(
        Request $request,
        Application $application,
        Interview $interview,
        InterviewSlotEngineService $slotEngine,
        GoogleCalendarService $googleCalendarService
    )
    {
        $this->ensureCanManageApplications();

        if (!$this->interviewsEnabled()) {
            return back()->withErrors(['interview' => 'Interview module is not ready. Run php artisan migrate.']);
        }

        abort_unless($application->company_id === (auth()->user()->company_id ?? null), 404);
        abort_unless($interview->application_id === $application->id, 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($interview->status !== 'scheduled') {
            return back()->withErrors(['interview' => 'Only scheduled interviews can be cancelled.']);
        }

        $interview->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_reason' => $validated['reason'] ?? 'Cancelled by recruiter',
        ]);
        $slotEngine->releaseInterviewSlot($interview);

        AuditLogger::log('recruiter.interview.cancelled', $application, [
            'interview_id' => $interview->id,
            'reason' => $interview->cancelled_reason,
        ]);

        $application->loadMissing(['candidate.user']);
        if ($application->candidate?->user) {
            $this->notifyUserSafely(
                $application->candidate->user,
                new InterviewCancelled($interview->loadMissing('application.jobListing')),
                'recruiter.interview.cancelled',
                $application->id
            );
        }

        $this->cancelInterviewOnGoogleCalendar($interview, $googleCalendarService);

        return back()->with('success', 'Interview cancelled and candidate notified.');
    }

    public function availableSlots(
        Request $request,
        Application $application,
        InterviewSlotEngineService $slotEngine
    ): JsonResponse {
        $this->ensureCanManageApplications();

        if (!$this->interviewsEnabled()) {
            return response()->json([
                'slots' => [],
                'message' => 'Interview module is not ready. Run php artisan migrate.',
            ], 503);
        }

        abort_unless($application->company_id === (auth()->user()->company_id ?? null), 404);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $from = !empty($validated['from'] ?? null)
            ? \Illuminate\Support\Carbon::parse($validated['from'], config('recruitment.uk_timezone', 'Europe/London'))
            : null;

        $slots = $slotEngine->listAvailableSlots(
            companyId: (int) $application->company_id,
            from: $from,
            days: isset($validated['days']) ? (int) $validated['days'] : null
        );

        return response()->json([
            'count' => $slots->count(),
            'slots' => $slots->all(),
        ]);
    }

    public function analysisStatus(Application $application): JsonResponse
    {
        abort_unless($application->company_id === (auth()->user()->company_id ?? null), 404);
        $this->ensureCanViewApplications();

        $application->loadMissing(['candidate', 'aiAnalysis']);
        $analysis = $application->aiAnalysis;
        $reasoning = trim((string) ($analysis->reasoning ?? ''));
        $hasReasoning = filled($reasoning)
            && !in_array($reasoning, ['""', "''", '[]', '{}', 'null', '-'], true);

        $hasAnalysis = (bool) (
            $analysis
            && (
                $hasReasoning
                || ($analysis->match_score ?? 0) > 0
                || !empty($analysis->matched_skills ?? [])
                || !empty($analysis->missing_skills ?? [])
            )
        );

        return response()->json([
            'application_id' => $application->id,
            'cv_status' => $application->candidate->cv_status ?? 'pending',
            'has_analysis' => $hasAnalysis,
            'score' => $analysis->match_score ?? null,
            'updated_at' => optional($analysis?->updated_at)->toIso8601String(),
        ]);
    }

    public function downloadAnalysisReport(Application $application)
    {
        abort_unless($application->company_id === (auth()->user()->company_id ?? null), 404);
        $this->ensureCanViewApplications();

        $application->loadMissing(['candidate', 'jobListing', 'aiAnalysis']);
        abort_if(!$application->aiAnalysis, 404, 'Analysis report is not available yet.');

        $pdf = Pdf::loadView('pdf.ai-report', compact('application'))
            ->setPaper('a4', 'portrait');

        $base = trim((string) ($application->candidate->name ?? 'candidate'));
        $safeName = Str::slug($base) ?: 'candidate';

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            "ai-report-{$safeName}.pdf"
        );
    }

    public function details(Application $application): JsonResponse
    {
        abort_unless($application->company_id === (auth()->user()->company_id ?? null), 404);
        $this->ensureCanViewApplications();

        $application->loadMissing([
            'candidate',
            'jobListing.company',
            'aiAnalysis',
            'upcomingInterview',
        ]);

        $notes = $application->notes()
            ->with('author:id,name')
            ->latest('id')
            ->limit(8)
            ->get();

        $emailLogs = $application->emailLogs()
            ->latest('id')
            ->limit(8)
            ->get();

        $latestInterview = $this->interviewsEnabled()
            ? $application->interviews()->latest('starts_at')->latest('id')->first()
            : null;

        $latestAiNote = $application->notes()
            ->where('source', 'ai')
            ->latest('id')
            ->value('content');

        return response()->json([
            'application' => [
                'id' => $application->id,
                'status' => (string) $application->status,
                'ai_score' => $application->ai_score,
                'applied_at' => optional($application->created_at)->toIso8601String(),
                'status_changed_at' => optional($application->status_changed_at)->toIso8601String(),
                'cover_letter' => (string) ($application->cover_letter ?? ''),
                'recruiter_notes' => (string) ($application->recruiter_notes ?? ''),
            ],
            'candidate' => [
                'id' => $application->candidate?->id,
                'name' => (string) ($application->candidate?->name ?? 'Unknown'),
                'email' => (string) ($application->candidate?->email ?? ''),
                'phone' => (string) ($application->candidate?->phone ?? ''),
                'location' => (string) ($application->candidate?->location ?? ''),
                'cv_status' => (string) ($application->candidate?->cv_status ?? 'pending'),
            ],
            'job' => [
                'id' => $application->jobListing?->id,
                'title' => (string) ($application->jobListing?->title ?? 'Role'),
                'department' => (string) ($application->jobListing?->department ?? ''),
                'location' => (string) ($application->jobListing?->location_label ?? $application->jobListing?->location ?? ''),
                'job_type' => (string) ($application->jobListing?->job_type ?? ''),
                'company' => (string) ($application->jobListing?->company?->name ?? ''),
            ],
            'ai' => [
                'match_score' => $application->aiAnalysis?->match_score,
                'reasoning' => (string) ($application->aiAnalysis?->reasoning ?? ''),
                'strengths' => (string) ($application->aiAnalysis?->strengths ?? ''),
                'weaknesses' => (string) ($application->aiAnalysis?->weaknesses ?? ''),
                'latest_note' => (string) ($latestAiNote ?? ''),
            ],
            'interview' => $latestInterview ? [
                'status' => (string) $latestInterview->status,
                'starts_at' => optional($latestInterview->starts_at)->toIso8601String(),
                'ends_at' => optional($latestInterview->ends_at)->toIso8601String(),
                'timezone' => (string) ($latestInterview->timezone ?? ''),
                'mode' => (string) ($latestInterview->mode ?? ''),
                'meeting_link' => (string) ($latestInterview->meeting_link ?? ''),
                'location' => (string) ($latestInterview->location ?? ''),
                'notes' => (string) ($latestInterview->notes ?? ''),
            ] : null,
            'notes' => $notes->map(function (ApplicationNote $note) {
                return [
                    'id' => $note->id,
                    'note_type' => (string) $note->note_type,
                    'source' => (string) $note->source,
                    'subject' => (string) ($note->subject ?? ''),
                    'content' => (string) ($note->content ?? ''),
                    'author' => (string) ($note->author?->name ?? 'System'),
                    'created_at' => optional($note->created_at)->toIso8601String(),
                ];
            })->values()->all(),
            'emails' => $emailLogs->map(function ($emailLog) {
                return [
                    'id' => $emailLog->id,
                    'template' => (string) ($emailLog->template ?? ''),
                    'subject' => (string) ($emailLog->subject ?? ''),
                    'status' => (string) ($emailLog->status ?? ''),
                    'recipient_email' => (string) ($emailLog->recipient_email ?? ''),
                    'sent_at' => optional($emailLog->sent_at)->toIso8601String(),
                    'failed_at' => optional($emailLog->failed_at)->toIso8601String(),
                    'created_at' => optional($emailLog->created_at)->toIso8601String(),
                    'error_message' => (string) ($emailLog->error_message ?? ''),
                ];
            })->values()->all(),
            'can_manage' => $this->userCanManageApplications(),
        ]);
    }

    public function overrideNote(
        Request $request,
        Application $application,
        CandidateDecisionEmailService $decisionEmailService
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        abort_unless($application->company_id === (auth()->user()->company_id ?? null), 404);
        $this->ensureCanManageApplications();

        $validated = $request->validate([
            'note_content' => ['required', 'string', 'max:5000'],
            'subject' => ['nullable', 'string', 'max:160'],
            'decision' => ['nullable', Rule::in(['rejected', 'shortlisted', 'interview'])],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $noteContent = trim((string) $validated['note_content']);
        $subject = trim((string) ($validated['subject'] ?? ''));
        $sendEmail = $request->boolean('send_email');

        $decision = (string) ($validated['decision'] ?? '');
        if ($decision === '') {
            $decision = in_array((string) $application->status, ['rejected', 'shortlisted', 'interview'], true)
                ? (string) $application->status
                : 'shortlisted';
        }

        $previousNote = trim((string) ($application->recruiter_notes ?? ''));
        $application->update([
            'recruiter_notes' => $noteContent,
        ]);

        $note = ApplicationNote::query()->create([
            'company_id' => (int) $application->company_id,
            'application_id' => (int) $application->id,
            'candidate_id' => (int) $application->candidate_id,
            'author_user_id' => auth()->id(),
            'note_type' => 'hr_override',
            'source' => 'hr',
            'subject' => $subject !== '' ? $subject : ('HR override: ' . ucfirst($decision)),
            'content' => $noteContent,
            'meta' => [
                'decision' => $decision,
                'send_email' => $sendEmail,
            ],
        ]);

        $emailLog = null;
        if ($sendEmail) {
            $emailLog = $decisionEmailService->queueForDecision(
                application: $application->fresh(['candidate', 'jobListing.company']),
                status: $decision,
                noteOverride: $noteContent,
                force: true
            );
        }

        AuditLogger::log('recruiter.application.note.overridden', $application, [
            'decision' => $decision,
            'note_id' => $note->id,
            'email_log_id' => $emailLog?->id,
            'from_note_excerpt' => Str::limit($previousNote, 200),
            'to_note_excerpt' => Str::limit($noteContent, 200),
            'email_queued' => (bool) $emailLog,
        ]);

        $message = $emailLog
            ? 'Recruiter note updated and email queued successfully.'
            : ($sendEmail ? 'Note saved. Email could not be queued for this application.' : 'Recruiter note updated successfully.');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'note_id' => $note->id,
                'email_log_id' => $emailLog?->id,
            ]);
        }

        return back()->with('success', $message);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->ensureCanViewApplications();
        $filters = $this->resolveFilters($request);
        $applications = $this->buildApplicationsQuery($filters, $this->interviewsEnabled())
            ->latest()
            ->get();

        AuditLogger::log('recruiter.applications.export.csv', null, [
            'filters' => $filters,
            'total' => $applications->count(),
        ]);

        $filename = 'novahire-applications-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ];

        return response()->streamDownload(function () use ($applications) {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fputcsv($stream, [
                'Application ID',
                'Candidate',
                'Email',
                'Job Title',
                'Status',
                'AI Score',
                'Applied At',
                'Interview At',
                'Interview Mode',
            ]);

            foreach ($applications as $application) {
                $interview = $application->upcomingInterview;

                fputcsv($stream, [
                    (string) $application->id,
                    (string) ($application->candidate?->name ?? ''),
                    (string) ($application->candidate?->email ?? ''),
                    (string) ($application->jobListing?->title ?? ''),
                    (string) $application->status,
                    is_null($application->ai_score) ? '' : (string) $application->ai_score,
                    optional($application->created_at)->toDateTimeString(),
                    $interview?->starts_at?->timezone($interview->timezone)->format('Y-m-d H:i') ?? '',
                    (string) ($interview?->mode ?? ''),
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    public function exportPdf(Request $request)
    {
        $this->ensureCanViewApplications();
        $filters = $this->resolveFilters($request);
        $applications = $this->buildApplicationsQuery($filters, $this->interviewsEnabled())
            ->latest()
            ->get();

        AuditLogger::log('recruiter.applications.export.pdf', null, [
            'filters' => $filters,
            'total' => $applications->count(),
        ]);

        $pdf = Pdf::loadView('pdf.applications-export', [
            'applications' => $applications,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $filename = 'novahire-applications-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(
            fn() => print($pdf->output()),
            $filename
        );
    }

    private function interviewsEnabled(): bool
    {
        if (self::$interviewsTableExists !== null) {
            return self::$interviewsTableExists;
        }

        return self::$interviewsTableExists = Schema::hasTable('interviews');
    }

    private function notifyUserSafely($user, $notification, string $context, ?int $applicationId = null): void
    {
        if (!$user) {
            return;
        }

        try {
            $user->notify($notification);
        } catch (\Throwable $exception) {
            logger()->warning('Notification delivery failed. Continuing request.', [
                'context' => $context,
                'user_id' => $user->id,
                'application_id' => $applicationId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function syncInterviewWithGoogleCalendar(
        Interview $interview,
        GoogleCalendarService $googleCalendarService
    ): void {
        try {
            $googleCalendarService->upsertInterviewEvent($interview);
        } catch (\Throwable $exception) {
            logger()->warning('Google Calendar sync failed during interview scheduling.', [
                'interview_id' => $interview->id,
                'application_id' => $interview->application_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function cancelInterviewOnGoogleCalendar(
        Interview $interview,
        GoogleCalendarService $googleCalendarService
    ): void {
        try {
            $googleCalendarService->cancelInterviewEvent($interview);
        } catch (\Throwable $exception) {
            logger()->warning('Google Calendar cancellation sync failed.', [
                'interview_id' => $interview->id,
                'application_id' => $interview->application_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'job_id' => max(0, (int) $request->query('job_id', 0)),
            'min_score' => trim((string) $request->query('min_score', '')),
        ];
    }

    private function buildApplicationsQuery(array $filters, bool $interviewsEnabled)
    {
        $search = (string) ($filters['q'] ?? '');
        $status = (string) ($filters['status'] ?? '');
        $jobId = (int) ($filters['job_id'] ?? 0);
        $minScore = (string) ($filters['min_score'] ?? '');

        $relations = ['candidate', 'jobListing'];
        if ($interviewsEnabled) {
            $relations[] = 'upcomingInterview';
        }

        return Application::query()
            ->with($relations)
            ->myCompany()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('candidate', function ($candidateQuery) use ($search) {
                        $candidateQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('jobListing', function ($jobQuery) use ($search) {
                        $jobQuery->where('title', 'like', "%{$search}%");
                    });
                });
            })
            ->when($status !== '' && in_array($status, self::STATUSES, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($jobId > 0, function ($query) use ($jobId) {
                $query->where('job_listing_id', $jobId);
            })
            ->when($minScore !== '' && is_numeric($minScore), function ($query) use ($minScore) {
                $query->whereNotNull('ai_score')->where('ai_score', '>=', (int) $minScore);
            });
    }

    private function ensureCanManageApplications(): void
    {
        abort_unless($this->userCanManageApplications(), 403);
    }

    private function ensureCanViewApplications(): void
    {
        abort_unless($this->userCanViewApplications(), 403);
    }

    private function userCanManageApplications(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('hr_admin') || (bool) $user->can('applications.manage');
    }

    private function userCanViewApplications(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('hr_admin')
            || $user->hasRole('hr_standard')
            || (bool) $user->can('applications.view')
            || (bool) $user->can('applications.manage');
    }
}
