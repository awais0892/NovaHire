@extends('layouts.app')

@section('content')
    <div class="space-y-6 max-w-4xl">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Company Settings</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage company profile and contact details.</p>
            </div>
            <div class="text-right">
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $memberCount }}</div>
                <div class="text-xs text-gray-500">Team Members</div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('recruiter.settings.update') }}"
            class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Company Name</label>
                    <input name="name" value="{{ old('name', $company->name) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Company Email</label>
                    <input name="email" value="{{ old('email', $company->email) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                    <input name="phone" value="{{ old('phone', $company->phone) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Website</label>
                    <input name="website" value="{{ old('website', $company->website) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('website') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300">
                Current plan: <span class="font-semibold uppercase">{{ $company->plan }}</span>.
                Manage plan upgrades from <a href="{{ route('account.settings') }}" class="text-brand-600 hover:text-brand-700 font-medium">Account Settings</a>.
            </div>

            <div class="flex justify-end">
                <button class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                    Save Company Settings
                </button>
            </div>
        </form>
    </div>
@endsection
