<?php

use App\Livewire\Candidate\CandidateProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('candidate can enable two-step verification from profile settings', function () {
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

    $candidate = User::factory()->create([
        'status' => 'active',
        'two_factor_enabled' => false,
    ]);
    $candidate->assignRole('candidate');

    $this->actingAs($candidate);

    Livewire::test(CandidateProfile::class)
        ->set('name', 'Candidate User')
        ->set('twoFactorEnabled', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect($candidate->fresh()->two_factor_enabled)->toBeTrue();
});

test('candidate can disable two-step verification from profile settings', function () {
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

    $candidate = User::factory()->create([
        'status' => 'active',
        'two_factor_enabled' => true,
    ]);
    $candidate->assignRole('candidate');

    $this->actingAs($candidate);

    Livewire::test(CandidateProfile::class)
        ->set('name', 'Candidate User')
        ->set('twoFactorEnabled', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect($candidate->fresh()->two_factor_enabled)->toBeFalse();
});
