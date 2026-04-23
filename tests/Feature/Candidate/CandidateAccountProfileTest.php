<?php

use App\Models\Candidate;
use App\Models\User;
use App\Services\CloudinaryImageService;
use Illuminate\Http\UploadedFile;
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

test('candidate generic profile update uploads avatar to cloudinary', function () {
    $user = candidateUserForAccountProfile();

    $cloudinary = Mockery::mock(CloudinaryImageService::class);
    $cloudinary->shouldReceive('uploadAvatar')
        ->once()
        ->andReturn('https://res.cloudinary.com/demo/image/upload/v1710000000/novahire/avatars/' . $user->id . '/profile.jpg');
    $this->app->instance(CloudinaryImageService::class, $cloudinary);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'Updated Candidate Avatar',
            'email' => $user->email,
            'phone' => '+1 555 000 1111',
            'location' => 'London',
            'linkedin' => 'https://linkedin.com/in/updated-candidate',
            'github' => 'https://github.com/updated-candidate',
            'portfolio' => 'https://updated-candidate.dev',
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 256, 256),
        ])
        ->assertRedirect();

    expect($user->fresh()->avatar)
        ->toBe('https://res.cloudinary.com/demo/image/upload/v1710000000/novahire/avatars/' . $user->id . '/profile.jpg');
});
