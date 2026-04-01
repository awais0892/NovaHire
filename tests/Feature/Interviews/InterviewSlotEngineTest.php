<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\InterviewSlot;
use App\Models\JobListing;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\InterviewSlotEngineService;
use App\Services\UkBankHolidayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('slot engine excludes uk bank holidays and disabled weekends by default', function () {
    $holidayService = Mockery::mock(UkBankHolidayService::class);
    $holidayService->shouldReceive('events')
        ->andReturn([
            [
                'title' => "New Year's Day",
                'date' => '2026-01-01',
                'year' => 2026,
                'notes' => '',
                'bunting' => true,
            ],
        ]);
    $this->app->instance(UkBankHolidayService::class, $holidayService);

    $company = Company::query()->create([
        'name' => 'Slot Co',
        'slug' => 'slot-co',
        'email' => fake()->unique()->safeEmail(),
        'status' => 'active',
    ]);

    $engine = app(InterviewSlotEngineService::class);
    $result = $engine->generateSlots($company->id, '2026-01-01', '2026-01-04');

    expect((int) $result['generated_total'])->toBeGreaterThan(0);
    expect(InterviewSlot::query()->where('company_id', $company->id)->whereDate('slot_date', '2026-01-01')->count())->toBe(0);
    expect(InterviewSlot::query()->where('company_id', $company->id)->whereDate('slot_date', '2026-01-02')->count())->toBeGreaterThan(0);
    expect(InterviewSlot::query()->where('company_id', $company->id)->whereDate('slot_date', '2026-01-03')->count())->toBe(0);
    expect(InterviewSlot::query()->where('company_id', $company->id)->whereDate('slot_date', '2026-01-04')->count())->toBe(0);
});

test('slot booking prevents double booking conflicts', function () {
    $data = makeInterviewSlotApplicationData();
    $engine = app(InterviewSlotEngineService::class);

    $slot = InterviewSlot::query()->create([
        'company_id' => $data['company']->id,
        'slot_date' => now()->addDays(2)->toDateString(),
        'starts_at' => now()->addDays(2)->startOfHour()->utc(),
        'ends_at' => now()->addDays(2)->startOfHour()->addMinutes(45)->utc(),
        'timezone' => 'Europe/London',
        'duration_minutes' => 45,
        'buffer_minutes' => 10,
        'mode' => 'video',
        'is_available' => true,
    ]);

    $interview = $engine->bookSlotForApplication($data['applicationA'], $slot->id, $data['recruiter']->id);

    expect($interview->interview_slot_id)->toBe($slot->id);
    expect(fn() => $engine->bookSlotForApplication($data['applicationB'], $slot->id, $data['recruiter']->id))
        ->toThrow(ValidationException::class);

    $slot->refresh();
    expect($slot->booked_application_id)->toBe($data['applicationA']->id);
    expect($slot->is_available)->toBeFalse();
});

