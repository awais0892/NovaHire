@props(['plans' => []])

@php
    $normalizeMoney = function ($value): float {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9.]/', '', (string) $value);
        return $clean === '' ? 0.0 : (float) $clean;
    };

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
@endphp

<section id="pricing" class="nh-section cv-auto" data-pricing-widget>
    <div class="nh-container">
        <div class="mb-12 flex flex-col items-center text-center">
            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300">
                Pricing
            </span>
            <h2 class="mt-4 text-4xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-5xl">
                Simple, transparent plans for every team
            </h2>
            <p class="mx-auto mt-4 max-w-3xl whitespace-pre-line text-lg text-slate-600 dark:text-slate-300">
                Start free and scale into a full AI-powered hiring stack as your team grows.
                All plans include secure multi-tenant infrastructure, smart candidate matching, and human support.
            </p>
        </div>

        <div class="mb-10 flex flex-wrap items-center justify-center gap-4">
            <div class="pricing-billing-switch inline-flex items-center rounded-full border border-slate-200 bg-white/80 p-1 text-sm font-medium shadow-sm backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/80" data-pricing-switch>
                <span class="pricing-billing-switch-indicator" data-pricing-switch-indicator aria-hidden="true"></span>
                <button
                    type="button"
                    data-pricing-billing="monthly"
                    aria-pressed="true"
                    class="pricing-billing-button is-active rounded-full px-4 py-2 text-xs font-semibold md:px-5">
                    Monthly
                </button>
                <button
                    type="button"
                    data-pricing-billing="annual"
                    aria-pressed="false"
                    class="pricing-billing-button rounded-full px-4 py-2 text-xs font-semibold transition md:px-5">
                    <span class="flex items-center gap-1.5">
                        Annual
                        <span class="pricing-billing-chip">(Save 20%)</span>
                    </span>
                </button>
            </div>
            <p class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-300">
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                Change or cancel any time. No hidden fees.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            @foreach(collect($plans)->values() as $index => $plan)
                @php
                    $key = strtolower((string) data_get($plan, 'key', data_get($plan, 'name', '')));
                    $name = (string) data_get($plan, 'name', ucfirst($key));
                    $description = (string) data_get($plan, 'desc', data_get($plan, 'description', 'Plan built for your hiring workflow.'));
                    $period = (string) data_get($plan, 'interval', 'month');
                    $monthly = (int) data_get($plan, 'monthly_cents', 0) > 0
                        ? ((int) data_get($plan, 'monthly_cents', 0) / 100)
                        : $normalizeMoney(data_get($plan, 'price', 0));
                    $annual = (int) data_get($plan, 'annual_cents', 0) > 0
                        ? ((int) data_get($plan, 'annual_cents', 0) / 100)
                        : ($monthly > 0 ? round($monthly * 12 * 0.8) : 0);
                    $isPopular = (bool) data_get($plan, 'highlight', false) || str_contains($key, 'pro');
                    $isFree = $key === 'free' || $monthly <= 0;
                    $isEnterprise = str_contains($key, 'enterprise');
                    $isUpgradeForm = false;
                    
                    $cta = (string) data_get($plan, 'cta', ($isEnterprise ? 'Contact Sales' : 'Get Started'));
                    $href = $isEnterprise ? route('public.contact') : route('register');

                    if (auth()->check()) {
                        if (auth()->user()->hasRole('super_admin')) {
                            $cta = 'Go to Dashboard';
                            $href = route('dashboard');
                        } elseif (!$isEnterprise) {
                            $isUpgradeForm = true;
                            $cta = 'Upgrade';
                        }
                    }

                    $limits = (array) data_get($plan, 'limits', []);
                    $limitBullets = collect($limits)
                        ->map(fn ($value, $limitKey) => ($limitLabels[$limitKey] ?? ucfirst(str_replace('_', ' ', (string) $limitKey))) . ': ' . $formatLimit($value))
                        ->values()
                        ->all();

                    $fallbackBullets = match (true) {
                        $isFree => [
                            'Up to 2 active job postings',
                            'Basic analytics',
                            'Community support',
                        ],
                        str_contains($key, 'basic') => [
                            'Up to 10 projects',
                            '48-hour support response time',
                            'Team collaboration',
                        ],
                        str_contains($key, 'pro') => [
                            'Unlimited projects',
                            'Advanced analytics',
                            'Priority support',
                            'Custom integrations',
                        ],
                        str_contains($key, 'enterprise') => [
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

                    $features = array_slice((array) (data_get($plan, 'features') ?? data_get($plan, 'bullets') ?? (count($limitBullets) ? $limitBullets : $fallbackBullets)), 0, 8);

                    $stackClass = $isPopular
                        ? 'md:scale-[1.02]'
                        : '';
                @endphp

                <article
                    class="relative flex flex-col rounded-2xl border border-slate-200 bg-white/95 p-6 text-left shadow-sm ring-1 ring-transparent transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-brand-500/40 dark:border-slate-700 dark:bg-slate-900/80 {{ $stackClass }}">
                    @if($isPopular)
                        <div class="absolute right-4 top-4 inline-flex items-center whitespace-nowrap rounded-full bg-brand-600/95 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm z-10">
                            <i data-lucide="star" class="mr-1 h-3 w-3 fill-current"></i>
                            Most popular
                        </div>
                    @endif

                    <div class="flex flex-1 flex-col pt-8">
                        <p class="pr-24 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                            {{ $isFree ? 'Get started in minutes' : 'Designed for growing teams' }}
                        </p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ $name }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">
                            {{ $description }}
                        </p>

                        <div class="mt-6 flex items-baseline gap-2">
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
                            billed monthly — switch to annual for automatic savings
                        </p>

                        <p class="mt-1 hidden text-xs font-medium text-slate-500 dark:text-slate-300" data-pricing-equivalent>
                            ≈ $0/mo when billed annually
                        </p>

                        <ul class="mt-5 flex flex-1 flex-col gap-2">
                            @foreach($features as $feature)
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        <i data-lucide="check" class="h-3 w-3"></i>
                                    </span>
                                    <span class="text-sm text-slate-700 dark:text-slate-200">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-6">
                            @if($isUpgradeForm)
                                <form action="{{ route('billing.checkout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $key }}">
                                    <input type="hidden" name="billing_cycle" value="monthly" data-billing-cycle-input>
                                    <button type="submit"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold tracking-tight transition {{ $isPopular ? 'bg-brand-600 text-white shadow-sm hover:bg-brand-500' : 'border border-slate-300 bg-white text-slate-900 hover:border-brand-400 hover:bg-brand-50 dark:border-slate-600 dark:bg-slate-950/40 dark:text-white dark:hover:border-brand-400 dark:hover:bg-slate-900' }}">
                                        {{ $cta }}
                                    </button>
                                </form>
                            @else
                                <a
                                    href="{{ $href }}"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold tracking-tight transition {{ $isPopular ? 'bg-brand-600 text-white shadow-sm hover:bg-brand-500' : 'border border-slate-300 bg-white text-slate-900 hover:border-brand-400 hover:bg-brand-50 dark:border-slate-600 dark:bg-slate-950/40 dark:text-white dark:hover:border-brand-400 dark:hover:bg-slate-900' }}">
                                    {{ $cta }}
                                </a>
                            @endif
                            @if($isFree)
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-300">
                                    No credit card required.
                                </p>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-10 grid gap-6 rounded-2xl border border-slate-200 bg-white/80 p-6 text-sm text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-200 md:grid-cols-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Fair, predictable billing</h3>
                <p class="mt-2">
                    Upgrade or downgrade in a few clicks. We prorate changes automatically so you only pay for what you use.
                </p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Secure by default</h3>
                <p class="mt-2">
                    Built on tenant-safe isolation, audited access controls, and GDPR-aware data retention policies.
                </p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Need something custom?</h3>
                <p class="mt-2">
                    Talk to us about volume hiring, custom SLAs, and integrations with your existing HR stack.
                </p>
            </div>
        </div>
    </div>
</section>
