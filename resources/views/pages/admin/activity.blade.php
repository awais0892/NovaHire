@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl p-4 md:p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Platform Activity</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Recent registrations and application activity across the platform.</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="app-card app-card-body">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-3">Recent Users</h2>
                <div class="space-y-3">
                    @forelse($recentUsers as $user)
                        <div class="app-subcard glow-subcard p-3" data-glow-card data-glow-proximity="84">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $user->company->name ?? 'No company' }} · {{ $user->created_at?->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No users yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="app-card app-card-body">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-3">Recent Companies</h2>
                <div class="space-y-3">
                    @forelse($recentCompanies as $company)
                        <div class="app-subcard glow-subcard p-3" data-glow-card data-glow-proximity="84">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $company->name }}</p>
                            <p class="text-xs text-gray-500">{{ $company->email }}</p>
                            <p class="text-xs text-gray-400 mt-1 uppercase">{{ $company->plan }} · {{ $company->status }} · {{ $company->created_at?->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No companies yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="app-card app-card-body">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-3">Recent Applications</h2>
                <div class="space-y-3">
                    @forelse($recentApplications as $app)
                        <div class="app-subcard glow-subcard p-3" data-glow-card data-glow-proximity="84">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $app->candidate->name ?? '-' }}</p>
                            <p class="text-xs text-gray-500">{{ $app->jobListing->title ?? '-' }}</p>
                            <p class="text-xs text-gray-400 mt-1 uppercase">{{ $app->status }} · {{ $app->created_at?->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No applications yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
