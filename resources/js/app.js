import './bootstrap';
import { createIcons, icons } from 'lucide';
import './shared/autocomplete-globals';

window.createIcons = createIcons;
window.lucideIcons = icons;

let flatpickrPromise = null;
let flatpickrStylesPromise = null;
let chartJsPromise = null;
let analysisLottieInitPromise = null;

async function ensureFlatpickr() {
    if (window.flatpickr) {
        return window.flatpickr;
    }

    if (!flatpickrPromise) {
        if (!flatpickrStylesPromise) {
            flatpickrStylesPromise = import('flatpickr/dist/flatpickr.min.css').catch((error) => {
                console.error('Could not load flatpickr styles.', error);
                return null;
            });
        }

        flatpickrPromise = Promise.all([import('flatpickr'), flatpickrStylesPromise])
            .then(([{ default: flatpickrLib }]) => {
                window.flatpickr = flatpickrLib;
                return flatpickrLib;
            })
            .catch((error) => {
                flatpickrPromise = null;
                console.error('Could not initialize flatpickr.', error);
                return null;
            });
    }

    return flatpickrPromise;
}

async function ensureChartJs() {
    if (window.Chart) {
        return window.Chart;
    }

    if (!chartJsPromise) {
        chartJsPromise = import('chart.js/auto')
            .then(({ default: ChartLib }) => {
                window.Chart = ChartLib;
                return ChartLib;
            })
            .catch((error) => {
                chartJsPromise = null;
                console.error('Could not initialize Chart.js.', error);
                return null;
            });
    }

    return chartJsPromise;
}

window.ensureFlatpickr = ensureFlatpickr;
window.ensureChartJs = ensureChartJs;

function prewarmConditionalLibraries() {
    const chartSelector = '#companyGrowthChart, #appsOverTimeChart, #scoreDistChart, #adminAiUsageChart, #adminAiRecoChart, #applicationsChart, #jobsChart, #recommendationChart, #funnelDoughnut, #scoreChart, #timeToHireChart';
    const flatpickrSelector = '[x-ref=\"dateInput\"], [x-ref=\"datepicker\"], .custom-datepicker input';

    if (document.querySelector(chartSelector)) {
        void ensureChartJs();
    }

    if (document.querySelector(flatpickrSelector)) {
        void ensureFlatpickr();
    }
}

prewarmConditionalLibraries();

async function initAnalysisLottieLoadersIfNeeded() {
    if (!document.querySelector('[data-ai-analysis-lottie-root]')) {
        return;
    }

    if (!analysisLottieInitPromise) {
        analysisLottieInitPromise = import('./react/init-analysis-lottie-loader')
            .then((module) => module.initAnalysisLottieLoaders)
            .catch((error) => {
                console.error('Could not initialize analysis Lottie loaders.', error);
                return null;
            });
    }

    const initLoaders = await analysisLottieInitPromise;
    if (typeof initLoaders === 'function') {
        initLoaders();
    }
}

function registerAlpineStores() {
    const Alpine = window.Alpine;
    if (!Alpine || typeof Alpine.store !== 'function') {
        return;
    }

    const hasStore = (name) => {
        try {
            return Alpine.store(name) !== undefined;
        } catch (error) {
            return false;
        }
    };

    if (!hasStore('toast')) {
        Alpine.store('toast', {
            visible: false,
            message: '',
            type: 'success',
            _hideTimer: null,
            show(message = '', options = {}) {
                const text = String(message || '').trim();
                if (!text) {
                    return;
                }

                const duration = Number(options.duration ?? 2400);
                this.message = text;
                this.type = options.type || 'success';
                this.visible = true;

                if (this._hideTimer) {
                    window.clearTimeout(this._hideTimer);
                }

                this._hideTimer = window.setTimeout(() => {
                    this.hide();
                }, Number.isFinite(duration) ? duration : 2400);
            },
            hide() {
                this.visible = false;
                if (this._hideTimer) {
                    window.clearTimeout(this._hideTimer);
                    this._hideTimer = null;
                }
            },
        });
    }

    if (!hasStore('sidebar')) {
        const syncSidebarExpandedDataset = (value) => {
            document.documentElement.dataset.sidebarExpanded = value ? 'true' : 'false';
        };

        const getInitialSidebarExpanded = () => {
            try {
                const persistedValue = window.localStorage.getItem('sidebar-expanded');
                if (persistedValue === 'true' || persistedValue === 'false') {
                    return persistedValue === 'true';
                }
            } catch (error) {
                // no-op: fallback to server/preload value
            }

            return document.documentElement.dataset.sidebarExpanded !== 'false';
        };

        const initialSidebarExpanded = getInitialSidebarExpanded();
        syncSidebarExpandedDataset(initialSidebarExpanded);

        Alpine.store('sidebar', {
            isExpanded: initialSidebarExpanded,
            isHovered: false,
            isMobileOpen: false,
            isReady: false,
            init() {
                this.isExpanded = getInitialSidebarExpanded();
                syncSidebarExpandedDataset(this.isExpanded);

                this.handleResize = () => {
                    if (window.innerWidth >= 1280) {
                        this.isMobileOpen = false;
                    }
                };

                window.addEventListener('resize', this.handleResize, { passive: true });
                this.handleResize();
                window.requestAnimationFrame(() => {
                    this.isReady = true;
                });
            },
            setExpanded(value) {
                this.isExpanded = Boolean(value);
                syncSidebarExpandedDataset(this.isExpanded);

                try {
                    window.localStorage.setItem('sidebar-expanded', this.isExpanded ? 'true' : 'false');
                } catch (error) {
                    // no-op: in-memory state still works
                }
            },
            toggleExpanded() {
                this.setExpanded(!this.isExpanded);
                this.isHovered = false;
            },
            setHovered(value) {
                this.isHovered = Boolean(value);
            },
            setMobileOpen(value) {
                this.isMobileOpen = Boolean(value);
            },
            toggleMobileOpen() {
                this.isMobileOpen = !this.isMobileOpen;
            },
        });
    }

    if (!hasStore('theme')) {
        Alpine.store('theme', {
            mode: 'light',
            init() {
                const savedTheme = window.localStorage.getItem('theme');
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                this.mode = savedTheme || systemTheme;
                this.apply();
            },
            apply() {
                const isDark = this.mode === 'dark';
                document.documentElement.classList.toggle('dark', isDark);
                window.localStorage.setItem('theme', this.mode);
            },
            set(mode) {
                this.mode = mode === 'dark' ? 'dark' : 'light';
                this.apply();
            },
            toggle() {
                this.set(this.mode === 'dark' ? 'light' : 'dark');
            },
        });
    }

    if (!hasStore('clip')) {
        Alpine.store('clip', {
            async copy(text, successMessage = 'Copied') {
                const value = String(text ?? '').trim();
                if (!value) {
                    return false;
                }

                try {
                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        await navigator.clipboard.writeText(value);
                    } else {
                        const input = document.createElement('textarea');
                        input.value = value;
                        input.style.position = 'fixed';
                        input.style.left = '-9999px';
                        document.body.appendChild(input);
                        input.focus();
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                    }

                    const toast = Alpine.store('toast');
                    if (toast && typeof toast.show === 'function') {
                        toast.show(successMessage, { duration: 2000 });
                    }

                    return true;
                } catch (error) {
                    const toast = Alpine.store('toast');
                    if (toast && typeof toast.show === 'function') {
                        toast.show('Could not copy. Try again.', { type: 'error', duration: 2600 });
                    }

                    return false;
                }
            },
        });
    }
}

