import {
    ArrowLeft,
    ArrowRight,
    Check,
    ChevronDown,
    Github,
    Linkedin,
    Moon,
    SquareArrowOutUpRight,
    Star,
    Sun,
    Twitter,
    createIcons,
} from 'lucide';
import './shared/autocomplete-globals';

window.createIcons = createIcons;
const publicLucideIcons = {
    ArrowLeft,
    ArrowRight,
    Check,
    ChevronDown,
    Github,
    Linkedin,
    Moon,
    SquareArrowOutUpRight,
    Star,
    Sun,
    Twitter,
};

window.lucideIcons = publicLucideIcons;

const glowCardSelector = '.glow-card, .app-card, .app-subcard, .nh-card, [data-glow-card]';
const silkBackgroundSelector = '[data-silk-bg]';

let glowingCardsCleanup;
let silkBackgroundsCleanup;
let roleVideosCleanup;
let aiOpsMotionCleanup;
let scrollToTopCleanup;
let jobBoardMapPromise = null;
let deferredPublicEnhancementsHandle = null;

function clearDeferredPublicEnhancementsHandle() {
    if (!deferredPublicEnhancementsHandle) {
        return;
    }

    if (deferredPublicEnhancementsHandle.type === 'idle' && 'cancelIdleCallback' in window) {
        window.cancelIdleCallback(deferredPublicEnhancementsHandle.id);
    } else {
        window.clearTimeout(deferredPublicEnhancementsHandle.id);
    }

    deferredPublicEnhancementsHandle = null;
}

function scheduleIdleWork(work, timeout = 900) {
    if ('requestIdleCallback' in window) {
        const id = window.requestIdleCallback(() => {
            work();
        }, { timeout });

        return { type: 'idle', id };
    }

    const id = window.setTimeout(work, 48);
    return { type: 'timeout', id };
}

function runPublicNonCriticalEnhancements() {
    initGlowingCards();
    initSilkBackgrounds();
    initPricingWidgets();
    initRoleExperienceVideos();
    initAiOpsMotionGating();
}

function schedulePublicNonCriticalEnhancements() {
    clearDeferredPublicEnhancementsHandle();
    deferredPublicEnhancementsHandle = scheduleIdleWork(() => {
        deferredPublicEnhancementsHandle = null;
        runPublicNonCriticalEnhancements();
    });
}

function initRevealAnimations() {
    const revealEls = Array.from(document.querySelectorAll('[data-animate="reveal"]'));
    if (!revealEls.length) {
        return;
    }

    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
        revealEls.forEach((el) => el.classList.add('is-in'));
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                entry.target.classList.add('is-in');
                io.unobserve(entry.target);
            }
        },
        { threshold: 0.12, rootMargin: '0px 0px -10% 0px' },
    );
    revealEls.forEach((el) => io.observe(el));
}

function initScrollToTopButtons() {
    if (typeof scrollToTopCleanup === 'function') {
        scrollToTopCleanup();
    }

    const buttons = Array.from(document.querySelectorAll('[data-scroll-to-top]'));
    if (!buttons.length) {
        scrollToTopCleanup = null;
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const resolveThreshold = (button) => {
        const value = Number(button.getAttribute('data-scroll-threshold'));
        return Number.isFinite(value) && value >= 0 ? value : 260;
    };

    const syncVisibility = () => {
        const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
        buttons.forEach((button) => {
            const visible = scrollY > resolveThreshold(button);
            button.classList.toggle('opacity-0', !visible);
            button.classList.toggle('translate-y-2', !visible);
            button.classList.toggle('pointer-events-none', !visible);
            button.setAttribute('aria-hidden', visible ? 'false' : 'true');
        });
    };

    const handleClick = (event) => {
        event.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: prefersReducedMotion ? 'auto' : 'smooth',
        });
    };

    buttons.forEach((button) => button.addEventListener('click', handleClick));
    window.addEventListener('scroll', syncVisibility, { passive: true });
    window.addEventListener('resize', syncVisibility, { passive: true });
    syncVisibility();

    scrollToTopCleanup = () => {
        buttons.forEach((button) => button.removeEventListener('click', handleClick));
        window.removeEventListener('scroll', syncVisibility);
        window.removeEventListener('resize', syncVisibility);
    };
}

async function ensureJobBoardMap() {
    if (!jobBoardMapPromise) {
        jobBoardMapPromise = import('./components/job-board-map')
            .catch((error) => {
                jobBoardMapPromise = null;
                console.error('Could not initialize job board map module.', error);
                return null;
            });
    }

    return jobBoardMapPromise;
}

async function initJobBoardMaps() {
    if (!document.querySelector('[data-job-board-map]')) {
        return;
    }

    const module = await ensureJobBoardMap();
    if (module && typeof module.initJobBoardMaps === 'function') {
        module.initJobBoardMaps();
    }
}

