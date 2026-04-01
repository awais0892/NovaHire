<?php

use App\Models\Candidate;
use App\Models\User;
use Spatie\Permission\Models\Role;

function candidateUserForAccountProfile(): User
{
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'status' => 'active',
        'company_id' => null,
    ]);

    $user->assignRole('candidate');

    return $user;
}

test('candidate hitting generic profile route is redirected to candidate profile page', function () {
    $user = candidateUserForAccountProfile();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertRedirect(route('candidate.profile'));
});

test('candidate generic profile update creates missing candidate record', function () {
    $user = candidateUserForAccountProfile();

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Candidate',
            'email' => $user->email,
            'phone' => '+1 555 000 1111',
            'location' => 'London',
            'linkedin' => 'https://linkedin.com/in/updated-candidate',
            'github' => 'https://github.com/updated-candidate',
            'portfolio' => 'https://updated-candidate.dev',
        ])
        ->assertRedirect();

    $candidate = Candidate::where('user_id', $user->id)->first();

    expect($candidate)->not->toBeNull();
    expect($candidate?->email)->toBe($user->email);
    expect($candidate?->name)->toBe('Updated Candidate');
    expect($candidate?->location)->toBe('London');
});
