@php
    $testimonials = [
        [
            'quote' => 'We cut screening time by over 60% and improved shortlist quality in the first week. The AI output is practical and easy for our hiring managers to trust.',
            'name' => 'Sarah Chen',
            'designation' => 'Head of Talent',
            'src' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=720&q=70',
        ],
        [
            'quote' => 'The ranking flow feels built for real recruiting teams. We spend less time sorting CVs and more time interviewing the right candidates.',
            'name' => 'Marcus Lee',
            'designation' => 'Recruiting Lead',
            'src' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=720&q=70',
        ],
        [
            'quote' => 'Collaboration between HR and hiring managers is finally clean. Pipeline visibility, interview coordination, and AI notes all live in one place.',
            'name' => 'Elena Roy',
            'designation' => 'HR Director',
            'src' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=720&q=70',
        ],
        [
            'quote' => 'I now review only the strongest profiles. The platform removed repetitive filtering work and made hiring discussions much faster.',
            'name' => 'David Kim',
            'designation' => 'Hiring Manager',
            'src' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=720&q=70',
        ],
    ];
@endphp

<section id="reviews"
    class="cv-auto nh-section overflow-hidden border-y border-slate-200 bg-[radial-gradient(900px_circle_at_15%_20%,rgba(70,95,255,0.08),transparent_45%),radial-gradient(700px_circle_at_85%_18%,rgba(14,165,233,0.10),transparent_42%),linear-gradient(180deg,rgba(255,255,255,0.96),rgba(248,250,252,0.94))] dark:border-slate-800 dark:bg-[radial-gradient(900px_circle_at_15%_20%,rgba(70,95,255,0.16),transparent_45%),radial-gradient(700px_circle_at_85%_18%,rgba(14,165,233,0.12),transparent_42%),linear-gradient(180deg,rgba(2,6,23,0.96),rgba(2,6,23,0.98))]"
    x-data="{
        testimonials: {{ Js::from($testimonials) }},
        activeIndex: 0,
        autoplay: true,
        hoverPrev: false,
        hoverNext: false,
        containerWidth: 1200,
        intervalId: null,
        init() {
            this.measure();
            this.startAutoplay();
            this.$nextTick(() => {
                window.addEventListener('resize', () => this.measure(), { passive: true });
            });
        },
        measure() {
            this.containerWidth = this.$refs.imageContainer ? this.$refs.imageContainer.offsetWidth : 1200;
        },
        calculateGap() {
            const width = this.containerWidth;
            const minWidth = 1024;
            const maxWidth = 1456;
            const minGap = 60;
            const maxGap = 86;
            if (width <= minWidth) return minGap;
            if (width >= maxWidth) return Math.max(minGap, maxGap + 0.06018 * (width - maxWidth));
            return minGap + (maxGap - minGap) * ((width - minWidth) / (maxWidth - minWidth));
        },
        activeTestimonial() {
            return this.testimonials[this.activeIndex] || this.testimonials[0];
        },
        next() {
            this.activeIndex = (this.activeIndex + 1) % this.testimonials.length;
            this.resetAutoplay();
        },
        prev() {
            this.activeIndex = (this.activeIndex - 1 + this.testimonials.length) % this.testimonials.length;
            this.resetAutoplay();
        },
        startAutoplay() {
            if (!this.autoplay) return;
            this.stopAutoplay();
            this.intervalId = setInterval(() => {
                this.activeIndex = (this.activeIndex + 1) % this.testimonials.length;
            }, 5000);
        },
        stopAutoplay() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        },
        resetAutoplay() {
            if (!this.autoplay) return;
            this.startAutoplay();
        },
        imageStyle(index) {
            const gap = this.calculateGap();
            const lift = gap * 0.8;
            const total = this.testimonials.length;
            const isActive = index === this.activeIndex;
            const isLeft = (this.activeIndex - 1 + total) % total === index;
            const isRight = (this.activeIndex + 1) % total === index;

            if (isActive) {
                return 'transform: translateX(0px) translateY(0px) scale(1) rotateY(0deg); opacity:1; z-index:3;';
            }
            if (isLeft) {
                return `transform: translateX(-${gap}px) translateY(-${lift}px) scale(0.85) rotateY(15deg); opacity:1; z-index:2;`;
            }
            if (isRight) {
                return `transform: translateX(${gap}px) translateY(-${lift}px) scale(0.85) rotateY(-15deg); opacity:1; z-index:2;`;
            }

            return 'opacity:0; z-index:1; transform: scale(0.88); pointer-events:none;';
        },
    }"
    x-init="init()"
    @mouseenter="stopAutoplay()"
    @mouseleave="startAutoplay()"
    @keydown.left.window="prev()"
    @keydown.right.window="next()">
    <div class="nh-container">
        <div class="mb-10 text-center">
            <p data-animate="reveal" class="nh-reveal nh-eyebrow">Customer Reviews</p>
            <h2 data-animate="reveal" data-delay="1" class="nh-reveal nh-h2">Loved by hiring teams</h2>
            <p data-animate="reveal" data-delay="2" class="nh-reveal nh-lead mx-auto max-w-2xl">
                Real feedback from recruiters and HR leaders using {{ config('app.name', 'NovaHire') }}.
            </p>
        </div>

        <div class="mx-auto max-w-6xl">
            <div class="grid items-center gap-10 md:grid-cols-2 md:gap-16">
                <div class="relative mx-auto w-full max-w-xl">
                    <div class="pointer-events-none absolute inset-x-8 top-10 h-64 rounded-[2rem] bg-gradient-to-b from-brand-500/18 via-sky-400/10 to-transparent blur-3xl"></div>
                    <div class="circular-testimonial-stack relative mx-auto h-[23rem] w-full max-w-[30rem]" x-ref="imageContainer">
                        <template x-for="(testimonial, index) in testimonials" :key="testimonial.src">
                            <img
                                :src="testimonial.src"
                                :alt="testimonial.name"
                                class="circular-testimonial-image"
                                :style="imageStyle(index)"
                                :loading="index === activeIndex ? 'eager' : 'lazy'"
                                decoding="async"
                                width="720"
                                height="720">
                        </template>
                    </div>
                </div>

                <div class="flex flex-col justify-between">
                    <div class="min-h-[16rem]">
                        <div x-transition:enter="transition duration-300 ease-out"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition duration-200 ease-in"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-4"
                            :key="activeIndex">
                            <h3 class="text-[1.9rem] font-bold tracking-tight text-slate-950 dark:text-white"
                                x-text="activeTestimonial().name"></h3>
                            <p class="mt-1 text-base font-medium text-slate-500 dark:text-slate-300"
                                x-text="activeTestimonial().designation"></p>

                            <p class="mt-8 text-lg leading-8 text-slate-700 dark:text-slate-100">
                                <template x-for="(word, wordIndex) in activeTestimonial().quote.split(' ')" :key="`${activeIndex}-${wordIndex}`">
                                    <span class="circular-testimonial-word"
                                        :style="`transition-delay:${wordIndex * 25}ms`"
                                        x-text="`${word} `"></span>
                                </template>
                            </p>
                        </div>
                    </div>

                    <div class="mt-10 flex items-center gap-4">
                        <button type="button"
                            class="circular-testimonial-arrow"
                            :class="{ 'circular-testimonial-arrow-hover': hoverPrev }"
                            @mouseenter="hoverPrev = true"
                            @mouseleave="hoverPrev = false"
                            @click="prev()"
                            aria-label="Previous testimonial">
                            <i data-lucide="arrow-left" class="h-5 w-5"></i>
                        </button>
                        <button type="button"
                            class="circular-testimonial-arrow"
                            :class="{ 'circular-testimonial-arrow-hover': hoverNext }"
                            @mouseenter="hoverNext = true"
                            @mouseleave="hoverNext = false"
                            @click="next()"
                            aria-label="Next testimonial">
                            <i data-lucide="arrow-right" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
