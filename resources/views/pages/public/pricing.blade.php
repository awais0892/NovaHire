@extends('layouts.public')

@section('content')
    @php
        $normalizeMoney = function ($value): float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            $clean = preg_replace('/[^0-9.]/', '', (string) $value);
            return $clean === '' ? 0.0 : (float) $clean;
        };

        $cmsPlans = collect(data_get($content ?? [], 'plans', []));
        $basePlans = ($stripePlans ?? collect())->isNotEmpty()
            ? collect($stripePlans)
            : $cmsPlans->map(fn ($plan) => [
                'key' => strtolower((string) data_get($plan, 'name')),
                'name' => (string) data_get($plan, 'name'),
                'price' => (string) data_get($plan, 'price'),
                'description' => (string) data_get($plan, 'desc'),
                'interval' => 'month',
                'limits' => (array) data_get($plan, 'limits', []),
                'cta' => (string) data_get($plan, 'cta', 'Get Started'),
                'highlight' => (bool) data_get($plan, 'highlight', false),
            ]);

        $fallbackLimitsByName = [
            'basic' => [
                'job_posts_per_month' => 2,
                'cv_downloads_per_month' => 40,
                'ai_analyses_per_month' => 60,
                'team_members' => 1,
            ],
            'individual' => [
                'job_posts_per_month' => 10,
                'cv_downloads_per_month' => 200,
                'ai_analyses_per_month' => 300,
                'team_members' => 3,
            ],
            'pro' => [
                'job_posts_per_month' => 50,
                'cv_downloads_per_month' => 1200,
                'ai_analyses_per_month' => 3000,
                'team_members' => 15,
            ],
            'enterprise' => [
                'job_posts_per_month' => -1,
                'cv_downloads_per_month' => -1,
                'ai_analyses_per_month' => -1,
                'team_members' => -1,
            ],
        ];

        $limitLabels = [
            'job_posts_per_month' => 'Job posts / month',
            'cv_downloads_per_month' => 'CV downloads / month',
            'ai_analyses_per_month' => 'AI analyses / month',
            'team_members' => 'Team members',
        ];

        $formatLimit = function ($value): string {
            $numeric = (int) $value;
            return $numeric === -1 ? 'Unlimited' : number_format($numeric);
        };

        $plans = $basePlans->map(function ($plan) use ($fallbackLimitsByName, $limitLabels, $formatLimit, $normalizeMoney) {
            $nameKey = strtolower((string) data_get($plan, 'name', data_get($plan, 'key', '')));
            $limits = (array) data_get($plan, 'limits', []);
            if (empty($limits) && isset($fallbackLimitsByName[$nameKey])) {
                $limits = $fallbackLimitsByName[$nameKey];
            }

            $monthly = (int) data_get($plan, 'monthly_cents', 0) > 0
                ? ((int) data_get($plan, 'monthly_cents', 0) / 100)
                : $normalizeMoney(data_get($plan, 'price', 0));
            $annual = (int) data_get($plan, 'annual_cents', 0) > 0
                ? ((int) data_get($plan, 'annual_cents', 0) / 100)
                : ($monthly > 0 ? round($monthly * 12 * 0.8) : 0);

            $limitBullets = collect($limits)
                ->map(fn ($value, $limitKey) => ($limitLabels[$limitKey] ?? ucfirst(str_replace('_', ' ', (string) $limitKey))) . ': ' . $formatLimit($value))
                ->values()
                ->all();

            $isFree = (string) data_get($plan, 'key', $nameKey) === 'free' || $monthly <= 0;
            $fallbackBullets = match (true) {
                $isFree => [
                    'Up to 2 active job postings',
                    'Basic analytics',
                    'Community support',
                ],
                str_contains($nameKey, 'basic') => [
                    'Up to 10 projects',
                    '48-hour support response time',
                    'Team collaboration',
                ],
                str_contains($nameKey, 'pro') => [
                    'Unlimited projects',
                    'Advanced analytics',
                    'Priority support',
                    'Custom integrations',
                ],
                str_contains($nameKey, 'enterprise') => [
                    'Custom solutions',
                    'Dedicated account manager',
                    'SLA agreement',
                ],
                default => [
                    'AI-assisted screening',
                    'Workflow automation',
                    'Dedicated support',
                ],
            };

            return [
                'key' => (string) data_get($plan, 'key', $nameKey),
                'name' => (string) data_get($plan, 'name', ucfirst($nameKey)),
                'description' => (string) data_get($plan, 'description', data_get($plan, 'desc', 'Plan built for your hiring workflow.')),
                'monthly' => $monthly,
                'annual' => $annual,
                'period' => (string) data_get($plan, 'interval', 'month'),
                'trial_days' => (int) data_get($plan, 'trial_days', 0),
                'highlight' => (bool) data_get($plan, 'highlight', false) || str_contains($nameKey, 'pro'),
                'cta' => (string) data_get($plan, 'cta', ($nameKey === 'enterprise' ? 'Contact Sales' : 'Get Started')),
                'limits' => $limits,
                'features' => array_slice((array) (data_get($plan, 'features') ?? data_get($plan, 'bullets') ?? (count($limitBullets) ? $limitBullets : $fallbackBullets)), 0, 8),
                'is_free' => $isFree,
            ];
        })->values();
    @endphp

    <section class="space-y-10" id="pricing-page" data-pricing-widget>
        <header class="space-y-6">
            <div class="flex flex-col gap-3">
                <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-300">
                    Pricing
                </span>
                <h1 class="text-4xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                    Plans that scale with your hiring
                </h1>
                <p class="max-w-2xl text-base text-slate-600 dark:text-slate-300">
                    Whether you’re hiring your first teammate or running a global talent pipeline, NovaHire keeps pricing
                    predictable while giving you enterprise-grade AI tools from day one.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-white/80 p-4 text-sm shadow-sm backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/70">
                <div class="pricing-billing-switch inline-flex items-center rounded-full border border-slate-200 bg-white/90 p-1 text-xs font-medium shadow-sm dark:border-slate-700 dark:bg-slate-950/60" data-pricing-switch>
                    <span class="pricing-billing-switch-indicator" data-pricing-switch-indicator aria-hidden="true"></span>
                    <button
                        type="button"
                        data-pricing-billing="monthly"
                        aria-pressed="true"
                        class="pricing-billing-button is-active rounded-full px-4 py-2 font-semibold">
                        Monthly
                    </button>
                    <button
                        type="button"
                        data-pricing-billing="annual"
                        aria-pressed="false"
                        class="pricing-billing-button rounded-full px-4 py-2 font-semibold transition">
                        <span class="flex items-center gap-1.5">
                            Annual
                            <span class="pricing-billing-chip">(Save 20%)</span>
                        </span>
                    </button>
                </div>
                <dl class="flex flex-wrap gap-4 text-xs text-slate-500 dark:text-slate-300">
                    <div class="flex items-center gap-2">
                        <dt class="inline-flex h-2 w-2 rounded-full bg-emerald-400"></dt>
                        <dd>No setup fees</dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <dt class="inline-flex h-2 w-2 rounded-full bg-emerald-400"></dt>
                        <dd>Cancel anytime</dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <dt class="inline-flex h-2 w-2 rounded-full bg-emerald-400"></dt>
                        <dd>Usage-based, fair billing</dd>
                    </div>
                </dl>
            </div>
        </header>

        <div class="space-y-10">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3 md:auto-rows-fr">
                @foreach($plans as $index => $plan)
                    @php
                        $isHighlight = (bool) data_get($plan, 'highlight');
                        $name = (string) data_get($plan, 'name');
                        $monthly = (float) data_get($plan, 'monthly');
                        $annual = (float) data_get($plan, 'annual');
                        $period = (string) data_get($plan, 'period');
                        $isFree = (bool) data_get($plan, 'is_free');
                        $description = (string) data_get($plan, 'description');
                        $trialDays = (int) data_get($plan, 'trial_days', 0);
                        $stackClass = $isHighlight ? 'md:scale-[1.02]' : '';
                    @endphp

                    <article
                        data-plan-card
                        data-plan-key="{{ data_get($plan, 'key') }}"
                        class="relative flex h-full flex-col rounded-2xl border border-slate-200 bg-white/95 p-6 text-left shadow-sm ring-1 ring-transparent transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-brand-500/40 focus-within:ring-brand-500/40 dark:border-slate-700 dark:bg-slate-900/80 {{ $stackClass }}">
                        @if($isHighlight)
                            <div class="absolute right-4 top-4 inline-flex items-center whitespace-nowrap rounded-full bg-brand-600/95 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm z-10">
                                <i data-lucide="star" class="mr-1 h-3 w-3 fill-current"></i>
                                Most popular
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col pt-8">
                            <p class="pr-24 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                                {{ $isFree ? 'For early-stage teams' : 'For scaling hiring pipelines' }}
                            </p>
                            <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ $name }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">
                                {{ $description }}
                            </p>

                            <div class="mt-5 flex items-baseline gap-2">
                                <span class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white md:text-4xl">
                                    <span
                                        data-pricing-amount
                                        data-monthly="{{ $monthly }}"
                                        data-annual="{{ $annual }}">
                                        ${{ number_format($monthly, 0) }}
                                    </span>
                                </span>
                                <span class="flex items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-300">
                                    <span data-pricing-period data-monthly="month" data-annual="year">
                                        / {{ $period }}
                                    </span>
                                    <span
                                        data-pricing-save-badge
                                        class="hidden rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">
                                        Save 20%
                                    </span>
                                </span>
                            </div>

                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-emerald-600 dark:text-emerald-300" data-pricing-billed>
                                billed monthly — annual billing applies automatic savings
                            </p>

                            <p class="mt-1 hidden text-xs font-medium text-slate-500 dark:text-slate-300" data-pricing-equivalent>
                                ≈ $0/mo when billed annually
                            </p>

                            @if($trialDays > 0 && !$isFree)
                                <p class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-300">
                                    {{ $trialDays }}-day free trial on monthly billing. Explore every feature before you commit.
                                </p>
                            @endif

                            <ul class="mt-5 flex flex-1 flex-col gap-2">
                                @foreach((array) data_get($plan, 'features', []) as $feature)
                                    <li class="flex items-start gap-2">
                                        <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            <i data-lucide="check" class="h-3 w-3"></i>
                                        </span>
                                        <span class="text-sm text-slate-700 dark:text-slate-200">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-6">
                                @auth
                                    @if(auth()->user()?->company)
                                        <form method="POST" action="{{ route('billing.checkout') }}" class="space-y-2">
                                            @csrf
                                            <input type="hidden" name="plan" value="{{ data_get($plan, 'key') }}">
                                            <input type="hidden" name="billing_cycle" value="monthly" data-billing-cycle-input>
                                            <input
                                                name="voucher_code"
                                                class="h-9 w-full rounded-lg border border-slate-300 bg-white px-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                                placeholder="Voucher code (optional)">
                                            <button
                                                class="inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold tracking-tight transition {{ $isHighlight ? 'bg-brand-600 text-white hover:bg-brand-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-950' : 'border border-slate-300 bg-white text-slate-900 hover:border-brand-400 hover:bg-brand-50 dark:border-slate-600 dark:bg-slate-950/40 dark:text-white dark:hover:border-brand-400 dark:hover:bg-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-950' }}">
                                                {{ $isFree ? 'Activate Basic (Free)' : data_get($plan, 'cta') }}
                                            </button>
                                        </form>
                                    @else
                                        <a
                                            href="{{ route('dashboard') }}"
                                            class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold tracking-tight text-slate-900 transition hover:border-brand-400 hover:bg-brand-50 dark:border-slate-600 dark:bg-slate-950/40 dark:text-white dark:hover:border-brand-400 dark:hover:bg-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-950">
                                            Open dashboard
                                        </a>
                                    @endif
                                @else
                                    @php($ctaHref = data_get($plan, 'key') === 'enterprise' ? route('public.contact') : route('register'))
                                    <a
                                        href="{{ $ctaHref }}"
                                        class="inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold tracking-tight transition {{ $isHighlight ? 'bg-brand-600 text-white hover:bg-brand-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-950' : 'border border-slate-300 bg-white text-slate-900 hover:border-brand-400 hover:bg-brand-50 dark:border-slate-600 dark:bg-slate-950/40 dark:text-white dark:hover:border-brand-400 dark:hover:bg-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-950' }}">
                                        {{ data_get($plan, 'cta') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <aside class="space-y-6 rounded-3xl border border-slate-200 bg-white/90 p-6 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                    How NovaHire pricing works
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Every plan is backed by the same secure multi-tenant infrastructure. Pricing is primarily based on the
                    volume of roles, AI analyses, and team members you manage in NovaHire.
                </p>

                <div class="space-y-3 rounded-2xl bg-slate-50 p-4 text-xs text-slate-600 dark:bg-slate-950/40 dark:text-slate-200">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Key rules</h3>
                    <ul class="mt-2 space-y-2">
                        <li class="flex gap-2">
                            <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-100">
                                1
                            </span>
                            <span class="flex-1">
                                <strong>Basic is always free.</strong> It’s ideal for solo recruiters or testing NovaHire in a live hiring
                                process without adding payment details.
                            </span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-100">
                                2
                            </span>
                            <span class="flex-1">
                                <strong>Trials apply to Individual and above.</strong> Your trial duration is currently set to
                                <span class="font-semibold">{{ (int) ($billingTrialDays ?? 30) }} days</span> on monthly billing.
                            </span>
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-100">
                                3
                            </span>
                            <span class="flex-1">
                                <strong>Auto-renew is on by default.</strong> You can cancel or downgrade at any time from the billing
                                section in Settings; changes take effect at the next renewal.
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-3 rounded-2xl bg-slate-50 p-4 text-xs text-slate-600 dark:bg-slate-950/40 dark:text-slate-200">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                        Vouchers and promotions
                    </h3>
                    <p>
                        Voucher codes are applied at checkout and validated via Stripe. They can unlock free months, percent-based
                        discounts, or plan-specific promotions.
                    </p>

                    @if(!empty($activeVouchers))
                        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900/60">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 bg-slate-50 text-left font-semibold dark:border-slate-700 dark:bg-slate-900">
                                        <th class="px-3 py-2">Voucher</th>
                                        <th class="px-3 py-2">Discount</th>
                                        <th class="px-3 py-2">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeVouchers as $voucher)
                                        <tr class="border-b border-slate-100 dark:border-slate-800">
                                            <td class="px-3 py-2 font-semibold">{{ $voucher->code }}</td>
                                            <td class="px-3 py-2">
                                                {{ $voucher->type === 'percent' ? rtrim(rtrim(number_format((float) $voucher->value, 2), '0'), '.') . '%' : '$' . number_format((float) $voucher->value, 2) }}
                                            </td>
                                            <td class="px-3 py-2">
                                                {{ $voucher->description ?: 'General discount' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="space-y-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 p-4 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-950/30 dark:text-slate-200">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                        Need enterprise pricing?
                    </h3>
                    <p>
                        If you manage multiple entities, complex approval workflows, or strict data residency requirements, our team
                        can design a custom plan that mirrors your governance model.
                    </p>
                    <a
                        href="{{ route('public.contact') }}"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-500 dark:text-brand-300 dark:hover:text-brand-200">
                        Talk to sales
                        <i data-lucide="arrow-right" class="h-3 w-3"></i>
                    </a>
                </div>
            </aside>
        </div>
    </section>
@endsection
