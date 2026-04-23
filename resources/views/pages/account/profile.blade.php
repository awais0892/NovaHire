@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">My Profile</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Keep your account details up to date.</p>
            </div>
            <div class="text-right">
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['primary'] }}</div>
                <div class="text-xs text-gray-500">{{ $stats['primary_label'] }}</div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @php
            $avatarUrl = $user->avatar_url;
            $userInitial = strtoupper(substr($user->name ?? 'U', 0, 1));
        @endphp

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
            class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] space-y-5">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Photo</label>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    @if($avatarUrl)
                        <img
                            src="{{ $avatarUrl }}"
                            alt="{{ $user->name }}"
                            class="h-20 w-20 rounded-full border border-gray-200 object-cover dark:border-gray-700"
                        />
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-full border border-gray-200 bg-brand-100 text-2xl font-semibold text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-brand-400">
                            {{ $userInitial }}
                        </div>
                    @endif

                    <div class="flex-1">
                        <input
                            type="file"
                            name="avatar"
                            accept=".jpg,.jpeg,.png,.webp,.avif,image/jpeg,image/png,image/webp,image/avif"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, WEBP, or AVIF. Maximum size: 2MB.</p>
                        @error('avatar') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                    <input name="name" value="{{ old('name', $user->name) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input name="email" value="{{ old('email', $user->email) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                    <input name="phone" value="{{ old('phone', $user->candidate?->phone) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                    <input name="location" value="{{ old('location', $user->candidate?->location) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('location') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">LinkedIn</label>
                    <input name="linkedin" value="{{ old('linkedin', $user->candidate?->linkedin) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('linkedin') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">GitHub</label>
                    <input name="github" value="{{ old('github', $user->candidate?->github) }}"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                    @error('github') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Portfolio</label>
                <input name="portfolio" value="{{ old('portfolio', $user->candidate?->portfolio) }}"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white" />
                @error('portfolio') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                    Save Profile
                </button>
            </div>
        </form>
    </div>
@endsection