function initGlowingCards() {
    if (typeof glowingCardsCleanup === 'function') {
        glowingCardsCleanup();
    }

    const allCards = Array.from(document.querySelectorAll(glowCardSelector));
    if (!allCards.length) {
        glowingCardsCleanup = null;
        return;
    }

    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        allCards.forEach((card) => {
            card.style.setProperty('--active', '0');
        });
        glowingCardsCleanup = null;
        return;
    }

    let rafId = 0;
    let lastX = 0;
    let lastY = 0;
    const visibleCards = new Set();
    const supportsObserver = 'IntersectionObserver' in window;
    let visibilityObserver = null;

    if (supportsObserver) {
        visibilityObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!(entry.target instanceof HTMLElement)) {
                        return;
                    }

                    if (entry.isIntersecting) {
                        visibleCards.add(entry.target);
                    } else {
                        visibleCards.delete(entry.target);
                        entry.target.style.setProperty('--active', '0');
                    }
                });
            },
            {
                root: null,
                rootMargin: '140px 0px 140px 0px',
                threshold: 0.01,
            },
        );

        allCards.forEach((card) => visibilityObserver.observe(card));
    } else {
        allCards.forEach((card) => visibleCards.add(card));
    }

    const getPaintTargets = () => (supportsObserver ? Array.from(visibleCards) : allCards);

    const paint = () => {
        rafId = 0;
        const targets = getPaintTargets();

        targets.forEach((card) => {
            const rect = card.getBoundingClientRect();
            const proximity = Number(card.getAttribute('data-glow-proximity') || 112);
            const inactiveZone = Number(card.getAttribute('data-glow-inactive-zone') || 0.08);
            const strength = Number(card.getAttribute('data-glow-strength') || 1);
            const isActive =
                lastX > rect.left - proximity &&
                lastX < rect.right + proximity &&
                lastY > rect.top - proximity &&
                lastY < rect.bottom + proximity;

            if (!isActive) {
                card.style.setProperty('--active', '0');
                return;
            }

            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const distanceFromCenter = Math.hypot(lastX - centerX, lastY - centerY);
            const inactiveRadius = Math.min(rect.width, rect.height) * inactiveZone * 0.5;
            const centerFactor = inactiveRadius > 0
                ? Math.min(1, distanceFromCenter / inactiveRadius)
                : 1;

            const distanceOutsideX = Math.max(Math.abs(lastX - centerX) - rect.width / 2, 0);
            const distanceOutsideY = Math.max(Math.abs(lastY - centerY) - rect.height / 2, 0);
            const distanceOutside = Math.hypot(distanceOutsideX, distanceOutsideY);
            const falloff = proximity > 0 ? Math.max(0, 1 - distanceOutside / proximity) : 1;

            const angle = (Math.atan2(lastY - centerY, lastX - centerX) * 180) / Math.PI + 90;
            const intensity = Math.max(
                0.26,
                Math.min(1, (0.42 + falloff * 0.58) * Math.max(centerFactor, 0.58) * strength),
            );
            card.style.setProperty('--start', String(angle));
            card.style.setProperty('--active', intensity.toFixed(3));
        });
    };

    const schedulePaint = () => {
        if (supportsObserver && visibleCards.size === 0) {
            return;
        }

        if (!rafId) {
            rafId = requestAnimationFrame(paint);
        }
    };

    const handlePointerMove = (event) => {
        lastX = event.clientX;
        lastY = event.clientY;
        schedulePaint();
    };

    const handlePointerLeave = () => {
        getPaintTargets().forEach((card) => card.style.setProperty('--active', '0'));
    };

    const handleScroll = () => schedulePaint();

    document.body.addEventListener('pointermove', handlePointerMove, { passive: true });
    document.body.addEventListener('pointerleave', handlePointerLeave, { passive: true });
    window.addEventListener('scroll', handleScroll, { passive: true });

    glowingCardsCleanup = () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = 0;
        }
        document.body.removeEventListener('pointermove', handlePointerMove);
        document.body.removeEventListener('pointerleave', handlePointerLeave);
        window.removeEventListener('scroll', handleScroll);
        if (visibilityObserver) {
            visibilityObserver.disconnect();
            visibilityObserver = null;
        }
        allCards.forEach((card) => card.style.setProperty('--active', '0'));
        visibleCards.clear();
    };
}

