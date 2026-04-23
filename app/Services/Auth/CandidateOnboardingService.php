<?php

namespace App\Services\Auth;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CandidateOnboardingService
{
    public function createCandidateUser(array $attributes): User
    {
        return DB::transaction(function () use ($attributes) {
            $user = User::create($attributes);

            $this->syncCandidateAccountRecords($user);

            return $user->fresh();
        });
    }

    public function syncCandidateAccount(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $this->syncCandidateAccountRecords($user);

            return $user->fresh();
        });
    }

    private function syncCandidateAccountRecords(User $user): void
    {
        $candidateRole = Role::findOrCreate('candidate', 'web');

        if (! $user->hasRole($candidateRole->name)) {
            $user->assignRole($candidateRole);
        }

        Candidate::updateOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $user->email,
                'name' => $user->name,
                'company_id' => $user->company_id,
            ]
        );
    }
}
