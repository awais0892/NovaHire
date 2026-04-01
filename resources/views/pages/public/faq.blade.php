@extends('layouts.public')

@push('head')
    <style>
        .faq-grid-bg {
            background-image: linear-gradient(rgba(15, 23, 42, 0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(15, 23, 42, 0.06) 1px, transparent 1px);
            background-size: 22px 22px;
        }

        .dark .faq-grid-bg {
            background-image: linear-gradient(rgba(148, 163, 184, 0.09) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.09) 1px, transparent 1px);
        }

        .faq-panel {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 260ms ease, opacity 220ms ease;
        }

        .faq-item[data-open="1"] .faq-panel {
            opacity: 1;
        }

        .faq-item[data-open="1"] .faq-icon {
            transform: rotate(180deg);
        }

        .faq-icon {
            transition: transform 220ms ease;
        }
    </style>
@endpush

@section('content')
    <section class="space-y-8" id="faq-page" data-faq-page>
        <div class="public-silk-shell relative overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/60">
            <x-ui.public-silk tone="features" intensity="0.96" />
            <div class="absolute inset-0 faq-grid-bg opacity-22"></div>

            <div class="public-silk-content p-8 lg:p-10">
                <p class="public-silk-chip">FAQ</p>
                <h1 class="mt-3 text-4xl font-bold text-slate-900 dark:text-white lg:text-5xl">Frequently Asked Questions</h1>
                <p class="mt-3 max-w-3xl text-lg text-slate-700 dark:text-slate-200">
                    Common questions from recruiters, hiring managers, admins, and candidates.
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <label class="relative min-w-[240px] flex-1 sm:max-w-md">
                        <span class="sr-only">Search FAQ</span>
                        <input
                            type="text"
                            placeholder="Search questions..."
                            class="w-full rounded-xl border border-slate-300 bg-white/95 px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none ring-brand-400 placeholder:text-slate-400 focus:ring-2 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200"
                            data-faq-search>
                    </label>
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" data-faq-expand-all>Expand all</button>
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" data-faq-collapse-all>Collapse all</button>
                </div>
            </div>
        </div>

        <div class="space-y-4" data-faq-list>
            @foreach(($faqs ?? []) as $index => $faq)
                <article class="faq-item rounded-2xl border border-slate-200 bg-white p-2 dark:border-slate-800 dark:bg-slate-900/60" data-faq-item data-open="{{ $index === 0 ? '1' : '0' }}" data-faq-text="{{ strtolower(($faq['q'] ?? '') . ' ' . ($faq['a'] ?? '')) }}">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-left"
                        data-faq-trigger
                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                        aria-controls="faq-answer-{{ $index }}">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $faq['q'] }}</h2>
                        <span class="faq-icon inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 dark:border-slate-700 dark:text-slate-300">
                            <i data-lucide="chevron-down" class="h-4 w-4"></i>
                        </span>
                    </button>

                    <div id="faq-answer-{{ $index }}" class="faq-panel" data-faq-panel>
                        <div class="px-4 pb-4 pt-1">
                            <p class="text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="rounded-2xl border border-brand-200 bg-brand-50/40 p-5 dark:border-brand-700/50 dark:bg-brand-500/10">
            <p class="text-sm font-semibold text-slate-900 dark:text-white">Still need help?</p>
            <a href="{{ route('public.contact') }}" class="mt-2 inline-flex text-sm font-semibold text-brand-700 hover:underline dark:text-brand-300">Contact support or sales</a>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-faq-page]');
            if (!root) return;

            const items = Array.from(root.querySelectorAll('[data-faq-item]'));
            const searchInput = root.querySelector('[data-faq-search]');
            const expandAllBtn = root.querySelector('[data-faq-expand-all]');
            const collapseAllBtn = root.querySelector('[data-faq-collapse-all]');

            const setPanelHeight = (item, open) => {
                const panel = item.querySelector('[data-faq-panel]');
                const trigger = item.querySelector('[data-faq-trigger]');
                if (!panel || !trigger) return;

                item.setAttribute('data-open', open ? '1' : '0');
                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');

                if (open) {
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                    setTimeout(() => {
                        if (item.getAttribute('data-open') === '1') {
                            panel.style.maxHeight = panel.scrollHeight + 'px';
                        }
                    }, 280);
                } else {
                    panel.style.maxHeight = '0px';
                }
            };

            const closeOthers = (exceptItem) => {
                items.forEach((item) => {
                    if (item === exceptItem) return;
                    setPanelHeight(item, false);
                });
            };

            items.forEach((item, index) => {
                const trigger = item.querySelector('[data-faq-trigger]');
                if (!trigger) return;

                setPanelHeight(item, index === 0);

                trigger.addEventListener('click', () => {
                    const isOpen = item.getAttribute('data-open') === '1';
                    if (!isOpen) closeOthers(item);
                    setPanelHeight(item, !isOpen);
                });
            });

            if (expandAllBtn) expandAllBtn.addEventListener('click', () => {
                items.forEach((item) => setPanelHeight(item, true));
            });

            if (collapseAllBtn) collapseAllBtn.addEventListener('click', () => {
                items.forEach((item) => setPanelHeight(item, false));
            });

            if (searchInput) searchInput.addEventListener('input', () => {
                const term = (searchInput.value || '').trim().toLowerCase();
                let visibleCount = 0;

                items.forEach((item) => {
                    const haystack = item.getAttribute('data-faq-text') || '';
                    const match = term === '' || haystack.includes(term);
                    item.classList.toggle('hidden', !match);
                    if (match) visibleCount += 1;
                });

                if (term !== '' && visibleCount > 0) {
                    const firstVisible = items.find((item) => !item.classList.contains('hidden'));
                    if (firstVisible) {
                        closeOthers(firstVisible);
                        setPanelHeight(firstVisible, true);
                    }
                }
            });

            window.addEventListener('resize', () => {
                items.forEach((item) => {
                    if (item.getAttribute('data-open') !== '1') return;
                    const panel = item.querySelector('[data-faq-panel]');
                    if (!panel) return;
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                });
            });
        });
    </script>

    @php
        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs ?? [])->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['q'] ?? '',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'] ?? '',
                ],
            ])->values()->all(),
        ];
    @endphp
    <script type="application/ld+json">
        {!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endsection
