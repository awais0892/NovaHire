@extends('layouts.public')

@push('head')
    <style>
        .features-grid-bg {
            background-image: linear-gradient(rgba(15, 23, 42, 0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(15, 23, 42, 0.06) 1px, transparent 1px);
            background-size: 22px 22px;
        }

        .dark .features-grid-bg {
            background-image: linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
        }

        .features-pill-active {
            background: linear-gradient(95deg, rgb(5 150 105), rgb(8 145 178));
            color: #fff;
            border-color: transparent;
        }
    </style>
@endpush

@section('content')
    @php
        $features = collect($features ?? data_get($content ?? [], 'features', []));
        $modules = collect($featureModules ?? [])->values();
        $media = collect($featuresMedia ?? [])->values();
        $timeline = collect($adoptionPlan ?? [])->values();
        $firstModule = $modules->first();
        $firstMedia = $media->first();
        $firstRole = collect($roleCards ?? [])->first();
    @endphp

    <section class="space-y-10" id="features-page" data-features-page>
        <div class="public-silk-shell relative overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/60">
            <x-ui.public-silk tone="features" intensity="1.08" />
            <div class="absolute inset-0 features-grid-bg opacity-28"></div>
            <div class="public-silk-content grid gap-6 p-8 lg:grid-cols-[1.2fr_0.8fr] lg:p-10">
                <div>
                    <p class="public-silk-chip">Feature Stack</p>
                    <h1 class="mt-3 text-4xl font-bold text-slate-900 dark:text-white lg:text-5xl">Platform Features</h1>
                    <p class="mt-4 max-w-3xl text-lg text-slate-700 dark:text-slate-200">
                        Built for recruiters, hiring managers, and candidates with role-specific workflows, AI intelligence, and live operational visibility.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('public.product') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">View Product Tour</a>
                        <a href="{{ route('public.pricing') }}" class="rounded-lg border border-slate-300 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">Compare Plans</a>
                        <a href="{{ route('register') }}" class="rounded-lg border border-slate-300 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">Start Free</a>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/25 bg-white/78 p-5 shadow-sm backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-900/80">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">Live System Pulse</h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs text-slate-500 dark:text-slate-300">Active Jobs</p>
                            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ number_format($platformMetrics['active_jobs'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs text-slate-500 dark:text-slate-300">Applications</p>
                            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ number_format($platformMetrics['applications'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs text-slate-500 dark:text-slate-300">Candidates</p>
                            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ number_format($platformMetrics['candidates'] ?? 0) }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs text-slate-500 dark:text-slate-300">Scheduled Interviews</p>
                            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ number_format($platformMetrics['scheduled_interviews'] ?? 0) }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-slate-600 dark:text-slate-300">Current average AI score: <span class="font-semibold text-slate-900 dark:text-white">{{ $platformMetrics['avg_ai_score'] ?? 0 }}%</span></p>
                </div>
            </div>
        </div>

        <div class="cv-auto grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900/60" data-modules>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Interactive Modules</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Explore capabilities by hiring objective</h2>

                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach($modules as $index => $module)
                        <button type="button"
                            class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-brand-400 dark:border-slate-700 dark:text-slate-200"
                            data-module-pill
                            data-key="{{ data_get($module, 'key') }}"
                            @if($index === 0) data-active="1" @endif>
                            {{ data_get($module, 'title') }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-900">
                    <h3 data-module-title class="text-lg font-semibold text-slate-900 dark:text-white">{{ data_get($firstModule, 'title') }}</h3>
                    <p data-module-desc class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ data_get($firstModule, 'desc') }}</p>
                    <div data-module-items class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach((array) data_get($firstModule, 'items', []) as $item)
                            <article class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ data_get($item, 'title') }}</h4>
                                <p class="mt-2 text-xs leading-6 text-slate-600 dark:text-slate-300">{{ data_get($item, 'desc') }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>

                <script type="application/json" data-modules-json>@json($modules)</script>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900/60" data-media-showcase>
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Visual Context</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Feature storytelling assets</h2>
                    </div>
                    <a href="https://www.vecteezy.com" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">Source: Vecteezy</a>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                    @if(($firstMedia['type'] ?? 'image') === 'video')
                        <video data-media-video controls preload="none" class="h-72 w-full object-cover">
                            <source src="{{ data_get($firstMedia, 'src') }}" type="video/mp4">
                        </video>
                        <img data-media-image src="" alt="" class="hidden h-72 w-full object-cover">
                    @else
                        <img data-media-image src="{{ data_get($firstMedia, 'src') }}" alt="{{ data_get($firstMedia, 'title') }}" class="h-72 w-full object-cover" loading="lazy" decoding="async">
                        <video data-media-video controls preload="none" class="hidden h-72 w-full object-cover">
                            <source src="" type="video/mp4">
                        </video>
                    @endif
                    <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                        <p data-media-title class="text-sm font-semibold text-slate-900 dark:text-white">{{ data_get($firstMedia, 'title') }}</p>
                        <p data-media-caption class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ data_get($firstMedia, 'caption') }}</p>
                        <a data-media-source href="{{ data_get($firstMedia, 'source_url') }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-xs font-semibold text-brand-700 hover:underline dark:text-brand-300">Open source link</a>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach($media as $item)
                        <button type="button" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-left hover:border-brand-400 dark:border-slate-700 dark:bg-slate-900" data-media-item
                            data-type="{{ data_get($item, 'type', 'image') }}"
                            data-src="{{ data_get($item, 'src') }}"
                            data-title="{{ data_get($item, 'title') }}"
                            data-caption="{{ data_get($item, 'caption') }}"
                            data-source="{{ data_get($item, 'source_url') }}">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ data_get($item, 'title') }}</p>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ data_get($item, 'caption') }}</p>
                        </button>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="cv-auto grid gap-6 lg:grid-cols-[1fr_1fr]">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900/60" data-role-focus>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Role Focus</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">See value by user role</h2>

                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach(collect($roleCards ?? [])->values() as $index => $role)
                        <button type="button" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200" data-role-pill data-index="{{ $index }}" @if($index === 0) data-active="1" @endif>
                            {{ data_get($role, 'title') }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-900">
                    <h3 data-role-title class="text-lg font-semibold text-slate-900 dark:text-white">{{ data_get($firstRole, 'title') }}</h3>
                    <ul data-role-points class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                        @foreach((array) data_get($firstRole, 'points', []) as $point)
                            <li>- {{ $point }}</li>
                        @endforeach
                    </ul>
                </div>

                <script type="application/json" data-roles-json>@json(collect($roleCards ?? [])->values())</script>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900/60" data-timeline>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">Adoption Plan</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">4-week rollout timeline</h2>
                <div class="mt-5 space-y-3">
                    @foreach($timeline as $item)
                        <article class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300">{{ data_get($item, 'phase') }}</p>
                            <h3 class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ data_get($item, 'title') }}</h3>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ data_get($item, 'detail') }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="cv-auto grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach($features as $feature)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900/60">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ data_get($feature, 'title') }}</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ data_get($feature, 'desc') }}</p>
                </article>
            @endforeach
        </div>

        <div class="cv-auto rounded-3xl border border-brand-200 bg-gradient-to-r from-brand-50 to-cyan-50 p-8 dark:border-brand-700/50 dark:from-brand-500/10 dark:to-cyan-500/10">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">See all features in action</h2>
            <p class="mt-2 text-slate-700 dark:text-slate-300">Run a complete hiring cycle from job publishing to interview decisions.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('public.product') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">View Product Tour</a>
                <a href="{{ route('register') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Create Account</a>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-features-page]');
            if (!root) return;

            // Modules switcher
            const modulesRoot = root.querySelector('[data-modules]');
            if (modulesRoot) {
                const pills = Array.from(modulesRoot.querySelectorAll('[data-module-pill]'));
                const titleEl = modulesRoot.querySelector('[data-module-title]');
                const descEl = modulesRoot.querySelector('[data-module-desc]');
                const itemsEl = modulesRoot.querySelector('[data-module-items]');
                const jsonEl = modulesRoot.querySelector('[data-modules-json]');
                let modules = [];

                try {
                    modules = JSON.parse((jsonEl && jsonEl.textContent) || '[]');
                } catch (e) {
                    modules = [];
                }

                const render = (key) => {
                    const module = modules.find((m) => m.key === key) || modules[0];
                    if (!module || !titleEl || !descEl || !itemsEl) return;

                    pills.forEach((pill) => {
                        const active = pill.getAttribute('data-key') === module.key;
                        pill.classList.toggle('features-pill-active', active);
                    });

                    titleEl.textContent = module.title || '';
                    descEl.textContent = module.desc || '';
                    itemsEl.innerHTML = '';

                    const list = Array.isArray(module.items) ? module.items : [];
                    if (!list.length) {
                        const empty = document.createElement('p');
                        empty.className = 'text-sm text-slate-500 dark:text-slate-300';
                        empty.textContent = 'No module items configured yet.';
                        itemsEl.appendChild(empty);
                        return;
                    }

                    list.forEach((item) => {
                        const card = document.createElement('article');
                        card.className = 'rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800/70';

                        const heading = document.createElement('h4');
                        heading.className = 'text-sm font-semibold text-slate-900 dark:text-white';
                        heading.textContent = (item && item.title) || '';

                        const detail = document.createElement('p');
                        detail.className = 'mt-2 text-xs leading-6 text-slate-600 dark:text-slate-300';
                        detail.textContent = (item && item.desc) || '';

                        card.appendChild(heading);
                        card.appendChild(detail);
                        itemsEl.appendChild(card);
                    });
                };

                pills.forEach((pill) => pill.addEventListener('click', () => render(pill.getAttribute('data-key'))));
                if (modules.length) render(modules[0].key);
            }

            // Media showcase
            const mediaRoot = root.querySelector('[data-media-showcase]');
            if (mediaRoot) {
                const imageEl = mediaRoot.querySelector('[data-media-image]');
                const videoEl = mediaRoot.querySelector('[data-media-video]');
                const videoSource = videoEl ? videoEl.querySelector('source') : null;
                const titleEl = mediaRoot.querySelector('[data-media-title]');
                const captionEl = mediaRoot.querySelector('[data-media-caption]');
                const sourceEl = mediaRoot.querySelector('[data-media-source]');
                const items = Array.from(mediaRoot.querySelectorAll('[data-media-item]'));

                const activate = (button) => {
                    if (!button || !imageEl || !videoEl || !titleEl || !captionEl || !sourceEl) return;
                    const type = button.getAttribute('data-type') || 'image';
                    const src = button.getAttribute('data-src') || '';

                    items.forEach((item) => item.classList.remove('ring-2', 'ring-brand-300', 'border-brand-400'));
                    button.classList.add('ring-2', 'ring-brand-300', 'border-brand-400');

                    if (type === 'video') {
                        imageEl.classList.add('hidden');
                        videoEl.classList.remove('hidden');
                        if (videoSource) {
                            videoSource.src = src;
                            videoEl.load();
                        }
                    } else {
                        videoEl.pause();
                        videoEl.classList.add('hidden');
                        imageEl.classList.remove('hidden');
                        imageEl.src = src;
                        imageEl.alt = button.getAttribute('data-title') || '';
                    }

                    titleEl.textContent = button.getAttribute('data-title') || '';
                    captionEl.textContent = button.getAttribute('data-caption') || '';
                    sourceEl.href = button.getAttribute('data-source') || '#';
                };

                items.forEach((item, index) => {
                    item.addEventListener('click', () => activate(item));
                    if (index === 0) activate(item);
                });
            }

            // Role focus switcher
            const roleRoot = root.querySelector('[data-role-focus]');
            if (roleRoot) {
                const jsonEl = roleRoot.querySelector('[data-roles-json]');
                const titleEl = roleRoot.querySelector('[data-role-title]');
                const pointsEl = roleRoot.querySelector('[data-role-points]');
                const pills = Array.from(roleRoot.querySelectorAll('[data-role-pill]'));
                let roles = [];

                try {
                    roles = JSON.parse((jsonEl && jsonEl.textContent) || '[]');
                } catch (e) {
                    roles = [];
                }

                const renderRole = (index) => {
                    const i = Number(index || 0);
                    const role = roles[i] || roles[0];
                    if (!role || !titleEl || !pointsEl) return;

                    pills.forEach((pill) => {
                        const active = Number(pill.getAttribute('data-index')) === i;
                        pill.classList.toggle('features-pill-active', active);
                    });

                    titleEl.textContent = role.title || '';
                    pointsEl.innerHTML = '';
                    (Array.isArray(role.points) ? role.points : []).forEach((point) => {
                        const li = document.createElement('li');
                        li.textContent = `- ${point || ''}`;
                        pointsEl.appendChild(li);
                    });
                };

                pills.forEach((pill) => pill.addEventListener('click', () => renderRole(pill.getAttribute('data-index'))));
                if (roles.length) renderRole(0);
            }
        });
    </script>
@endsection
