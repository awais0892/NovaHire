import '../css/nova-landing.css';
import 'swiper/css';

import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import Swiper from 'swiper';
import { A11y, Navigation } from 'swiper/modules';
import { createIcons, icons as lucideIcons } from 'lucide';
import lottie, { type AnimationItem } from 'lottie-web';

import aiAnalysisLoader from './assets/lottie/ai-analysis-loader.json';

gsap.registerPlugin(ScrollTrigger);

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const supportsHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

const splitHeadingWords = (heading: HTMLElement): HTMLElement[] => {
    const text = heading.textContent?.trim() ?? '';
    if (!text) {
        return [];
    }

    heading.setAttribute('aria-label', text);
    heading.textContent = '';

    const words = text.split(/\s+/);
    const pieces: HTMLElement[] = [];

    words.forEach((word, index) => {
        const wrap = document.createElement('span');
        wrap.className = 'nh-split-word-wrap';

        const part = document.createElement('span');
        part.className = 'nh-split-word';
        part.setAttribute('aria-hidden', 'true');
        part.textContent = word;

        wrap.appendChild(part);
        heading.appendChild(wrap);

        if (index < words.length - 1) {
            heading.appendChild(document.createTextNode(' '));
        }

        pieces.push(part);
    });

    return pieces;
};

const initLucide = (): void => {
    createIcons({ icons: lucideIcons });
};

const initLenis = (): Lenis | null => {
    if (prefersReducedMotion) {
        return null;
    }

    const lenis = new Lenis({
        lerp: 0.11,
        smoothWheel: true,
        syncTouch: true,
    });

    const raf = (time: number): void => {
        lenis.raf(time);
        requestAnimationFrame(raf);
    };

    requestAnimationFrame(raf);
    lenis.on('scroll', ScrollTrigger.update);

    return lenis;
};

const initHeroReveal = (): void => {
    const hero = document.querySelector<HTMLElement>('[data-nh-hero]');
    if (!hero) {
        return;
    }

    const badge = hero.querySelector<HTMLElement>('[data-nh-hero-badge]');
    const heading = hero.querySelector<HTMLElement>('[data-split-heading]');
    const subcopy = hero.querySelector<HTMLElement>('[data-nh-hero-subcopy]');
    const actions = hero.querySelector<HTMLElement>('[data-nh-hero-actions]');
    const pills = hero.querySelector<HTMLElement>('[data-nh-hero-pills]');
    const visual = hero.querySelector<HTMLElement>('[data-nh-hero-visual]');

    if (!heading) {
        return;
    }

    const words = splitHeadingWords(heading);

    if (prefersReducedMotion) {
        gsap.set([badge, subcopy, actions, pills, visual, ...words], {
            autoAlpha: 1,
            y: 0,
            scale: 1,
        });
        return;
    }

    const timeline = gsap.timeline({ defaults: { ease: 'power3.out' } });

    if (badge) {
        timeline.from(badge, { autoAlpha: 0, y: 18, duration: 0.5 });
    }

    if (words.length) {
        timeline.from(
            words,
            {
                yPercent: 115,
                autoAlpha: 0,
                duration: 0.8,
                stagger: 0.05,
            },
            '-=0.12',
        );
    }

    if (subcopy) {
        timeline.from(subcopy, { autoAlpha: 0, y: 24, duration: 0.55 }, '-=0.35');
    }

    if (actions) {
        timeline.from(Array.from(actions.children), { autoAlpha: 0, y: 20, duration: 0.48, stagger: 0.1 }, '-=0.32');
    }

    if (pills) {
        timeline.from(Array.from(pills.children), { autoAlpha: 0, y: 12, duration: 0.4, stagger: 0.06 }, '-=0.28');
    }

    if (visual) {
        timeline.from(visual, { autoAlpha: 0, y: 36, scale: 0.96, duration: 0.9 }, '-=0.82');
    }
};

const initScrollReveals = (): void => {
    const targets = Array.from(document.querySelectorAll<HTMLElement>('[data-nh-reveal]')).filter(
        (el) => !el.closest('[data-nh-hero]'),
    );

    if (!targets.length) {
        return;
    }

    if (prefersReducedMotion) {
        gsap.set(targets, { autoAlpha: 1, y: 0, scale: 1 });
        return;
    }

    targets.forEach((target) => {
        gsap.to(target, {
            autoAlpha: 1,
            y: 0,
            scale: 1,
            duration: 0.85,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: target,
                start: 'top 86%',
                once: true,
            },
        });
    });
};

