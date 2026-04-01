@php
    $isEdit = isset($company);
    $action = $isEdit ? route('admin.companies.update', $company) : route('admin.companies.store');
@endphp

@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl p-4 md:p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $isEdit ? 'Edit Company' : 'Create Company' }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Configure organization details, plan, and status.</p>
            </div>
            <a href="{{ route('admin.companies.index') }}" class="btn btn-outline">Back</a>
        </div>

        <form method="POST" action="{{ $action }}" class="card p-6 space-y-5">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="label">Company Name</label>
                    <input class="input" name="name" value="{{ old('name', $company->name ?? '') }}" required>
                    @error('name') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Email</label>
                    <input type="email" class="input" name="email" value="{{ old('email', $company->email ?? '') }}" required>
                    @error('email') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input class="input" name="phone" value="{{ old('phone', $company->phone ?? '') }}">
                    @error('phone') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Website</label>
                    <input class="input" name="website" value="{{ old('website', $company->website ?? '') }}" placeholder="https://example.com">
                    @error('website') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Plan</label>
                    <select class="input" name="plan" required>
                        @foreach(['free','basic','pro','enterprise'] as $plan)
                            <option value="{{ $plan }}" @selected(old('plan', $company->plan ?? 'free') === $plan)>{{ ucfirst($plan) }}</option>
                        @endforeach
                    </select>
                    @error('plan') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Status</label>
                    <select class="input" name="status" required>
                        @foreach(['active','trial','suspended'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $company->status ?? 'trial') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Trial Ends At</label>
                    <input type="date" class="input" name="trial_ends_at" value="{{ old('trial_ends_at', isset($company->trial_ends_at) && $company->trial_ends_at ? $company->trial_ends_at->format('Y-m-d') : '') }}">
                    @error('trial_ends_at') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button class="btn btn-primary">{{ $isEdit ? 'Save Changes' : 'Create Company' }}</button>
            </div>
        </form>
    </div>
@endsection
