@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl p-4 md:p-6 space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Account Settings</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Security and billing preferences.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="card p-6 xl:col-span-2">
                <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Change Password</h3>
                <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="label">New Password</label>
                        <input type="password" name="password" class="input" />
                        @error('password') <p class="mt-1 text-xs text-error-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="input" />
                    </div>

                    <div class="flex justify-end">
                        <button class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>

            <div class="card p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Current Billing</h3>
                @if($company)
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Company</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $company->name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Plan</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $currentPlanName }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Billing Status</span>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase {{ $statusClass }}">{{ $billingStatus }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Renews / Ends</span>
                            <span class="text-gray-900 dark:text-white">{{ $company->billing_period_ends_at?->format('d M Y') ?? '-' }}</span>
                        </div>
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No company is attached to this account, so billing is unavailable.</p>
                @endif
            </div>
        </div>

        <div class="card p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Notification Preferences</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Choose where each update should be delivered.</p>

            @php
                $types = [
                    'application_status_changed' => 'Application status updates',
                    'interview_scheduled' => 'Interview scheduled',
                    'interview_cancelled' => 'Interview cancelled',
                    'interview_invitation_responded' => 'Candidate invitation response',
                    'interview_reminder' => 'Interview reminders (24h/1h)',
                ];
                $channels = [
                    'mail' => 'Email',
                    'database' => 'In-app',
                    'broadcast' => 'Realtime',
                ];
            @endphp

            <form method="POST" action="{{ route('account.notifications.update') }}">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Event</th>
                                @foreach($channels as $channelLabel)
                                    <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">{{ $channelLabel }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($types as $key => $label)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $label }}</td>
                                    @foreach($channels as $channelKey => $channelLabel)
                                        <td class="px-4 py-3">
                                            <label class="inline-flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    name="preferences[{{ $key }}][{{ $channelKey }}]"
                                                    value="1"
                                                    class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                                    {{ data_get($notificationPreferences ?? [], "{$key}.{$channelKey}") ? 'checked' : '' }}
                                                >
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Enabled</span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <button class="btn btn-primary">Save Preferences</button>
                </div>
            </form>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Billing and Subscription</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Basic is free. Individual monthly includes a {{ (int) ($individualMonthlyTrialDays ?? 30) }}-day trial, then renews automatically unless canceled.</p>
                </div>
                @if(!$stripeConfigured)
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300">
                        Stripe not configured
                    </span>
                @elseif(in_array($billingStatus, ['past_due', 'incomplete', 'unpaid'], true))
                    <form method="POST" action="{{ route('billing.portal') }}">
                        @csrf
                        <button class="btn btn-outline btn-sm">Retry Payment</button>
                    </form>
                @elseif($isSubscribed)
                    <div class="flex items-center gap-2">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300">
                            Subscription active
                        </span>
                        <form method="POST" action="{{ route('billing.portal') }}">
                            @csrf
                            <button class="btn btn-outline btn-sm">Manage / Cancel Plan</button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($stripePlans as $plan)
                    @php($isCurrent = $currentPlan === $plan['key'])
                    <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700 {{ $isCurrent ? 'ring-2 ring-brand-500/40' : '' }}">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ $plan['name'] }}</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">${{ $plan['display_amount'] }}<span class="text-sm font-medium text-gray-500">/mo</span></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Annual: ${{ $plan['display_amount_annual'] }}/yr</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $plan['description'] ?: 'Premium plan' }}</p>
                        @if((int) data_get($plan, 'trial_days', 0) > 0)
                            <p class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                                {{ (int) data_get($plan, 'trial_days', 0) }}-day monthly trial. Auto-charge starts after trial unless canceled.
                            </p>
                        @endif

                        <ul class="mt-3 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <li>Jobs/month: {{ (($plan['limits']['job_posts_per_month'] ?? 0) < 0) ? 'Unlimited' : ($plan['limits']['job_posts_per_month'] ?? 0) }}</li>
                            <li>CV downloads: {{ (($plan['limits']['cv_downloads_per_month'] ?? 0) < 0) ? 'Unlimited' : ($plan['limits']['cv_downloads_per_month'] ?? 0) }}</li>
                            <li>AI analyses: {{ (($plan['limits']['ai_analyses_per_month'] ?? 0) < 0) ? 'Unlimited' : ($plan['limits']['ai_analyses_per_month'] ?? 0) }}</li>
                        </ul>

                        <div class="mt-4">
                            @if(!$company)
                                <button class="btn btn-outline btn-sm cursor-not-allowed opacity-70" disabled>No company</button>
                            @elseif(!$stripeConfigured && $plan['key'] !== 'free')
                                <button class="btn btn-outline btn-sm cursor-not-allowed opacity-70" disabled>Configure Stripe</button>
                            @else
                                <form method="POST" action="{{ route('billing.checkout') }}">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $plan['key'] }}">
                                    @if($plan['key'] !== 'free')
                                        <div class="mb-2">
                                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Billing cycle</label>
                                            <select name="billing_cycle" class="input h-9 text-xs">
                                                <option value="monthly">Monthly</option>
                                                <option value="annual">Annual (discounted)</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Voucher code</label>
                                            <input name="voucher_code" class="input h-9 text-xs" placeholder="WELCOME5">
                                        </div>
                                    @endif
                                    <button class="btn {{ $isCurrent ? 'btn-outline' : 'btn-primary' }} btn-sm" {{ $isCurrent ? 'disabled' : '' }}>
                                        {{ $isCurrent ? 'Current Plan' : ($plan['key'] === 'free' ? 'Activate Basic (Free)' : 'Upgrade to ' . $plan['name']) }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                To avoid renewal charges, cancel your paid subscription from the billing portal before the next renewal date.
            </p>

            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                For local webhook testing: `stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook`.
            </p>
            @if(!empty($activeVouchers))
                <div class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Active voucher examples</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($activeVouchers as $voucher)
                            <span class="inline-flex rounded-full border border-gray-200 px-2.5 py-1 text-[11px] font-semibold dark:border-gray-700">
                                {{ $voucher->code }} ({{ $voucher->type === 'percent' ? rtrim(rtrim(number_format((float) $voucher->value, 2), '0'), '.') . '%' : '$' . number_format((float) $voucher->value, 2) }})
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