function initSilkBackgrounds() {
    if (typeof silkBackgroundsCleanup === 'function') {
        silkBackgroundsCleanup();
    }

    const canvases = Array.from(document.querySelectorAll(silkBackgroundSelector));
    if (!canvases.length) {
        silkBackgroundsCleanup = null;
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const cleanups = [];

    const palettes = {
        default: {
            backgroundTop: '#f8fbff',
            backgroundBottom: '#dbeafe',
            accentA: '34, 197, 94',
            accentB: '14, 165, 233',
            accentC: '59, 130, 246',
            glow: '14, 165, 233',
        },
        product: {
            backgroundTop: '#f2fbfa',
            backgroundBottom: '#d6f5ef',
            accentA: '16, 185, 129',
            accentB: '6, 182, 212',
            accentC: '59, 130, 246',
            glow: '16, 185, 129',
        },
        features: {
            backgroundTop: '#f0fbff',
            backgroundBottom: '#dcfce7',
            accentA: '20, 184, 166',
            accentB: '14, 165, 233',
            accentC: '99, 102, 241',
            glow: '14, 165, 233',
        },
        pricing: {
            backgroundTop: '#f8fafc',
            backgroundBottom: '#dff7f2',
            accentA: '13, 148, 136',
            accentB: '8, 145, 178',
            accentC: '37, 99, 235',
            glow: '13, 148, 136',
        },
        contact: {
            backgroundTop: '#f8fbff',
            backgroundBottom: '#e0f2fe',
            accentA: '14, 165, 233',
            accentB: '59, 130, 246',
            accentC: '16, 185, 129',
            glow: '59, 130, 246',
        },
        legal: {
            backgroundTop: '#f8fafc',
            backgroundBottom: '#e2e8f0',
            accentA: '71, 85, 105',
            accentB: '14, 165, 233',
            accentC: '45, 212, 191',
            glow: '71, 85, 105',
        },
    };

    canvases.forEach((canvas) => {
        const parent = canvas.parentElement;
        const ctx = canvas.getContext('2d');
        if (!parent || !ctx) {
            return;
        }

        let rafId = 0;
        let width = 0;
        let height = 0;
        const tone = canvas.dataset.silkTone || 'default';
        const intensity = Number(canvas.dataset.silkIntensity || 1);
        const supportsObserver = 'IntersectionObserver' in window;
        const shouldAnimate = canvas.dataset.silkAnimate === 'true' && !prefersReducedMotion;
        let isIntersecting = !shouldAnimate || !supportsObserver;
        let visibilityObserver = null;

        const resolvePalette = () => {
            const base = palettes[tone] || palettes.default;
            if (document.documentElement.classList.contains('dark')) {
                return {
                    backgroundTop: '#08111f',
                    backgroundBottom: '#020617',
                    accentA: base.accentA,
                    accentB: base.accentB,
                    accentC: base.accentC,
                    glow: base.glow,
                };
            }

            return base;
        };

        const resize = () => {
            const rect = parent.getBoundingClientRect();
            width = Math.max(1, rect.width);
            height = Math.max(1, rect.height);

            const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        };

        const draw = (timestamp = 0) => {
            const palette = resolvePalette();
            const time = timestamp * 0.00028;

            const background = ctx.createLinearGradient(0, 0, width, height);
            background.addColorStop(0, palette.backgroundTop);
            background.addColorStop(1, palette.backgroundBottom);
            ctx.clearRect(0, 0, width, height);
            ctx.fillStyle = background;
            ctx.fillRect(0, 0, width, height);

            const radial = ctx.createRadialGradient(width * 0.78, height * 0.2, 0, width * 0.78, height * 0.2, width * 0.55);
            const glowAlpha = document.documentElement.classList.contains('dark') ? (16 / 100) : (18 / 100);
            radial.addColorStop(0, `rgba(${palette.glow}, ${glowAlpha})`);
            radial.addColorStop(1, 'rgba(255,255,255,0)');
            ctx.fillStyle = radial;
            ctx.fillRect(0, 0, width, height);

            const bandCount = Math.max(11, Math.round(height / 34));
            for (let index = 0; index < bandCount; index += 1) {
                const ratio = index / Math.max(1, bandCount - 1);
                const baseY = height * (0.1 + ratio * 0.9);
                const amplitude = (12 + ratio * 30) * intensity;
                const secondaryAmplitude = (8 + ratio * 14) * intensity;
                const lineWidth = 1.2 + ratio * 2.2;

                ctx.beginPath();
                for (let x = -28; x <= width + 28; x += 18) {
                    const y =
                        baseY +
                        Math.sin(x * 0.0105 + time * (1.4 + ratio * 0.8) + index * 0.46) * amplitude +
                        Math.cos(x * 0.018 + time * (0.8 + ratio * 0.55) - index * 0.31) * secondaryAmplitude;

                    if (x === -28) {
                        ctx.moveTo(x, y);
                    } else {
                        ctx.lineTo(x, y);
                    }
                }

                const alpha = document.documentElement.classList.contains('dark')
                    ? ((12 / 100) + ratio * (8 / 100))
                    : ((8 / 100) + ratio * (9 / 100));
                const color =
                    index % 3 === 0
                        ? palette.accentA
                        : index % 3 === 1
                            ? palette.accentB
                            : palette.accentC;

                ctx.strokeStyle = `rgba(${color}, ${alpha})`;
                ctx.lineWidth = lineWidth;
                ctx.shadowBlur = 24;
                ctx.shadowColor = `rgba(${color}, ${alpha * 1.6})`;
                ctx.stroke();
            }

            ctx.shadowBlur = 0;

            if (shouldAnimate && isIntersecting && !document.hidden) {
                rafId = requestAnimationFrame(draw);
            }
        };

        const stopAnimation = () => {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = 0;
            }
        };

        const startAnimation = () => {
            if (!shouldAnimate || document.hidden || !isIntersecting || rafId) {
                return;
            }

            rafId = requestAnimationFrame(draw);
        };

        if (shouldAnimate && supportsObserver) {
            isIntersecting = false;
        }

        resize();
        draw();

        if (shouldAnimate && supportsObserver) {
            visibilityObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.target !== canvas) {
                            return;
                        }

                        isIntersecting = entry.isIntersecting && entry.intersectionRatio >= 0.1;
                        if (isIntersecting) {
                            startAnimation();
                        } else {
                            stopAnimation();
                        }
                    });
                },
                {
                    root: null,
                    rootMargin: '220px 0px 220px 0px',
                    threshold: [0, 0.1],
                },
            );

            visibilityObserver.observe(canvas);
        } else if (shouldAnimate) {
            isIntersecting = true;
            startAnimation();
        }

        const handleResize = () => {
            resize();
            stopAnimation();
            draw();
        };

        const handleVisibilityChange = () => {
            if (!shouldAnimate) {
                return;
            }

            if (document.hidden) {
                stopAnimation();
                return;
            }

            startAnimation();
        };

        window.addEventListener('resize', handleResize, { passive: true });
        document.addEventListener('visibilitychange', handleVisibilityChange, { passive: true });

        cleanups.push(() => {
            stopAnimation();
            if (visibilityObserver) {
                visibilityObserver.disconnect();
                visibilityObserver = null;
            }
            window.removeEventListener('resize', handleResize);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        });
    });

    silkBackgroundsCleanup = () => {
        cleanups.forEach((cleanup) => cleanup());
    };
}

