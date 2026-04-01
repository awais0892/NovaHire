@props(['roles' => []])

<section id="roles" class="nh-section border-y border-slate-200 bg-white/70 backdrop-blur dark:border-slate-800 dark:bg-slate-950/60">
    <div class="nh-container">
        <p data-animate="reveal" class="nh-reveal nh-eyebrow">Role Experience</p>
        <h2 data-animate="reveal" data-delay="1" class="nh-reveal nh-h2">Designed for every hiring role</h2>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($roles as $card)
                <div
                    data-animate="reveal"
                    class="nh-reveal nh-card bg-[radial-gradient(110%_120%_at_0%_0%,rgba(70,95,255,0.10),transparent_55%),radial-gradient(110%_120%_at_100%_0%,rgba(16,185,129,0.10),transparent_55%)] p-6">
                    <h3 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ data_get($card, 'title') }}</h3>
                    <ul class="mt-4 space-y-2.5 text-base text-slate-600 dark:text-slate-100">
                        @foreach((array) data_get($card, 'points', []) as $point)
                            <li class="flex items-center gap-2">
                                <i data-lucide="check-circle-2" class="h-4 w-4 text-brand-500"></i>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>