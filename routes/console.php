<?php

use Illuminate\Foundation\Inspiring;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Mail\HrDailyEmailDigestMail;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\EmailLog;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use App\Jobs\ProcessCvAnalysis;
use App\Services\OpenAiRecruiterNoteService;
use App\Services\ScoreBasedProcessingEngine;
use App\Services\UkBankHolidayService;
use App\Jobs\SendInterviewReminderNotifications;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('interviews:send-reminders', function () {
    SendInterviewReminderNotifications::dispatchSync();
    $this->info('Interview reminders processed.');
})->purpose('Send 24h and 1h interview reminders.');

Artisan::command('recruitment:uk-holidays:sync {--division=} {--year=} {--force}', function (UkBankHolidayService $service) {
    $division = trim((string) ($this->option('division') ?: config('recruitment.uk_bank_holidays.division', 'england-and-wales')));
    $yearOption = $this->option('year');
    $year = is_numeric($yearOption) ? (int) $yearOption : null;
    $force = (bool) $this->option('force');

    $events = $service->events($year, $division, $force);

    $this->info("Synced {$division} holidays. Events loaded: " . count($events));

    if (!empty($events)) {
        $this->table(
            ['Date', 'Title', 'Bunting'],
            collect($events)->take(12)->map(fn(array $event) => [
                $event['date'],
                $event['title'],
                $event['bunting'] ? 'yes' : 'no',
            ])->all()
        );
    }
})->purpose('Sync UK bank holidays into cache for recruitment scheduling.');

Artisan::command('recruitment:uk-timezone:check', function () {
    $timezone = (string) config('recruitment.uk_timezone', 'Europe/London');
    $january = CarbonImmutable::parse('2026-01-15 12:00:00', $timezone);
    $july = CarbonImmutable::parse('2026-07-15 12:00:00', $timezone);

    $this->table(
        ['Sample Date', 'Timezone', 'Offset', 'Abbreviation'],
        [
            [$january->toDateTimeString(), $timezone, $january->format('P'), $january->format('T')],
            [$july->toDateTimeString(), $timezone, $july->format('P'), $july->format('T')],
        ]
    );

    $switchDetected = $january->format('P') !== $july->format('P');
    $this->info($switchDetected
        ? 'BST/GMT switching verified successfully.'
        : 'No offset change detected. Check timezone configuration.');
})->purpose('Verify UK timezone BST/GMT switching.');

Artisan::command('recruitment:openai:test-note {--name=Candidate} {--role=Software Engineer} {--score=74} {--decision=shortlist}', function (OpenAiRecruiterNoteService $service) {
    if (!$service->isConfigured()) {
        $this->error('OPENAI_API_KEY is not configured.');
        return self::FAILURE;
    }

    $result = $service->generateRecruiterNote([
        'candidate_name' => (string) $this->option('name'),
        'role' => (string) $this->option('role'),
        'score' => (int) $this->option('score'),
        'decision' => (string) $this->option('decision'),
        'tone' => 'professional and constructive',
    ]);

    $this->info('OpenAI recruiter note generated successfully.');
    $this->line('Model: ' . (string) ($result['model'] ?? 'unknown'));
    $this->line('Input tokens: ' . (int) data_get($result, 'usage.input_tokens', 0));
    $this->line('Output tokens: ' . (int) data_get($result, 'usage.output_tokens', 0));
    $this->newLine();
    $this->line((string) ($result['content'] ?? ''));

    return self::SUCCESS;
})->purpose('Test OpenAI note generation for recruitment notes.');

Artisan::command('recruitment:phase2:process {application_id} {--score=}', function (ScoreBasedProcessingEngine $engine) {
    $applicationId = (int) $this->argument('application_id');
    $application = Application::query()
        ->with(['candidate', 'jobListing', 'aiAnalysis'])
        ->find($applicationId);

    if (!$application) {
        $this->error("Application #{$applicationId} was not found.");
        return self::FAILURE;
    }

    $score = $this->option('score');
    $resolvedScore = is_numeric($score) ? (int) $score : null;

    $result = $engine->process($application, $resolvedScore);

    $this->table(['Key', 'Value'], [
        ['application_id', (string) $result['application_id']],
        ['score', (string) $result['score']],
        ['status_before', (string) $result['status_before']],
        ['status_after', (string) $result['status_after']],
        ['status_applied', $result['status_applied'] ? 'yes' : 'no'],
        ['note_created', $result['note_created'] ? 'yes' : 'no'],
        ['note_fallback_used', $result['note_fallback_used'] ? 'yes' : 'no'],
    ]);

    return self::SUCCESS;
})->purpose('Run Phase 2 score-based decision + note pipeline for an application.');