const initHeroVideoBehavior = (): void => {
    const video = document.querySelector<HTMLVideoElement>('[data-nh-hero-video]');
    if (!video) {
        return;
    }

    const stopAt = Number(video.dataset.stop ?? '2.1');
    let segmentFrame: number | null = null;

    const cancelSegment = (): void => {
        if (segmentFrame !== null) {
            cancelAnimationFrame(segmentFrame);
            segmentFrame = null;
        }
    };

    const playSegment = (): void => {
        cancelSegment();
        video.currentTime = 0;
        void video.play().catch(() => undefined);

        const monitor = (): void => {
            if (video.currentTime >= stopAt) {
                video.pause();
                cancelSegment();
                return;
            }
            segmentFrame = requestAnimationFrame(monitor);
        };

        segmentFrame = requestAnimationFrame(monitor);
    };

    const resumeLoop = (): void => {
        cancelSegment();
        if (video.paused) {
            void video.play().catch(() => undefined);
        }
    };

    if (supportsHover && !prefersReducedMotion) {
        video.addEventListener('mouseenter', playSegment);
        video.addEventListener('mouseleave', resumeLoop);
    }

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && entry.intersectionRatio > 0.2) {
                        resumeLoop();
                    } else {
                        cancelSegment();
                        video.pause();
                    }
                });
            },
            {
                threshold: [0, 0.2, 0.5],
            },
        );

        observer.observe(video);
    }
};

const initLottieDecorations = (): void => {
    const menuContainer = document.querySelector<HTMLElement>('[data-nh-lottie-menu]');
    const ribbonContainer = document.querySelector<HTMLElement>('[data-nh-lottie-ribbon]');

    let menuAnimation: AnimationItem | null = null;

    if (menuContainer) {
        menuAnimation = lottie.loadAnimation({
            container: menuContainer,
            renderer: 'svg',
            loop: false,
            autoplay: false,
            animationData: aiAnalysisLoader,
            rendererSettings: { progressiveLoad: true },
        });

        menuAnimation.setSpeed(1.2);
        menuAnimation.addEventListener('DOMLoaded', () => {
            menuAnimation?.goToAndStop(0, true);
        });
    }

    if (ribbonContainer) {
        const ribbonAnimation = lottie.loadAnimation({
            container: ribbonContainer,
            renderer: 'svg',
            loop: true,
            autoplay: !prefersReducedMotion,
            animationData: aiAnalysisLoader,
            rendererSettings: { progressiveLoad: true },
        });

        ribbonAnimation.setSpeed(0.35);

        if (prefersReducedMotion) {
            ribbonAnimation.addEventListener('DOMLoaded', () => {
                ribbonAnimation.goToAndStop(0, true);
            });
        }
    }

    const toggle = document.querySelector<HTMLButtonElement>('[data-nh-menu-toggle]');
    const panel = document.querySelector<HTMLElement>('[data-nh-mobile-panel]');
    const overlay = document.querySelector<HTMLElement>('[data-nh-mobile-overlay]');
    const close = document.querySelector<HTMLButtonElement>('[data-nh-mobile-close]');
    const links = Array.from(document.querySelectorAll<HTMLElement>('[data-nh-mobile-link]'));

    if (!toggle || !panel || !overlay) {
        return;
    }

    let isOpen = false;

    const syncMenuAnimation = (open: boolean): void => {
        if (!menuAnimation) {
            return;
        }

        menuAnimation.setDirection(open ? 1 : -1);
        menuAnimation.play();
    };

    const applyState = (open: boolean): void => {
        isOpen = open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');

        if (open) {
            panel.hidden = false;
            overlay.hidden = false;
            document.body.classList.add('nh-lock-scroll');

            requestAnimationFrame(() => {
                panel.classList.add('is-open');
                overlay.classList.add('is-open');
            });

            panel.focus();
        } else {
            panel.classList.remove('is-open');
            overlay.classList.remove('is-open');
            document.body.classList.remove('nh-lock-scroll');

            window.setTimeout(() => {
                if (!isOpen) {
                    panel.hidden = true;
                    overlay.hidden = true;
                }
            }, 340);
        }

        syncMenuAnimation(open);
    };

    toggle.addEventListener('click', () => applyState(!isOpen));
    overlay.addEventListener('click', () => applyState(false));
    close?.addEventListener('click', () => applyState(false));
    links.forEach((link) => link.addEventListener('click', () => applyState(false)));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            event.preventDefault();
            applyState(false);
            toggle.focus();
        }
    });
};

