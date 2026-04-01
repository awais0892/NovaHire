@php
    $stackItems = [
        [
            'id' => 1,
            'title' => 'AI CV Screening',
            'description' => 'Parse resumes, extract structured skills, and score candidates against role requirements in seconds.',
            'tag' => 'Recruiter',
            'icon' => 'brain-circuit',
            'href' => route('public.features'),
            'cta' => 'Explore AI',
            'tone' => 'from-brand-500 via-sky-500 to-cyan-400',
        ],
        [
            'id' => 2,
            'title' => 'Candidate Ranking',
            'description' => 'Move from raw applications to a defensible shortlist with explainable AI match signals.',
            'tag' => 'Shortlist',
            'icon' => 'list-ordered',
            'href' => route('public.product'),
            'cta' => 'View ranking flow',
            'tone' => 'from-emerald-500 via-teal-500 to-sky-500',
        ],
        [
            'id' => 3,
            'title' => 'Hiring Manager Review',
            'description' => 'Share score rationale, notes, and interview decisions in one role-based workspace.',
            'tag' => 'Collaboration',
            'icon' => 'users',
            'href' => route('public.features'),
            'cta' => 'See collaboration',
            'tone' => 'from-violet-500 via-fuchsia-500 to-brand-500',
        ],
        [
            'id' => 4,
            'title' => 'Interview Workflow',
            'description' => 'Coordinate invitations, reminders, and status changes without losing candidate context.',
            'tag' => 'Automation',
            'icon' => 'calendar-check-2',
            'href' => route('public.product'),
            'cta' => 'Open workflow',
            'tone' => 'from-amber-500 via-orange-500 to-rose-500',
        ],
        [
            'id' => 5,
            'title' => 'Recruitment Analytics',
            'description' => 'Track conversion, throughput, and team performance across every hiring stage.',
            'tag' => 'Insights',
            'icon' => 'bar-chart-3',
            'href' => route('public.pricing'),
            'cta' => 'See plans',
            'tone' => 'from-slate-700 via-slate-900 to-black',
        ],
    ];
@endphp