function initRoleExperienceVideos() {
    if (typeof roleVideosCleanup === 'function') {
        roleVideosCleanup();
        roleVideosCleanup = null;
    }

    const videos = Array.from(document.querySelectorAll('video[data-role-exp-video], video[data-hiring-loop-video]'));
    if (!videos.length) {
        return;
    }

    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const intersectionRatioMap = new WeakMap();
    let rafId = 0;

    const getMaxActiveVideos = () => {
        const hasLiteModeSection = document.querySelector('[data-ai-ops-section].ai-ops-lite') !== null;
        if (window.matchMedia('(max-width: 640px)').matches) {
            return hasLiteModeSection ? 1 : 2;
        }

        if (window.matchMedia('(max-width: 1024px)').matches) {
            return hasLiteModeSection ? 2 : 2;
        }

        return hasLiteModeSection ? 2 : 3;
    };

    const parsePriority = (video) => {
        const parsed = Number.parseFloat(video.dataset.videoPriority || '');
        if (!Number.isFinite(parsed)) {
            return 1;
        }

        return Math.max(1, Math.min(10, parsed));
    };

    const measureArea = (video) => {
        const rect = video.getBoundingClientRect();
        return Math.max(0, rect.width) * Math.max(0, rect.height);
    };

    const setPlaybackRate = (video) => {
        const mediaHost = video.closest('.hiring-motion-icon');
        if (mediaHost instanceof HTMLElement) {
            const configuredRate = Number.parseFloat(getComputedStyle(mediaHost).getPropertyValue('--hiring-icon-rate'));
            if (Number.isFinite(configuredRate) && configuredRate > 0) {
                video.playbackRate = Math.max(0.5, Math.min(1.6, configuredRate));
            }
        }
    };

    const ensureVideoSource = (video) => {
        if (!(video instanceof HTMLVideoElement)) {
            return false;
        }

        if (video.dataset.videoSourceReady === '1') {
            return true;
        }

        const sourceElements = Array.from(video.querySelectorAll('source'));
        let hasDeferredSources = false;
        let sourceUpdated = false;

        sourceElements.forEach((sourceElement) => {
            const deferredSrc = (sourceElement.dataset.src || '').trim();
            if (!deferredSrc) {
                return;
            }

            hasDeferredSources = true;
            if (!sourceElement.getAttribute('src')) {
                sourceElement.setAttribute('src', deferredSrc);
                sourceUpdated = true;
            }
        });

        if (hasDeferredSources) {
            if (sourceUpdated) {
                video.load();
            }

            video.dataset.videoSourceReady = '1';
            return true;
        }

        const fallbackSrc = (
            video.dataset.videoSrcWebm ||
            video.dataset.videoSrcMp4 ||
            video.dataset.videoSrc ||
            video.getAttribute('src') ||
            ''
        ).trim();

        if (!fallbackSrc) {
            video.dataset.videoSourceReady = '1';
            return true;
        }

        if (!video.getAttribute('src')) {
            video.setAttribute('src', fallbackSrc);
            video.load();
        }

        video.dataset.videoSourceReady = '1';
        return true;
    };

    const pauseVideo = (video) => {
        if (!(video instanceof HTMLVideoElement)) {
            return;
        }

        video.pause();
        video.dataset.videoState = 'paused';
    };

    const playVideo = (video) => {
        if (!(video instanceof HTMLVideoElement)) {
            return;
        }

        ensureVideoSource(video);
        setPlaybackRate(video);

        const playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(() => {});
        }
        video.dataset.videoState = 'playing';
    };

    videos.forEach((video) => {
        if (!(video instanceof HTMLVideoElement)) {
            return;
        }

        video.preload = 'none';
        setPlaybackRate(video);
        pauseVideo(video);
        intersectionRatioMap.set(video, 0);
    });

    if (prefersReducedMotion) {
        videos.forEach(pauseVideo);
        return;
    }

    const syncPlayback = () => {
        if (document.hidden) {
            videos.forEach(pauseVideo);
            return;
        }

        const candidateVideos = videos
            .filter((video) => {
                if (!(video instanceof HTMLVideoElement)) {
                    return false;
                }

                return (intersectionRatioMap.get(video) || 0) >= 0.34;
            })
            .sort((left, right) => {
                const priorityDelta = parsePriority(right) - parsePriority(left);
                if (priorityDelta !== 0) {
                    return priorityDelta;
                }

                const areaDelta = measureArea(right) - measureArea(left);
                if (Math.abs(areaDelta) > 1) {
                    return areaDelta;
                }

                return (intersectionRatioMap.get(right) || 0) - (intersectionRatioMap.get(left) || 0);
            });

        const activeVideos = new Set(candidateVideos.slice(0, getMaxActiveVideos()));
        videos.forEach((video) => {
            if (!(video instanceof HTMLVideoElement)) {
                return;
            }

            if (activeVideos.has(video)) {
                playVideo(video);
            } else {
                pauseVideo(video);
            }
        });
    };

    const scheduleSyncPlayback = () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
        }

        rafId = requestAnimationFrame(() => {
            rafId = 0;
            syncPlayback();
        });
    };

    if (!('IntersectionObserver' in window)) {
        videos.forEach((video) => {
            if (video instanceof HTMLVideoElement) {
                intersectionRatioMap.set(video, 1);
            }
        });

        syncPlayback();
        const handleVisibilityChange = () => syncPlayback();
        const handleResize = () => scheduleSyncPlayback();
        document.addEventListener('visibilitychange', handleVisibilityChange, { passive: true });
        window.addEventListener('resize', handleResize, { passive: true });

        roleVideosCleanup = () => {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = 0;
            }
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            window.removeEventListener('resize', handleResize);
            videos.forEach(pauseVideo);
        };
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                const video = entry.target;
                if (!(video instanceof HTMLVideoElement)) {
                    return;
                }

                const nextRatio = entry.isIntersecting ? entry.intersectionRatio : 0;
                intersectionRatioMap.set(video, nextRatio);
            });

            scheduleSyncPlayback();
        },
        {
            root: null,
            rootMargin: '100px 0px 100px 0px',
            threshold: [0, 0.12, 0.34, 0.52, 0.75],
        },
    );

    videos.forEach((video) => {
        if (!(video instanceof HTMLVideoElement)) {
            return;
        }

        observer.observe(video);
    });

    const handleVisibilityChange = () => syncPlayback();
    const handleResize = () => scheduleSyncPlayback();

    document.addEventListener('visibilitychange', handleVisibilityChange, { passive: true });
    window.addEventListener('resize', handleResize, { passive: true });
    syncPlayback();

    roleVideosCleanup = () => {
        if (rafId) {
            cancelAnimationFrame(rafId);
            rafId = 0;
        }
        observer.disconnect();
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        window.removeEventListener('resize', handleResize);
        videos.forEach(pauseVideo);
    };
}

