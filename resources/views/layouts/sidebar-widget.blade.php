<div
    class="mx-auto mb-10 w-full max-w-60 rounded-2xl bg-brand-50 px-4 py-5 text-center dark:bg-white/[0.03] border border-brand-100 dark:border-gray-800">
    @php
        $company = Auth::user()?->company;
        $planName = ucfirst(str_replace('_', ' ', strtolower((string) ($company->plan ?? 'free'))));
        $settingsPath = route('account.settings');
    @endphp
    <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">
        {{ $company?->name ?? 'Your Company' }}
    </h3>
    <p class="mb-4 text-theme-sm text-gray-500 dark:text-gray-400">
        Current Plan: <span class="font-medium text-brand-600 dark:text-brand-400">{{ $planName }}</span>
    </p>
    <a href="{{ $settingsPath }}"
        class="flex items-center justify-center p-3 font-medium text-white rounded-lg bg-brand-500 text-theme-sm hover:bg-brand-600">
        Upgrade Plan
    </a>
</div>
