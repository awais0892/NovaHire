@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl p-4 md:p-6 space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Companies</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage tenant companies and subscription status.</p>
            </div>
            <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">Create Company</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ $filters['search'] ?? '' }}" placeholder="Search by name/email/slug" class="input">
            <select name="status" class="input">
                <option value="">All statuses</option>
                @foreach(['active','trial','suspended'] as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
            <select name="plan" class="input">
                <option value="">All plans</option>
                @foreach(['free','basic','pro','enterprise'] as $plan)
                    <option value="{{ $plan }}" @selected(($filters['plan'] ?? '') === $plan)>{{ ucfirst($plan) }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline">Filter</button>
        </form>

        <div class="card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Company</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Users</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Plan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($companies as $company)
                            @php
                                $statusClass = match($company->status) {
                                    'active' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
                                    'trial' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
                                    default => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $company->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $company->email }}</p>
                                    <p class="text-xs text-gray-400">{{ $company->slug }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $company->users_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 uppercase">{{ $company->plan }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase {{ $statusClass }}">{{ $company->status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-outline btn-xs">View</a>
                                        <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-outline btn-xs">Edit</a>
                                        <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" onsubmit="return confirm('Archive this company?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline btn-xs text-error-600">Archive</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No companies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $companies->links() }}</div>
    </div>
@endsection