const initPillarSlider = (): void => {
    const slider = document.querySelector<HTMLElement>('[data-nh-pillars-swiper]');
    if (!slider) {
        return;
    }

    const swiper = new Swiper(slider, {
        modules: [Navigation, A11y],
        slidesPerView: 1.1,
        spaceBetween: 16,
        speed: 720,
        a11y: {
            enabled: true,
        },
        navigation: {
            prevEl: '[data-nh-pillars-prev]',
            nextEl: '[data-nh-pillars-next]',
        },
        breakpoints: {
            520: {
                slidesPerView: 1.35,
                spaceBetween: 18,
            },
            768: {
                slidesPerView: 2.05,
                spaceBetween: 20,
            },
            1080: {
                slidesPerView: 2.75,
                spaceBetween: 24,
            },
        },
    });

    if (supportsHover) {
        const cards = Array.from(slider.querySelectorAll<HTMLElement>('[data-nh-pillar-card]'));

        cards.forEach((card) => {
            let leaveTimer: number | null = null;

            card.addEventListener('mouseenter', () => {
                if (leaveTimer !== null) {
                    window.clearTimeout(leaveTimer);
                    leaveTimer = null;
                }

                card.classList.add('is-hovered');
                card.classList.remove('is-leaving');
            });

            card.addEventListener('mouseleave', () => {
                card.classList.remove('is-hovered');
                card.classList.add('is-leaving');

                leaveTimer = window.setTimeout(() => {
                    card.classList.remove('is-leaving');
                    leaveTimer = null;
                }, 420);
            });
        });

        slider.addEventListener('mouseenter', () => {
            swiper.allowTouchMove = false;
        });

        slider.addEventListener('mouseleave', () => {
            swiper.allowTouchMove = true;
        });
    }
};

const initProcessTabs = (): void => {
    const triggers = Array.from(document.querySelectorAll<HTMLButtonElement>('[data-nh-tab-trigger]'));
    const panels = Array.from(document.querySelectorAll<HTMLElement>('[data-nh-tab-panel]'));

    if (triggers.length < 2 || panels.length < 2) {
        return;
    }

    const activate = (value: string, focus = false): void => {
        triggers.forEach((trigger) => {
            const active = trigger.dataset.nhTabTrigger === value;
            trigger.classList.toggle('is-active', active);
            trigger.setAttribute('aria-selected', active ? 'true' : 'false');
            trigger.tabIndex = active ? 0 : -1;

            if (active && focus) {
                trigger.focus();
            }
        });

        panels.forEach((panel) => {
            const active = panel.dataset.nhTabPanel === value;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;

            if (active && !prefersReducedMotion) {
                gsap.fromTo(
                    panel,
                    { autoAlpha: 0, y: 18 },
                    { autoAlpha: 1, y: 0, duration: 0.38, ease: 'power2.out' },
                );
            }
        });
    };

    triggers.forEach((trigger, index) => {
        trigger.addEventListener('click', () => {
            activate(trigger.dataset.nhTabTrigger ?? 'before');
        });

        trigger.addEventListener('keydown', (event) => {
            if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) {
                return;
            }

            event.preventDefault();

            const lastIndex = triggers.length - 1;
            const nextIndex =
                event.key === 'ArrowRight'
                    ? (index + 1 > lastIndex ? 0 : index + 1)
                    : event.key === 'ArrowLeft'
                        ? (index - 1 < 0 ? lastIndex : index - 1)
                        : event.key === 'Home'
                            ? 0
                            : lastIndex;

            activate(triggers[nextIndex].dataset.nhTabTrigger ?? 'before', true);
        });
    });
};

const initStickyStories = (): void => {
    const section = document.querySelector<HTMLElement>('[data-nh-story-section]');
    if (!section) {
        return;
    }

    const cards = Array.from(section.querySelectorAll<HTMLElement>('[data-nh-story-card]'));
    const steps = Array.from(section.querySelectorAll<HTMLElement>('[data-nh-story-dot]'));

    if (!cards.length) {
        return;
    }

    const setActiveStep = (index: number): void => {
        cards.forEach((card, cardIndex) => {
            card.classList.toggle('is-current', cardIndex === index);
        });

        steps.forEach((step, stepIndex) => {
            step.classList.toggle('is-active', stepIndex === index);
        });
    };

    if (prefersReducedMotion) {
        setActiveStep(0);
        return;
    }

    gsap.set(cards, { autoAlpha: 0, y: 54, scale: 0.93 });
    gsap.set(cards[0], { autoAlpha: 1, y: 0, scale: 1 });

    const timeline = gsap.timeline({
        scrollTrigger: {
            trigger: section,
            start: 'top top',
            end: 'bottom bottom',
            scrub: 0.7,
        },
    });

    cards.slice(1).forEach((card, index) => {
        const previous = cards[index];
        const cue = index;

        timeline
            .to(
                previous,
                {
                    autoAlpha: 0.18,
                    y: -34,
                    scale: 0.9,
                    duration: 0.56,
                    ease: 'power2.out',
                },
                cue,
            )
            .to(
                card,
                {
                    autoAlpha: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.65,
                    ease: 'power2.out',
                },
                cue,
            );
    });

    ScrollTrigger.create({
        trigger: section,
        start: 'top top',
        end: 'bottom bottom',
        onUpdate(self) {
            const segment = 1 / cards.length;
            const index = Math.min(cards.length - 1, Math.floor(self.progress / segment));
            setActiveStep(index);
        },
    });
};