Artisan::command(
    'recruitment:phase7:simulate-load {--candidates=500} {--chunk=50} {--dispatch-analysis}',
    function () {
        $candidateCount = max(1, (int) $this->option('candidates'));
        $logChunkSize = max(1, (int) $this->option('chunk'));
        $dispatchAnalysis = (bool) $this->option('dispatch-analysis');
        $hasNotificationsTable = Schema::hasTable('notifications');
        $startedAt = microtime(true);
        $startMemory = memory_get_usage(true);

        $candidateRole = Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

        $runToken = Str::lower(Str::random(10));
        $company = Company::query()->create([
            'name' => "Phase7 Load {$runToken}",
            'slug' => "phase7-load-{$runToken}",
            'email' => "phase7-load-{$runToken}@example.test",
            'status' => 'active',
            'plan' => 'pro',
        ]);

        $hr = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'email' => "phase7-load-hr-{$runToken}@example.test",
        ]);
        $hr->assignRole('hr_admin');

        $job = JobListing::query()->create([
            'company_id' => $company->id,
            'created_by' => $hr->id,
            'title' => "Phase7 Load Test Role {$runToken}",
            'slug' => "phase7-load-role-{$runToken}",
            'location' => 'London',
            'location_type' => 'hybrid',
            'job_type' => 'full_time',
            'description' => 'Performance simulation role for phase 7 QA.',
            'status' => 'active',
            'published_at' => now(),
        ]);

        $passwordHash = Hash::make('password');
        $applicationsCreated = 0;
        $emailsQueued = 0;
        $notificationsCreated = 0;
        $analysisJobsDispatched = 0;

        for ($index = 1; $index <= $candidateCount; $index++) {
            $candidateUser = User::query()->create([
                'name' => "Load Candidate {$index}",
                'email' => "phase7-load-candidate-{$runToken}-{$index}@example.test",
                'password' => $passwordHash,
                'company_id' => $company->id,
                'status' => 'active',
            ]);

            DB::table('model_has_roles')->insert([
                'role_id' => $candidateRole->id,
                'model_type' => User::class,
                'model_id' => $candidateUser->id,
            ]);

            $candidate = Candidate::query()->create([
                'user_id' => $candidateUser->id,
                'company_id' => $company->id,
                'name' => $candidateUser->name,
                'email' => $candidateUser->email,
                'cv_status' => 'pending',
                'cv_raw_text' => 'Phase7 load profile text for queue and notification simulation.',
            ]);

            $application = Application::query()->create([
                'job_listing_id' => $job->id,
                'candidate_id' => $candidate->id,
                'company_id' => $company->id,
                'status' => 'applied',
                'cover_letter' => 'Generated by phase 7 load simulation command.',
            ]);
            $applicationsCreated++;

            EmailLog::query()->create([
                'company_id' => $company->id,
                'application_id' => $application->id,
                'candidate_id' => $candidate->id,
                'template' => 'candidate_shortlist',
                'channel' => 'email',
                'direction' => 'outbound',
                'recipient_email' => $candidate->email,
                'subject' => 'Phase 7 load simulation email',
                'provider' => (string) config('mail.default'),
                'status' => 'queued',
                'meta' => [
                    'phase' => 'phase7',
                    'simulation' => true,
                    'batch_token' => $runToken,
                    'candidate_index' => $index,
                ],
            ]);
            $emailsQueued++;

            if ($hasNotificationsTable) {
                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\\Notifications\\ApplicationStatusChanged',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $candidateUser->id,
                    'data' => json_encode([
                        'application_id' => $application->id,
                        'job_title' => $job->title,
                        'status' => 'applied',
                        'note_excerpt' => 'Phase 7 simulation notification',
                        'has_note' => true,
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $notificationsCreated++;
            }

            if ($dispatchAnalysis) {
                ProcessCvAnalysis::dispatch($application);
                $analysisJobsDispatched++;
            }

            if ($index % $logChunkSize === 0 || $index === $candidateCount) {
                $this->line("Phase 7 load simulation progress: {$index}/{$candidateCount}");
            }
        }

        $durationSeconds = round(microtime(true) - $startedAt, 2);
        $memoryDeltaMb = round((memory_get_usage(true) - $startMemory) / 1024 / 1024, 2);
        $peakMemoryMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        $this->table(['Metric', 'Value'], [
            ['Candidates requested', (string) $candidateCount],
            ['Applications created', (string) $applicationsCreated],
            ['Email logs queued', (string) $emailsQueued],
            ['Database notifications created', (string) $notificationsCreated],
            ['Analysis jobs dispatched', (string) $analysisJobsDispatched],
            ['Notifications table present', $hasNotificationsTable ? 'yes' : 'no'],
            ['Queue connection', (string) config('queue.default')],
            ['Duration (seconds)', (string) $durationSeconds],
            ['Memory delta (MB)', (string) $memoryDeltaMb],
            ['Peak memory (MB)', (string) $peakMemoryMb],
            ['Run token', $runToken],
        ]);

        if ($dispatchAnalysis && (string) config('queue.default') === 'sync') {
            $this->warn('Queue connection is sync. Analysis jobs executed inline and may include external AI calls.');
        }

        return self::SUCCESS;
    }
)->purpose('Phase 7 performance simulation for candidate volume, email queue logs, and notification throughput.');

