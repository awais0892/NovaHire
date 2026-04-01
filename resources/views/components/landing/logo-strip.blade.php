@props(['logos' => []])

@php
    $defaultLogos = [
        'https://cdn.simpleicons.org/google',
        'https://cdn.simpleicons.org/microsoft',
        'https://cdn.simpleicons.org/amazon',
        'https://cdn.simpleicons.org/slack',
        'https://cdn.simpleicons.org/spotify',
        'https://cdn.simpleicons.org/notion',
    ];
    $displayLogos = collect($logos)->filter()->values()->all();
    if (count($displayLogos) === 0) {
        $displayLogos = $defaultLogos;
    }
@endphp

<section class="relative overflow-hidden border-y border-slate-200/70 bg-white/60 py-14 backdrop-blur dark:border-slate-800/80 dark:bg-slate-950/60">
    <div class="pointer-events-none absolute inset-0 opacity-40">
        <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-brand-500/10 blur-3xl"></div>
        <div class="absolute -right-24 -bottom-10 h-72 w-72 rounded-full bg-emerald-400/10 blur-3xl"></div>
    </div>
    <div class="relative nh-container">
        <p data-animate="reveal" class="nh-reveal text-center text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
            Trusted by modern hiring teams</p>
        <div class="mt-7 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($displayLogos as $logo)
                <div
                    data-animate="reveal"
                    class="nh-reveal group flex h-16 items-center justify-center rounded-2xl border border-slate-200 bg-white/70 p-4 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:bg-white hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/50 dark:hover:bg-slate-900/70">
                    <img src="{{ $logo }}" alt="Partner logo"
                        loading="lazy"
                        decoding="async"
                        class="max-h-8 w-auto opacity-75 grayscale transition duration-300 group-hover:opacity-100 group-hover:grayscale-0 group-hover:drop-shadow-[0_0_12px_rgba(99,102,241,0.45)]">
                </div>
            @endforeach
        </div>
    </div>
</section>