<section class="nh-section overflow-hidden"
    x-data="{
        items: {{ Js::from($stackItems) }},
        active: 0,
        hovering: false,
        containerWidth: 980,
        init() {
            this.measure();
            this.startAuto();
            window.addEventListener('resize', () => this.measure(), { passive: true });
        },
        measure() {
            this.containerWidth = this.$refs.stage ? this.$refs.stage.offsetWidth : 980;
        },
        maxOffset() {
            return window.innerWidth < 768 ? 1 : 2;
        },
        cardWidth() {
            if (window.innerWidth < 640) return Math.min(this.containerWidth - 24, 320);
            if (window.innerWidth < 1024) return Math.min(this.containerWidth - 40, 430);
            return Math.min(this.containerWidth - 120, 520);
        },
        cardHeight() {
            return window.innerWidth < 640 ? 360 : 330;
        },
        spacing() {
            return Math.max(16, Math.round(this.cardWidth() * 0.52));
        },
        stepDeg() {
            const maxOffset = this.maxOffset();
            return maxOffset > 0 ? 48 / maxOffset : 0;
        },
        signedOffset(index) {
            const len = this.items.length;
            const raw = index - this.active;
            const alt = raw > 0 ? raw - len : raw + len;
            return Math.abs(alt) < Math.abs(raw) ? alt : raw;
        },
        visible(index) {
            return Math.abs(this.signedOffset(index)) <= this.maxOffset();
        },
        style(index) {
            const off = this.signedOffset(index);
            const abs = Math.abs(off);
            const x = off * this.spacing();
            const y = abs * 12 + (off === 0 ? -22 : 0);
            const rotateZ = off * this.stepDeg();
            const rotateX = off === 0 ? 0 : 12;
            const scale = off === 0 ? 1.03 : 0.93;
            const z = -abs * 140;
            const opacity = this.visible(index) ? 1 : 0;
            const zIndex = 100 - abs;
            return `width:${this.cardWidth()}px;height:${this.cardHeight()}px;transform:translateX(${x}px) translateY(${y}px) translateZ(${z}px) rotateZ(${rotateZ}deg) rotateX(${rotateX}deg) scale(${scale});opacity:${opacity};z-index:${zIndex};`;
        },
        next() {
            this.active = (this.active + 1) % this.items.length;
            this.resetAuto();
        },
        prev() {
            this.active = (this.active - 1 + this.items.length) % this.items.length;
            this.resetAuto();
        },
        startAuto() {
            this.stopAuto();
            this.interval = setInterval(() => {
                if (!this.hovering) this.active = (this.active + 1) % this.items.length;
            }, 3200);
        },
        stopAuto() {
            if (this.interval) clearInterval(this.interval);
        },
        resetAuto() {
            this.startAuto();
        },
    }"
    x-init="init()"
    @mouseenter="hovering = true"
    @mouseleave="hovering = false"
    @keydown.left.window="prev()"
    @keydown.right.window="next()">
    <div class="nh-container">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p data-animate="reveal" class="nh-reveal nh-eyebrow">Product Walkthrough</p>
                <h2 data-animate="reveal" data-delay="1" class="nh-reveal nh-h2">See the AI recruiting flow in motion</h2>
                <p data-animate="reveal" data-delay="2" class="nh-reveal nh-lead max-w-2xl">
                    A stacked product carousel built around the workflows your recruiters, hiring managers, and candidates actually use.
                </p>
            </div>
            <div data-animate="reveal" data-delay="3"
                class="nh-reveal hidden rounded-full border border-slate-200 bg-white/70 px-5 py-2.5 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300 sm:block">
                Screening, ranking, interviews, analytics
            </div>
        </div>

        <div class="card-stack-shell rounded-[2rem] border border-slate-200/80 bg-white/70 p-4 shadow-sm backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-900/50 sm:p-6 lg:p-8">
            <div class="relative w-full" x-ref="stage" tabindex="0">
                <div class="card-stack-wash-top"></div>
                <div class="card-stack-wash-bottom"></div>

                <div class="relative flex min-h-[28rem] items-end justify-center overflow-hidden sm:min-h-[31rem]" style="perspective: 1100px;">
                    <template x-for="(item, index) in items" :key="item.id">
                        <article x-show="visible(index)"
                            x-transition:enter="transition duration-500 ease-out"
                            x-transition:enter-start="opacity-0 translate-y-8"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="card-stack-panel absolute bottom-0 cursor-pointer overflow-hidden rounded-[1.75rem] border-4 border-black/8 shadow-2xl dark:border-white/10"
                            :style="style(index)"
                            @click="active = index; resetAuto()">
                            <div class="card-stack-panel-inner h-full w-full"
                                :class="item.id === items[active].id ? 'ring-1 ring-white/20' : ''">
                                <div class="absolute inset-0 bg-gradient-to-br opacity-95" :class="item.tone"></div>
                                <div class="card-stack-mesh"></div>
                                <div class="card-stack-orb"></div>
                                <div class="card-stack-overlay"></div>

                                <div class="relative z-10 flex h-full flex-col justify-between p-6 sm:p-7">
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="inline-flex items-center rounded-full border border-white/18 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/85 backdrop-blur">
                                            <span x-text="item.tag"></span>
                                        </span>
                                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/14 bg-black/20 text-white/90 backdrop-blur">
                                            <i :data-lucide="item.icon" class="h-5 w-5"></i>
                                        </span>
                                    </div>

                                    <div>
                                        <h3 class="max-w-xs text-2xl font-semibold leading-tight tracking-tight text-white sm:text-[1.9rem]" x-text="item.title"></h3>
                                        <p class="mt-3 max-w-md text-sm leading-7 text-white/75 sm:text-base" x-text="item.description"></p>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-2 text-white/60">
                                            <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                                            <span class="text-xs font-medium uppercase tracking-[0.18em]">Live workflow</span>
                                        </div>
                                        <a :href="item.href"
                                            class="inline-flex items-center gap-2 rounded-full border border-white/14 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.12em] text-white transition hover:bg-white/18">
                                            <span x-text="item.cta"></span>
                                            <i data-lucide="square-arrow-out-up-right" class="h-4 w-4"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>

            <div class="mt-7 flex items-center justify-center gap-3">
                <template x-for="(item, index) in items" :key="`dot-${item.id}`">
                    <button type="button"
                        class="h-2.5 rounded-full transition-all duration-200"
                        :class="index === active ? 'w-8 bg-slate-900 dark:bg-white' : 'w-2.5 bg-slate-300 hover:bg-slate-400 dark:bg-white/25 dark:hover:bg-white/40'"
                        :aria-label="`Go to ${item.title}`"
                        @click="active = index; resetAuto()"></button>
                </template>
            </div>
        </div>
    </div>
</section>
