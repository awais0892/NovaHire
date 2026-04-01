@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl p-4 md:p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $company->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Company profile and assigned users</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-outline">Edit</a>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-outline">Back</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="card p-5 lg:col-span-1 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><span class="text-gray-500">Slug</span><span class="font-medium text-gray-900 dark:text-white">{{ $company->slug }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Email</span><span class="font-medium text-gray-900 dark:text-white">{{ $company->email }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Phone</span><span class="font-medium text-gray-900 dark:text-white">{{ $company->phone ?: '-' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Website</span><span class="font-medium text-gray-900 dark:text-white">{{ $company->website ?: '-' }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Plan</span><span class="font-medium uppercase text-gray-900 dark:text-white">{{ $company->plan }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Status</span><span class="font-medium uppercase text-gray-900 dark:text-white">{{ $company->status }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-gray-500">Users</span><span class="font-medium text-gray-900 dark:text-white">{{ $company->users_count }}</span></div>
            </div>

            <div class="card p-0 lg:col-span-2 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Assigned Users</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($company->users as $user)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No users assigned.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