test('recruiter can fetch available slots and schedule interview using slot id', function () {
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

    $data = makeInterviewSlotApplicationData();
    $data['recruiter']->assignRole('hr_admin');

    $slot = InterviewSlot::query()->create([
        'company_id' => $data['company']->id,
        'slot_date' => now()->addDays(3)->toDateString(),
        'starts_at' => now()->addDays(3)->setTime(10, 0)->utc(),
        'ends_at' => now()->addDays(3)->setTime(10, 45)->utc(),
        'timezone' => 'Europe/London',
        'duration_minutes' => 45,
        'buffer_minutes' => 10,
        'mode' => 'video',
        'is_available' => true,
    ]);

    $slotResponse = $this->actingAs($data['recruiter'])
        ->getJson(route('recruiter.applications.interviews.slots', $data['applicationA']))
        ->assertOk()
        ->assertJsonFragment(['id' => $slot->id]);
    expect((int) $slotResponse->json('count'))->toBeGreaterThan(0);

    $calendarService = Mockery::mock(GoogleCalendarService::class);
    $calendarService->shouldReceive('upsertInterviewEvent')
        ->once()
        ->andReturn([
            'event_id' => 'gcal-event-1',
            'event_link' => 'https://calendar.google.com/event?eid=gcal-event-1',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);
    $this->app->instance(GoogleCalendarService::class, $calendarService);

    $this->actingAs($data['recruiter'])
        ->from(route('recruiter.applications'))
        ->post(route('recruiter.applications.interviews.schedule', $data['applicationA']), [
            'slot_id' => $slot->id,
            'notes' => 'Bring portfolio samples',
        ])
        ->assertRedirect(route('recruiter.applications'));

    $this->assertDatabaseHas('interviews', [
        'application_id' => $data['applicationA']->id,
        'interview_slot_id' => $slot->id,
        'status' => 'scheduled',
        'notes' => 'Bring portfolio samples',
    ]);
    $this->assertDatabaseHas('interview_slots', [
        'id' => $slot->id,
        'booked_application_id' => $data['applicationA']->id,
        'is_available' => 0,
    ]);
});

test('cancelling an interview also triggers google calendar cancellation sync safely', function () {
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

    $data = makeInterviewSlotApplicationData();
    $data['recruiter']->assignRole('hr_admin');

    $interview = \App\Models\Interview::query()->create([
        'company_id' => $data['company']->id,
        'application_id' => $data['applicationA']->id,
        'scheduled_by' => $data['recruiter']->id,
        'starts_at' => now()->addDays(2)->setTime(11, 0)->utc(),
        'ends_at' => now()->addDays(2)->setTime(11, 45)->utc(),
        'timezone' => 'Europe/London',
        'mode' => 'video',
        'meeting_link' => 'https://meet.example.com/phase7',
        'status' => 'scheduled',
        'google_calendar_event_id' => 'gcal-existing-event',
    ]);

    $calendarService = Mockery::mock(GoogleCalendarService::class);
    $calendarService->shouldReceive('cancelInterviewEvent')
        ->once()
        ->andReturnNull();
    $this->app->instance(GoogleCalendarService::class, $calendarService);

    $this->actingAs($data['recruiter'])
        ->from(route('recruiter.applications'))
        ->patch(route('recruiter.applications.interviews.cancel', [$data['applicationA'], $interview]), [
            'reason' => 'Role closed',
        ])
        ->assertRedirect(route('recruiter.applications'));

    $this->assertDatabaseHas('interviews', [
        'id' => $interview->id,
        'status' => 'cancelled',
        'cancelled_reason' => 'Role closed',
    ]);
});

function makeInterviewSlotApplicationData(): array
{
    $company = Company::query()->create([
        'name' => 'Recruiting Co',
        'slug' => 'recruiting-co',
        'email' => fake()->unique()->safeEmail(),
        'status' => 'active',
    ]);

    $recruiter = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $candidateUserA = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $candidateA = Candidate::query()->create([
        'user_id' => $candidateUserA->id,
        'company_id' => $company->id,
        'name' => $candidateUserA->name,
        'email' => $candidateUserA->email,
    ]);

    $candidateUserB = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $candidateB = Candidate::query()->create([
        'user_id' => $candidateUserB->id,
        'company_id' => $company->id,
        'name' => $candidateUserB->name,
        'email' => $candidateUserB->email,
    ]);

    $job = JobListing::query()->create([
        'company_id' => $company->id,
        'created_by' => $recruiter->id,
        'title' => 'Platform Engineer',
        'slug' => 'platform-engineer-' . uniqid(),
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'description' => 'Build scalable systems.',
        'status' => 'active',
    ]);

    $applicationA = Application::query()->create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidateA->id,
        'company_id' => $company->id,
        'status' => 'shortlisted',
    ]);
    $applicationB = Application::query()->create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidateB->id,
        'company_id' => $company->id,
        'status' => 'shortlisted',
    ]);

    return compact('company', 'recruiter', 'job', 'candidateA', 'candidateB', 'applicationA', 'applicationB');
}
