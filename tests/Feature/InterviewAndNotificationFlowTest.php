<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\JobListing;
use App\Models\User;
use App\Notifications\InterviewInvitationResponded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function seedCoreRolesForFlow(): void
{
    foreach (['candidate', 'hr_admin'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
}

function buildInterviewFlowData(): array
{
    seedCoreRolesForFlow();

    $company = Company::create([
        'name' => 'Flow Corp',
        'slug' => 'flow-corp',
        'email' => 'flow@example.com',
        'status' => 'active',
    ]);

    $hr = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $hr->assignRole('hr_admin');

    $candidateUser = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $candidateUser->assignRole('candidate');

    $candidate = Candidate::create([
        'user_id' => $candidateUser->id,
        'company_id' => $company->id,
        'name' => $candidateUser->name,
        'email' => $candidateUser->email,
    ]);

    $job = JobListing::create([
        'company_id' => $company->id,
        'created_by' => $hr->id,
        'title' => 'Backend Engineer',
        'slug' => 'backend-engineer-flow',
        'location' => 'London',
        'description' => 'Role description',
        'status' => 'active',
    ]);

    $application = Application::create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'interview',
    ]);

    $interview = Interview::create([
        'company_id' => $company->id,
        'application_id' => $application->id,
        'scheduled_by' => $hr->id,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHour(),
        'timezone' => 'UTC',
        'mode' => 'video',
        'meeting_link' => 'https://meet.example.com/test',
        'status' => 'scheduled',
    ]);

    return compact('company', 'hr', 'candidateUser', 'candidate', 'job', 'application', 'interview');
}

test('candidate can accept interview invitation and hr receives notification', function () {
    $data = buildInterviewFlowData();

    $this->actingAs($data['candidateUser'])
        ->post(route('candidate.interviews.invitation.respond', $data['interview']), [
            'response' => 'accepted',
        ])
        ->assertRedirect(route('candidate.interviews.invitation.show', $data['interview']));

    $this->assertDatabaseHas('interviews', [
        'id' => $data['interview']->id,
        'candidate_response' => 'accepted',
    ]);

    expect(
        $data['hr']->fresh()->notifications()->where('type', InterviewInvitationResponded::class)->exists()
    )->toBeTrue();
});

test('notification endpoints mark single and all as read', function () {
    seedCoreRolesForFlow();
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole('candidate');

    $user->notify(new class extends Notification {
        public function via($notifiable): array
        {
            return ['database'];
        }

        public function toArray($notifiable): array
        {
            return ['type' => 'system_test', 'message' => 'Test'];
        }
    });

    $notificationId = $user->notifications()->first()->id;

    $this->actingAs($user)
        ->get(route('notifications.feed'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1);

    $this->actingAs($user)
        ->post(route('notifications.read', $notificationId))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('notifications.feed'))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    $user->notify(new class extends Notification {
        public function via($notifiable): array
        {
            return ['database'];
        }

        public function toArray($notifiable): array
        {
            return ['type' => 'system_test_2', 'message' => 'Test 2'];
        }
    });

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('notifications.feed'))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);
});

test('wrong role opening candidate invitation is redirected to login then intended candidate invitation after login', function () {
    $data = buildInterviewFlowData();

    $this->actingAs($data['hr'])
        ->get(route('candidate.interviews.invitation.show', $data['interview']))
        ->assertRedirect(route('login'));

    $this->post(route('login.post'), [
        'email' => $data['candidateUser']->email,
        'password' => 'password',
    ])->assertRedirect(route('candidate.interviews.invitation.show', $data['interview']));
});