function initAiOpsMotionGating() {
    if (typeof aiOpsMotionCleanup === 'function') {
        aiOpsMotionCleanup();
        aiOpsMotionCleanup = null;
    }

    const sections = Array.from(document.querySelectorAll('[data-ai-ops-section]'));
    if (!sections.length) {
        return;
    }

    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const prefersDataSaving = Boolean(connection && connection.saveData);
    const lowMemoryDevice = typeof navigator.deviceMemory === 'number' && navigator.deviceMemory <= 4;
    const lowCpuDevice = typeof navigator.hardwareConcurrency === 'number' && navigator.hardwareConcurrency <= 4;
    const useLiteMode = prefersDataSaving || lowMemoryDevice || lowCpuDevice;

    sections.forEach((section) => {
        if (!(section instanceof HTMLElement)) {
            return;
        }

        section.classList.toggle('ai-ops-lite', useLiteMode);
    });

    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        sections.forEach((section) => {
            section.classList.add('ai-ops-paused');
        });
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!(entry.target instanceof HTMLElement)) {
                    return;
                }

                entry.target.classList.toggle('ai-ops-paused', !entry.isIntersecting);
            });
        },
        {
            root: null,
            rootMargin: '160px 0px 160px 0px',
            threshold: 0.12,
        },
    );

    sections.forEach((section) => {
        section.classList.add('ai-ops-paused');
        observer.observe(section);
    });

    aiOpsMotionCleanup = () => {
        observer.disconnect();
    };
}