document.addEventListener('alpine:init', registerAlpineStores);
if (window.Alpine) {
    registerAlpineStores();
}

let apexChartsPromise;
let jobBoardMapPromise;
const glowCardSelector = '.glow-card, .app-card, .app-subcard, .nh-card, [data-glow-card]';

async function ensureApexCharts() {
    if (!apexChartsPromise) {
        apexChartsPromise = import('apexcharts').then(({ default: ApexCharts }) => {
            window.ApexCharts = ApexCharts;
            return ApexCharts;
        });
    }

    return apexChartsPromise;
}

async function ensureJobBoardMap() {
    if (!jobBoardMapPromise) {
        jobBoardMapPromise = import('./components/job-board-map');
    }

    return jobBoardMapPromise;
}

async function initJobBoardMaps() {
    if (!document.querySelector('[data-job-board-map]')) {
        return;
    }

    const module = await ensureJobBoardMap();
    module.initJobBoardMaps();
}

let glowingCardsCleanup;
let scrollToTopCleanup;

function initGlowingCards() {
    if (typeof glowingCardsCleanup === 'function') {
        glowingCardsCleanup();
    }

    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        document.querySelectorAll(glowCardSelector).forEach((card) => {
            card.style.setProperty('--active', '0');
        });
        glowingCardsCleanup = null;
        return;
    }

    let rafId = 0;
    let lastX = 0;
    let lastY = 0;

    const cards = () => document.querySelectorAll(glowCardSelector);

    const paint = () => {
        rafId = 0;

        cards().forEach((card) => {
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
        cards().forEach((card) => card.style.setProperty('--active', '0'));
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
    };
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

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', async () => {
    prewarmConditionalLibraries();
    await initAnalysisLottieLoadersIfNeeded();
    // Local lucide icons (avoid third-party CDN blocking)
    createIcons({ icons });
    await initJobBoardMaps();

    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    const chartModules = [
        { selector: '#chartOne', initFn: 'initChartOne', loader: () => import('./components/chart/chart-1') },
        { selector: '#chartTwo', initFn: 'initChartTwo', loader: () => import('./components/chart/chart-2') },
        { selector: '#chartThree', initFn: 'initChartThree', loader: () => import('./components/chart/chart-3') },
        { selector: '#chartSix', initFn: 'initChartSix', loader: () => import('./components/chart/chart-6') },
        { selector: '#chartEight', initFn: 'initChartEight', loader: () => import('./components/chart/chart-8') },
        { selector: '#chartThirteen', initFn: 'initChartThirteen', loader: () => import('./components/chart/chart-13') },
    ];
    const activeChartModules = chartModules.filter(({ selector }) => document.querySelector(selector));

    if (activeChartModules.length > 0) {
        await ensureApexCharts();
        activeChartModules.forEach(({ loader, initFn }) => {
            loader().then((module) => {
                if (typeof module[initFn] === 'function') {
                    module[initFn]();
                }
            });
        });
    }

    // Calendar init
    if (document.querySelector('#calendar')) {
        import('./components/calendar-init').then(module => module.calendarInit());
    }

    // Kanban drag/drop only needs Sortable on that page.
    if (document.querySelector('[data-kanban-board]')) {
        const { default: Sortable } = await import('sortablejs');
        window.Sortable = Sortable;
    }

    initGlowingCards();
    initScrollToTopButtons();
});

document.addEventListener('livewire:initialized', () => {
    if (!window.Livewire || typeof window.Livewire.hook !== 'function') {
        return;
    }

    window.Livewire.hook('morph.updated', () => {
        initAnalysisLottieLoadersIfNeeded();
    });
});

document.addEventListener('livewire:navigated', () => {
    prewarmConditionalLibraries();
    initAnalysisLottieLoadersIfNeeded();
    initGlowingCards();
    initScrollToTopButtons();
    initJobBoardMaps();
});