Artisan::command('recruitment:mail:test {to?}', function () {
    $to = trim((string) ($this->argument('to') ?: config('mail.from.address')));
    if ($to === '') {
        $this->error('No recipient provided and MAIL_FROM_ADDRESS is empty.');
        return self::FAILURE;
    }

    $subject = 'NovaHire Phase 1 Email Connectivity Test';
    $body = "This is a Phase 1 recruitment infrastructure test email.\n\nTimestamp: " . now()->toDateTimeString();

    $companyId = Company::query()->value('id');

    try {
        Mail::raw($body, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });

        if (Schema::hasTable('email_logs') && $companyId) {
            EmailLog::query()->create([
                'company_id' => $companyId,
                'recipient_email' => $to,
                'subject' => $subject,
                'provider' => (string) config('mail.default'),
                'status' => 'sent',
                'sent_at' => now(),
                'meta' => [
                    'command' => 'recruitment:mail:test',
                ],
            ]);
        }

        $this->info("Test email dispatched to {$to}.");
        return self::SUCCESS;
    } catch (\Throwable $exception) {
        if (Schema::hasTable('email_logs') && $companyId) {
            EmailLog::query()->create([
                'company_id' => $companyId,
                'recipient_email' => $to,
                'subject' => $subject,
                'provider' => (string) config('mail.default'),
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
                'meta' => [
                    'command' => 'recruitment:mail:test',
                ],
            ]);
        }

        $this->error('Test email failed: ' . $exception->getMessage());
        return self::FAILURE;
    }
})->purpose('Send a recruitment phase test email and record the result.');

Artisan::command('recruitment:emails:digest {--date=}', function () {
    $timezone = (string) config('recruitment.uk_timezone', 'Europe/London');
    $dateOption = trim((string) $this->option('date'));
    $windowEnd = $dateOption !== ''
        ? \Illuminate\Support\Carbon::parse($dateOption, $timezone)->endOfDay()
        : now($timezone);
    $windowStart = $windowEnd->copy()->subDay();

    $companies = Company::query()->get(['id', 'name']);
    $emailsSent = 0;
    $companiesProcessed = 0;

    foreach ($companies as $company) {
        $logs = EmailLog::query()
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$windowStart->copy()->utc(), $windowEnd->copy()->utc()])
            ->where('template', 'like', 'candidate_%')
            ->get();

        if ($logs->isEmpty()) {
            continue;
        }

        $summary = [
            'sent' => (int) $logs->where('status', 'sent')->count(),
            'failed' => (int) $logs->where('status', 'failed')->count(),
            'queued' => (int) $logs->where('status', 'queued')->count(),
            'templates' => $logs->groupBy('template')->map(fn($group) => $group->count())->all(),
        ];

        $hrUsers = User::query()
            ->where('company_id', $company->id)
            ->role('hr_admin')
            ->get(['id', 'email']);

        if ($hrUsers->isEmpty()) {
            continue;
        }

        $companiesProcessed++;

        foreach ($hrUsers as $hrUser) {
            $recipient = trim((string) $hrUser->email);
            if ($recipient === '') {
                continue;
            }

            try {
                Mail::to($recipient)->send(
                    new HrDailyEmailDigestMail($company, $summary, $windowStart, $windowEnd)
                );

                EmailLog::query()->create([
                    'company_id' => $company->id,
                    'template' => 'hr_daily_digest',
                    'channel' => 'email',
                    'direction' => 'outbound',
                    'recipient_email' => $recipient,
                    'subject' => 'NovaHire Daily Email Digest',
                    'provider' => (string) config('mail.default'),
                    'status' => 'sent',
                    'sent_at' => now(),
                    'meta' => [
                        'phase' => 'phase3',
                        'window_start' => $windowStart->toIso8601String(),
                        'window_end' => $windowEnd->toIso8601String(),
                        'summary' => $summary,
                    ],
                ]);

                $emailsSent++;
            } catch (\Throwable $exception) {
                EmailLog::query()->create([
                    'company_id' => $company->id,
                    'template' => 'hr_daily_digest',
                    'channel' => 'email',
                    'direction' => 'outbound',
                    'recipient_email' => $recipient,
                    'subject' => 'NovaHire Daily Email Digest',
                    'provider' => (string) config('mail.default'),
                    'status' => 'failed',
                    'failed_at' => now(),
                    'error_message' => $exception->getMessage(),
                    'meta' => [
                        'phase' => 'phase3',
                        'window_start' => $windowStart->toIso8601String(),
                        'window_end' => $windowEnd->toIso8601String(),
                        'summary' => $summary,
                    ],
                ]);

                logger()->warning('Daily HR email digest delivery failed.', [
                    'company_id' => $company->id,
                    'recipient' => $recipient,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    $this->info("Daily email digest processed for {$companiesProcessed} companies. Emails sent: {$emailsSent}.");
    return self::SUCCESS;
})->purpose('Send HR daily digest for candidate decision emails in the last 24 hours.');

Schedule::job(new SendInterviewReminderNotifications())
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('recruitment:uk-holidays:sync')
    ->weeklyOn(1, '04:30')
    ->withoutOverlapping();

Schedule::command('recruitment:emails:digest')
    ->dailyAt((string) config('recruitment.phase3.digest_send_time', '07:30'))
    ->withoutOverlapping();