function initPricingWidgets() {
    const widgets = Array.from(document.querySelectorAll('[data-pricing-widget]'));
    if (!widgets.length) {
        return;
    }

    const formatMoney = (value) => `$${Number(value || 0).toLocaleString()}`;

    widgets.forEach((widget) => {
        const buttons = Array.from(widget.querySelectorAll('[data-pricing-billing]'));
        if (!buttons.length) {
            return;
        }

        const switchTrack = widget.querySelector('[data-pricing-switch]');
        const switchIndicator = widget.querySelector('[data-pricing-switch-indicator]');
        const priceEls = Array.from(widget.querySelectorAll('[data-pricing-amount]'));
        const periodEls = Array.from(widget.querySelectorAll('[data-pricing-period]'));
        const saveBadges = Array.from(widget.querySelectorAll('[data-pricing-save-badge]'));
        const equivalentEls = Array.from(widget.querySelectorAll('[data-pricing-equivalent]'));
        const billedEls = Array.from(widget.querySelectorAll('[data-pricing-billed]'));
        const billingCycleInputs = Array.from(widget.querySelectorAll('[data-billing-cycle-input]'));
        let activeAnimationFrame = null;
        let switchAnimationFrame = null;

        const resolveAnnual = (monthly, annualRaw) => {
            const annual = annualRaw > 0 ? annualRaw : (monthly * 12 * 0.8);
            return Number.isFinite(annual) ? annual : 0;
        };

        const syncPeriodLabels = (normalizedMode) => {
            periodEls.forEach((el) => {
                const monthlyLabel = (el.getAttribute('data-monthly') || 'month').trim() || 'month';
                const annualLabel = (el.getAttribute('data-annual') || 'year').trim() || 'year';
                const label = normalizedMode === 'annual' ? annualLabel : monthlyLabel;
                el.textContent = `/ ${label}`;
            });
        };

        const syncSaveBadges = (normalizedMode) => {
            saveBadges.forEach((badge) => {
                const card = badge.closest('[data-plan-card]') || badge.closest('article') || badge.parentElement;
                const priceEl = card ? card.querySelector('[data-pricing-amount]') : null;
                const monthly = priceEl ? Number(priceEl.getAttribute('data-monthly') || 0) : 0;
                const annualRaw = priceEl ? Number(priceEl.getAttribute('data-annual') || 0) : 0;
                const annual = resolveAnnual(monthly, annualRaw);
                const shouldShow = normalizedMode === 'annual' && monthly > 0 && annual > 0 && annual < (monthly * 12);
                badge.classList.toggle('hidden', !shouldShow);
            });
        };

        const syncEquivalentLines = (normalizedMode) => {
            equivalentEls.forEach((line) => {
                const card = line.closest('[data-plan-card]') || line.closest('article') || line.parentElement;
                const priceEl = card ? card.querySelector('[data-pricing-amount]') : null;
                const monthly = priceEl ? Number(priceEl.getAttribute('data-monthly') || 0) : 0;
                const annualRaw = priceEl ? Number(priceEl.getAttribute('data-annual') || 0) : 0;
                const annual = resolveAnnual(monthly, annualRaw);
                const shouldShow = normalizedMode === 'annual' && annual > 0;
                line.classList.toggle('hidden', !shouldShow);
                if (shouldShow) {
                    const perMonth = annual / 12;
                    line.textContent = `≈ ${formatMoney(Math.round(perMonth))}/mo when billed annually`;
                }
            });
        };

        const moveIndicator = (activeButton) => {
            if (!switchTrack || !switchIndicator || !activeButton) {
                return;
            }

            if (switchAnimationFrame !== null) {
                cancelAnimationFrame(switchAnimationFrame);
                switchAnimationFrame = null;
            }

            switchAnimationFrame = requestAnimationFrame(() => {
                const trackRect = switchTrack.getBoundingClientRect();
                const buttonRect = activeButton.getBoundingClientRect();
                const offset = Math.max(0, buttonRect.left - trackRect.left);

                switchIndicator.style.width = `${Math.round(buttonRect.width)}px`;
                switchIndicator.style.transform = `translate3d(${Math.round(offset)}px, 0, 0)`;
                switchIndicator.style.opacity = '1';

                switchAnimationFrame = null;
            });
        };

        const applyMode = (mode, triggerButton = null) => {
            const normalizedMode = mode === 'annual' ? 'annual' : 'monthly';
            widget.dataset.pricingMode = normalizedMode;

            const activeButton = triggerButton && buttons.includes(triggerButton)
                ? triggerButton
                : (buttons.find((btn) => btn.getAttribute('data-pricing-billing') === normalizedMode) || buttons[0]);

            buttons.forEach((btn) => {
                const isActive = btn.getAttribute('data-pricing-billing') === normalizedMode;
                btn.classList.toggle('is-active', isActive);
                btn.classList.toggle('bg-brand-600', isActive && !switchTrack);
                btn.classList.toggle('text-white', isActive && !switchTrack);
                btn.classList.toggle('shadow-sm', isActive && !switchTrack);
                btn.classList.toggle('text-slate-600', !isActive && !switchTrack);
                btn.classList.toggle('dark:text-slate-200', !isActive && !switchTrack);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            moveIndicator(activeButton);

            if (activeAnimationFrame !== null) {
                cancelAnimationFrame(activeAnimationFrame);
                activeAnimationFrame = null;
            }

            const prefersReduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

            if (prefersReduced) {
                priceEls.forEach((el) => {
                    const monthly = Number(el.getAttribute('data-monthly') || 0);
                    const annualRaw = Number(el.getAttribute('data-annual') || 0);
                    const annual = resolveAnnual(monthly, annualRaw);
                    const amount = normalizedMode === 'annual' ? annual : monthly;
                    el.textContent = formatMoney(Math.round(amount));
                    el.style.transform = '';
                    el.style.opacity = '';
                    el.style.filter = '';
                });
            } else {
                const snapshots = priceEls.map((el) => {
                    const monthly = Number(el.getAttribute('data-monthly') || 0);
                    const annualRaw = Number(el.getAttribute('data-annual') || 0);
                    const annual = resolveAnnual(monthly, annualRaw);
                    const target = normalizedMode === 'annual' ? annual : monthly;
                    const currentText = (el.textContent || '').replace(/[^\d.]/g, '');
                    const current = Number(currentText || target);
                    return { el, from: current, to: target };
                });

                const duration = 520;
                const startAt = performance.now();
                const direction = normalizedMode === 'annual' ? -1 : 1;
                const easeOutQuart = (t) => 1 - Math.pow(1 - t, 4);

                const step = (now) => {
                    const progress = Math.min(1, (now - startAt) / duration);
                    const eased = easeOutQuart(progress);

                    snapshots.forEach(({ el, from, to }) => {
                        const value = from + (to - from) * eased;
                        el.textContent = formatMoney(Math.round(value));
                        el.style.transform = `translateY(${((1 - eased) * 8 * direction).toFixed(2)}px)`;
                        el.style.opacity = String(0.56 + eased * 0.44);
                        el.style.filter = `blur(${((1 - eased) * 1.6).toFixed(2)}px)`;
                    });

                    if (progress < 1) {
                        activeAnimationFrame = requestAnimationFrame(step);
                    } else {
                        activeAnimationFrame = null;
                        snapshots.forEach(({ el, to }) => {
                            el.textContent = formatMoney(Math.round(to));
                            el.style.transform = '';
                            el.style.opacity = '';
                            el.style.filter = '';
                        });
                    }
                };

                activeAnimationFrame = requestAnimationFrame(step);
            }

            billedEls.forEach((el) => {
                el.textContent = normalizedMode === 'annual' ? 'billed annually' : 'billed monthly';
            });

            billingCycleInputs.forEach((input) => {
                input.value = normalizedMode;
            });

            syncPeriodLabels(normalizedMode);
            syncSaveBadges(normalizedMode);
            syncEquivalentLines(normalizedMode);
        };

        const refreshIndicator = () => {
            const activeButton = buttons.find((btn) => btn.getAttribute('aria-pressed') === 'true')
                || buttons.find((btn) => btn.getAttribute('data-pricing-billing') === (widget.dataset.pricingMode || 'monthly'))
                || buttons[0];
            moveIndicator(activeButton);
        };

        buttons.forEach((btn) => {
            if (btn.dataset.pricingBound === '1') {
                return;
            }

            btn.addEventListener('click', () => {
                applyMode(btn.getAttribute('data-pricing-billing') || 'monthly', btn);
            });
            btn.dataset.pricingBound = '1';
        });

        if (widget.dataset.pricingResizeBound !== '1') {
            window.addEventListener('resize', refreshIndicator, { passive: true });
            widget.dataset.pricingResizeBound = '1';
        }

        const initialMode = widget.dataset.pricingMode === 'annual' ? 'annual' : 'monthly';
        applyMode(initialMode);
    });
}
let mobileNavCleanup = null;

/**
 * Mobile navigation drawer.
 *
 * The <aside #nh-mob-drawer> and <div #nh-mob-overlay> are rendered inside
 * the Blade component but **portalled to document.body** at runtime so they
 * are never clipped by the sticky header's CSS stacking context.
 */
function initMobileNav() {
    // Tear down any previous binding (SPA navigation)
    if (typeof mobileNavCleanup === 'function') {
        mobileNavCleanup();
        mobileNavCleanup = null;
    }

    const toggle  = document.getElementById('nh-mob-toggle');
    const drawer  = document.getElementById('nh-mob-drawer');
    const overlay = document.getElementById('nh-mob-overlay');
    const closeBt = document.getElementById('nh-mob-drawer-close');
    const openIco = document.getElementById('nh-mob-open-icon');
    const closeIco = document.getElementById('nh-mob-close-icon');

    if (!toggle || !drawer) return;

    // ── Portal drawer + overlay to <body> so fixed positioning works correctly ──
    if (drawer.parentElement !== document.body)  document.body.appendChild(drawer);
    if (overlay && overlay.parentElement !== document.body) document.body.appendChild(overlay);

    let isOpen = false;

    const showDrawer = () => {
        isOpen = true;

        // Make elements visible before animating
        drawer.style.display  = 'flex';
        if (overlay) overlay.style.display = 'block';

        // Force reflow so the CSS transition fires
        // eslint-disable-next-line no-unused-expressions
        drawer.offsetHeight;
        if (overlay) overlay.offsetHeight;

        drawer.classList.remove('-translate-x-full');
        drawer.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');

        if (overlay) {
            overlay.classList.remove('opacity-0');
            overlay.classList.add('opacity-100');
            overlay.style.pointerEvents = 'auto';
        }

        if (openIco)  openIco.classList.add('hidden');
        if (closeIco) closeIco.classList.remove('hidden');

        // Lock scroll
        document.body.style.overflow = 'hidden';

        // Move focus into drawer
        const first = drawer.querySelector('a[href], button:not([disabled])');
        if (first) first.focus();
    };

    const hideDrawer = () => {
        if (!isOpen) return;
        isOpen = false;

        drawer.classList.add('-translate-x-full');
        drawer.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');

        if (overlay) {
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
            overlay.style.pointerEvents = 'none';
        }

        if (openIco)  openIco.classList.remove('hidden');
        if (closeIco) closeIco.classList.add('hidden');

        document.body.style.overflow = '';
        toggle.focus();

        // Hide after transition ends
        const DURATION = 320;
        setTimeout(() => {
            if (!isOpen) {
                drawer.style.display  = 'none';
                if (overlay) overlay.style.display = 'none';
            }
        }, DURATION);
    };

    const onToggle    = () => (isOpen ? hideDrawer() : showDrawer());
    const onOverlay   = () => hideDrawer();
    const onKeyDown   = (e) => { if (e.key === 'Escape' && isOpen) { e.preventDefault(); hideDrawer(); } };

    // Auto-close at desktop breakpoint
    const mq = window.matchMedia('(min-width: 768px)');
    const onBreakpoint = (e) => { if (e.matches && isOpen) hideDrawer(); };

    toggle.addEventListener('click', onToggle);
    if (closeBt) closeBt.addEventListener('click', hideDrawer);
    if (overlay) overlay.addEventListener('click', onOverlay);
    document.addEventListener('keydown', onKeyDown);
    mq.addEventListener('change', onBreakpoint);

    // Any link/button inside drawer that should close it
    const navLinks = Array.from(drawer.querySelectorAll('.nh-mob-link'));
    navLinks.forEach((el) => el.addEventListener('click', hideDrawer));

    mobileNavCleanup = () => {
        toggle.removeEventListener('click', onToggle);
        if (closeBt) closeBt.removeEventListener('click', hideDrawer);
        if (overlay) overlay.removeEventListener('click', onOverlay);
        document.removeEventListener('keydown', onKeyDown);
        mq.removeEventListener('change', onBreakpoint);
        navLinks.forEach((el) => el.removeEventListener('click', hideDrawer));
        if (isOpen) hideDrawer();
        document.body.style.overflow = '';
    };
}


async function initializePublicUi() {
    clearDeferredPublicEnhancementsHandle();
    await Promise.all([
        initJobBoardMaps(),
    ]);
    createIcons({ icons: publicLucideIcons });
    initMobileNav();
    initScrollToTopButtons();
    initRevealAnimations();
    schedulePublicNonCriticalEnhancements();
}

document.addEventListener('DOMContentLoaded', () => {
    initializePublicUi();
});

document.addEventListener('livewire:initialized', () => {
    if (!window.Livewire || typeof window.Livewire.hook !== 'function') {
        return;
    }

    window.Livewire.hook('morph.updated', () => {
        initJobBoardMaps();
        createIcons({ icons: publicLucideIcons });
        initMobileNav();
        initScrollToTopButtons();
        schedulePublicNonCriticalEnhancements();
    });
});

document.addEventListener('livewire:navigated', () => {
    initializePublicUi();
});