const initFaqAccordion = (): void => {
    const items = Array.from(document.querySelectorAll<HTMLElement>('[data-nh-faq-item]'));

    if (!items.length) {
        return;
    }

    const setItemState = (item: HTMLElement, expand: boolean): void => {
        const trigger = item.querySelector<HTMLButtonElement>('[data-nh-faq-trigger]');
        const answer = item.querySelector<HTMLElement>('[data-nh-faq-answer]');

        if (!trigger || !answer) {
            return;
        }

        item.classList.toggle('is-open', expand);
        trigger.setAttribute('aria-expanded', expand ? 'true' : 'false');

        if (prefersReducedMotion) {
            answer.hidden = !expand;
            answer.style.height = expand ? 'auto' : '0px';
            return;
        }

        if (expand) {
            answer.hidden = false;
            gsap.killTweensOf(answer);
            gsap.fromTo(
                answer,
                { height: 0 },
                {
                    height: answer.scrollHeight,
                    duration: 0.42,
                    ease: 'power2.out',
                    onComplete: () => {
                        answer.style.height = 'auto';
                    },
                },
            );
            return;
        }

        gsap.killTweensOf(answer);
        gsap.fromTo(
            answer,
            { height: answer.scrollHeight },
            {
                height: 0,
                duration: 0.33,
                ease: 'power2.inOut',
                onComplete: () => {
                    answer.hidden = true;
                },
            },
        );
    };

    items.forEach((item, index) => {
        const trigger = item.querySelector<HTMLButtonElement>('[data-nh-faq-trigger]');
        if (!trigger) {
            return;
        }

        if (index === 0) {
            setItemState(item, true);
        }

        trigger.addEventListener('click', () => {
            const shouldExpand = !item.classList.contains('is-open');

            items.forEach((candidate) => {
                setItemState(candidate, candidate === item ? shouldExpand : false);
            });
        });
    });
};

const initFooterLazyMedia = (): void => {
    const media = document.querySelector<HTMLElement>('[data-nh-footer-media]');
    const video = media?.querySelector<HTMLVideoElement>('[data-nh-footer-video]');

    if (!media || !video) {
        return;
    }

    let loaded = false;

    const loadVideo = (): void => {
        if (loaded) {
            return;
        }

        loaded = true;

        const sources = Array.from(video.querySelectorAll<HTMLSourceElement>('source[data-src]'));
        sources.forEach((source) => {
            const src = source.dataset.src;
            if (src) {
                source.src = src;
            }
            source.removeAttribute('data-src');
        });

        video.load();

        if (!prefersReducedMotion) {
            void video.play().catch(() => undefined);
        }
    };

    if (!('IntersectionObserver' in window)) {
        loadVideo();
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    loadVideo();
                    observer.disconnect();
                }
            });
        },
        {
            root: null,
            rootMargin: '900px 0px',
            threshold: 0.01,
        },
    );

    observer.observe(media);
};

const initButtonMicroInteractions = (): void => {
    const buttons = Array.from(document.querySelectorAll<HTMLElement>('.nh-btn'));

    if (!buttons.length || !supportsHover) {
        return;
    }

    buttons.forEach((button) => {
        button.addEventListener('pointermove', (event: PointerEvent) => {
            const rect = button.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;

            button.style.setProperty('--mx', `${x.toFixed(2)}%`);
            button.style.setProperty('--my', `${y.toFixed(2)}%`);
        });
    });
};

const initNovaLanding = (): void => {
    if (!document.querySelector('[data-nh-page]')) {
        return;
    }

    initLucide();
    initLenis();
    initLottieDecorations();
    initHeroReveal();
    initHeroVideoBehavior();
    initScrollReveals();
    initPillarSlider();
    initProcessTabs();
    initStickyStories();
    initFaqAccordion();
    initFooterLazyMedia();
    initButtonMicroInteractions();

    window.addEventListener('load', () => {
        ScrollTrigger.refresh();
    });
};

document.addEventListener('DOMContentLoaded', initNovaLanding);
