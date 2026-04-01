<?php

namespace App\Policies;

use App\Models\JobListing;
use App\Models\User;

class JobListingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('jobs.view');
    }

    public function view(User $user, JobListing $job): bool
    {
        return $user->can('jobs.view') && $user->company_id === $job->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('jobs.create') && !is_null($user->company_id);
    }

    public function update(User $user, JobListing $job): bool
    {
        return $user->can('jobs.edit') && $user->company_id === $job->company_id;
    }

    public function delete(User $user, JobListing $job): bool
    {
        return $user->can('jobs.delete') && $user->company_id === $job->company_id;
    }

    public function restore(User $user, JobListing $job): bool
    {
        return $this->update($user, $job);
    }

    public function forceDelete(User $user, JobListing $job): bool
    {
        return $user->can('jobs.delete') && $user->company_id === $job->company_id;
    }
}
