import './bootstrap';
import { createIcons, icons } from 'lucide';
import { initLandingHeroes } from './react/init-landing-hero';

// flatpickr
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
// Chart tools
import Chart from 'chart.js/auto';

window.flatpickr = flatpickr;
window.Chart = Chart;
window.createIcons = createIcons;
window.lucideIcons = icons;

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
        Alpine.store('sidebar', {
            isExpanded: true,
            isHovered: false,
            isMobileOpen: false,
            init() {
                const storedExpanded = window.localStorage.getItem('sidebar-expanded');
                if (storedExpanded === 'true') {
                    this.isExpanded = true;
                } else if (storedExpanded === 'false') {
                    this.isExpanded = false;
                } else {
                    this.isExpanded = true;
                }

                this.handleResize = () => {
                    if (window.innerWidth >= 1280) {
                        this.isMobileOpen = false;
                    }
                };

                window.addEventListener('resize', this.handleResize, { passive: true });
                this.handleResize();
            },
            setExpanded(value) {
                this.isExpanded = Boolean(value);
                window.localStorage.setItem('sidebar-expanded', this.isExpanded ? 'true' : 'false');
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

window.locationAutocomplete = function locationAutocomplete({
    endpoint,
    initialValue = '',
    minChars = 2,
    onInput = null,
    onSelect = null,
    onClear = null,
} = {}) {
    return {
        query: initialValue,
        suggestions: [],
        loading: false,
        open: false,
        configured: true,
        provider: null,
        usedFallbackOnly: false,
        feedbackMessage: '',
        highlightedIndex: -1,
        selectedDescription: initialValue || null,
        selectedPlaceId: null,
        debounceTimer: null,
        abortController: null,
        sessionToken: null,
        suppressInputHandlerOnce: false,
        init() {
            this.sessionToken = this.makeSessionToken();
        },
        syncInputValue() {
            if (!this.$refs || !this.$refs.input) {
                return;
            }

            this.$refs.input.value = this.query;
            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
        },
        makeSessionToken() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }

            return `loc-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
        },
        handleFocus() {
            if (this.query.trim().length >= minChars && this.suggestions.length) {
                this.open = true;
            }
        },
        handleInput(event) {
            this.query = event.target.value;

            if (this.suppressInputHandlerOnce) {
                this.suppressInputHandlerOnce = false;
                return;
            }

            const hadSelection = this.selectedDescription !== null;
            const selectionDirty = hadSelection && this.query !== this.selectedDescription;

            if (selectionDirty) {
                this.selectedDescription = null;
                this.selectedPlaceId = null;
                if (typeof onClear === 'function') {
                    onClear();
                }
            }

            if (typeof onInput === 'function') {
                onInput.call(this, this.query, {
                    selectionDirty,
                    selectedPlaceId: this.selectedPlaceId,
                });
            }

            clearTimeout(this.debounceTimer);
            const query = this.query.trim();

            if (query.length < minChars) {
                if (this.abortController) {
                    this.abortController.abort();
                }
                this.suggestions = [];
                this.open = false;
                this.loading = false;
                this.feedbackMessage = '';
                this.highlightedIndex = -1;
                return;
            }

            this.loading = true;
            this.debounceTimer = setTimeout(() => this.fetchSuggestions(query), 250);
        },
        async fetchSuggestions(query = '') {
            const searchQuery = typeof query === 'string' ? query.trim() : this.query.trim();
            if (searchQuery.length < minChars) {
                this.loading = false;
                return;
            }

            if (this.abortController) {
                this.abortController.abort();
            }
            this.abortController = new AbortController();

            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set('q', searchQuery);
                url.searchParams.set('session_token', this.sessionToken);

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                    },
                    signal: this.abortController.signal,
                });

                if (!response.ok) {
                    throw new Error(`Autocomplete request failed with ${response.status}`);
                }

                const payload = await response.json();
                if (searchQuery !== this.query.trim()) {
                    return;
                }

                this.configured = payload.configured !== false;
                this.provider = payload.provider || null;
                this.usedFallbackOnly = payload.used_fallback_only === true;
                this.suggestions = Array.isArray(payload.suggestions) ? payload.suggestions : [];
                this.open = this.suggestions.length > 0;
                this.highlightedIndex = this.suggestions.length ? 0 : -1;
                this.feedbackMessage = this.resolveFeedbackMessage();
            } catch (error) {
                if (error.name !== 'AbortError') {
                    if (searchQuery === this.query.trim()) {
                        this.suggestions = [];
                        this.open = false;
                        this.feedbackMessage = 'Location lookup failed. Check your maps configuration or try again.';
                    }
                }
            } finally {
                if (searchQuery === this.query.trim()) {
                    this.loading = false;
                }
            }
        },
        resolveFeedbackMessage() {
            if (this.suggestions.length) {
                if (!this.configured) {
                    return 'Maps provider is not configured. Showing local fallback suggestions only.';
                }

                if (this.usedFallbackOnly) {
                    return 'Showing fallback suggestions because the live maps provider returned no results.';
                }

                return '';
            }

            if (!this.configured) {
                return 'Maps provider is not configured. Add your Geoapify key in .env and rebuild assets.';
            }

            return this.query.trim().length >= minChars
                ? 'No matching locations found.'
                : '';
        },
        selectSuggestion(suggestion) {
            this.query = suggestion.description || '';
            this.selectedDescription = this.query;
            this.selectedPlaceId = suggestion.place_id || null;
            this.suggestions = [];
            this.open = false;
            this.highlightedIndex = -1;
            this.feedbackMessage = '';
            this.suppressInputHandlerOnce = true;
            this.syncInputValue();
            if (typeof onInput === 'function') {
                onInput.call(this, this.query, {
                    selectionDirty: false,
                    selectedPlaceId: this.selectedPlaceId,
                });
            }
            if (typeof onSelect === 'function') {
                onSelect.call(this, suggestion, this.sessionToken);
            }
            this.sessionToken = this.makeSessionToken();
        },
        chooseHighlighted() {
            if (this.highlightedIndex < 0 || !this.suggestions[this.highlightedIndex]) {
                return;
            }

            this.selectSuggestion(this.suggestions[this.highlightedIndex]);
        },
        moveHighlight(delta) {
            if (!this.suggestions.length) {
                return;
            }

            const nextIndex = this.highlightedIndex + delta;
            if (nextIndex < 0) {
                this.highlightedIndex = this.suggestions.length - 1;
                return;
            }

            if (nextIndex >= this.suggestions.length) {
                this.highlightedIndex = 0;
                return;
            }

            this.highlightedIndex = nextIndex;
        },
        clearQuery() {
            this.query = '';
            this.suggestions = [];
            this.open = false;
            this.loading = false;
            this.highlightedIndex = -1;
            this.feedbackMessage = '';
            this.selectedDescription = null;
            this.selectedPlaceId = null;
            this.suppressInputHandlerOnce = true;
            this.syncInputValue();
            if (typeof onInput === 'function') {
                onInput.call(this, '', {
                    selectionDirty: true,
                    selectedPlaceId: null,
                });
            }
            if (typeof onClear === 'function') {
                onClear.call(this);
            }
            this.sessionToken = this.makeSessionToken();
        },
        close() {
            window.setTimeout(() => {
                this.open = false;
            }, 120);
        },
    };
};

window.getLivewireComponentById = function getLivewireComponentById(componentId) {
    if (!componentId || !window.Livewire || typeof window.Livewire.find !== 'function') {
        return null;
    }

    return window.Livewire.find(componentId) || null;
};

window.callLivewireMethod = async function callLivewireMethod(component, method, args = []) {
    if (!component || !method) {
        throw new Error('Livewire component or method is missing.');
    }

    if (typeof component.call === 'function') {
        return component.call(method, ...args);
    }

    if (typeof component.$call === 'function') {
        return component.$call(method, ...args);
    }

    if (component.$wire && typeof component.$wire.$call === 'function') {
        return component.$wire.$call(method, ...args);
    }

    if (component.$wire && typeof component.$wire[method] === 'function') {
        return component.$wire[method](...args);
    }

    if (typeof component[method] === 'function') {
        return component[method](...args);
    }

    throw new Error(`Livewire method "${method}" is not callable.`);
};

window.livewireLocationAutocomplete = function livewireLocationAutocomplete({
    componentId,
    endpoint,
    initialValue = '',
    selectMethod,
    clearMethod,
    browserLocationMethod = 'useBrowserLocation',
    allowCurrentLocation = false,
} = {}) {
    return {
        componentId,
        usingCurrentLocation: false,
        geolocationError: '',
        ...window.locationAutocomplete({
            endpoint,
            initialValue,
            onSelect: async function (suggestion, sessionToken) {
                const component = this.getLivewireComponent();
                const args = [
                    suggestion.description,
                    suggestion.place_id || '',
                    sessionToken,
                    suggestion.latitude !== undefined ? suggestion.latitude : null,
                    suggestion.longitude !== undefined ? suggestion.longitude : null,
                    suggestion.city || '',
                    suggestion.region || '',
                    suggestion.country_code || '',
                ];

                if (!component) {
                    this.feedbackMessage = 'Livewire component is not available. Refresh the page and try again.';
                    return;
                }

                try {
                    await window.callLivewireMethod(component, selectMethod, args);
                } catch (error) {
                    this.feedbackMessage = 'Could not update the selected location. Try again.';
                }
            },
            onClear: function () {
                const component = this.getLivewireComponent();

                if (!component) {
                    return;
                }

                window.callLivewireMethod(component, clearMethod, [false]).catch(() => {});
            },
        }),
        resolveLivewireComponentId() {
            if (this.componentId) {
                return this.componentId;
            }

            const root = this.$root || this.$el || null;

            if (!root || typeof root.closest !== 'function') {
                return null;
            }

            const host = root.closest('[wire\\:id]');
            return host ? host.getAttribute('wire:id') : null;
        },
        getLivewireComponent() {
            const resolvedComponentId = this.resolveLivewireComponentId();

            if (!resolvedComponentId) {
                return null;
            }

            this.componentId = resolvedComponentId;

            return window.getLivewireComponentById(resolvedComponentId);
        },
        async useMyLocation() {
            if (!allowCurrentLocation) {
                return;
            }

            if (!navigator.geolocation) {
                this.geolocationError = 'Geolocation is not supported in this browser.';
                return;
            }

            const component = this.getLivewireComponent();

            if (!component) {
                this.geolocationError = 'Livewire component is not available. Refresh the page and try again.';
                return;
            }

            this.usingCurrentLocation = true;
            this.geolocationError = '';

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    const fallbackLabel = `Current location (${latitude.toFixed(4)}, ${longitude.toFixed(4)})`;

                    this.query = fallbackLabel;
                    this.selectedDescription = fallbackLabel;
                    this.selectedPlaceId = null;
                    this.suggestions = [];
                    this.open = false;
                    this.loading = false;
                    this.highlightedIndex = -1;
                    this.feedbackMessage = '';

                    try {
                        const resolvedLabel = await window.callLivewireMethod(component, browserLocationMethod, [
                            latitude,
                            longitude,
                        ]);

                        this.query = resolvedLabel || fallbackLabel;
                        this.selectedDescription = this.query;
                    } catch (error) {
                        this.geolocationError = 'Could not resolve your current location.';
                    } finally {
                        this.usingCurrentLocation = false;
                    }
                },
                (error) => {
                    this.geolocationError = error.message || 'Could not access your current location.';
                    this.usingCurrentLocation = false;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000,
                }
            );
        },
    };
};

window.asyncSuggestionBox = function asyncSuggestionBox({
    endpoint,
    initialValue = '',
    minChars = 2,
    queryParam = 'q',
    onInput = null,
    onSelect = null,
    onClear = null,
} = {}) {
    return {
        query: initialValue,
        suggestions: [],
        loading: false,
        open: false,
        highlightedIndex: -1,
        debounceTimer: null,
        abortController: null,
        suppressInputHandlerOnce: false,
        syncInputValue() {
            if (!this.$refs || !this.$refs.input) {
                return;
            }

            this.$refs.input.value = this.query;
            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
        },
        handleFocus() {
            if (this.query.trim().length >= minChars && this.suggestions.length) {
                this.open = true;
            }
        },
        handleInput(event) {
            this.query = event.target.value;

            if (this.suppressInputHandlerOnce) {
                this.suppressInputHandlerOnce = false;
                return;
            }

            if (typeof onInput === 'function') {
                onInput.call(this, this.query);
            }
            clearTimeout(this.debounceTimer);
            const query = this.query.trim();

            if (query.length < minChars) {
                if (this.abortController) {
                    this.abortController.abort();
                }
                this.suggestions = [];
                this.open = false;
                this.loading = false;
                this.highlightedIndex = -1;
                if (!this.query.trim()) {
                    if (typeof onClear === 'function') {
                        onClear();
                    }
                }
                return;
            }

            this.loading = true;
            this.debounceTimer = setTimeout(() => this.fetchSuggestions(query), 220);
        },
        async fetchSuggestions(query = '') {
            const searchQuery = typeof query === 'string' ? query.trim() : this.query.trim();
            if (searchQuery.length < minChars) {
                this.loading = false;
                return;
            }

            if (this.abortController) {
                this.abortController.abort();
            }
            this.abortController = new AbortController();

            try {
                const url = new URL(endpoint, window.location.origin);
                url.searchParams.set(queryParam, searchQuery);

                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
                    signal: this.abortController.signal,
                });

                if (!response.ok) {
                    throw new Error(`Suggestion request failed with ${response.status}`);
                }

                const payload = await response.json();
                if (searchQuery !== this.query.trim()) {
                    return;
                }

                this.suggestions = Array.isArray(payload.suggestions) ? payload.suggestions : [];
                this.open = this.suggestions.length > 0;
                this.highlightedIndex = this.suggestions.length ? 0 : -1;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    if (searchQuery === this.query.trim()) {
                        this.suggestions = [];
                        this.open = false;
                    }
                }
            } finally {
                if (searchQuery === this.query.trim()) {
                    this.loading = false;
                }
            }
        },
        selectSuggestion(suggestion) {
            this.query = suggestion.label || '';
            this.suggestions = [];
            this.open = false;
            this.highlightedIndex = -1;
            this.suppressInputHandlerOnce = true;
            this.syncInputValue();
            if (typeof onInput === 'function') {
                onInput.call(this, this.query);
            }
            if (typeof onSelect === 'function') {
                onSelect.call(this, suggestion);
            }
        },
        chooseHighlighted() {
            if (this.highlightedIndex < 0 || !this.suggestions[this.highlightedIndex]) {
                return;
            }
            this.selectSuggestion(this.suggestions[this.highlightedIndex]);
        },
        moveHighlight(delta) {
            if (!this.suggestions.length) {
                return;
            }

            const nextIndex = this.highlightedIndex + delta;
            if (nextIndex < 0) {
                this.highlightedIndex = this.suggestions.length - 1;
                return;
            }
            if (nextIndex >= this.suggestions.length) {
                this.highlightedIndex = 0;
                return;
            }

            this.highlightedIndex = nextIndex;
        },
        clearQuery() {
            this.query = '';
            this.suggestions = [];
            this.open = false;
            this.loading = false;
            this.highlightedIndex = -1;
            this.suppressInputHandlerOnce = true;
            this.syncInputValue();
            if (typeof onInput === 'function') {
                onInput.call(this, '');
            }
            if (typeof onClear === 'function') {
                onClear.call(this);
            }
        },
        close() {
            window.setTimeout(() => {
                this.open = false;
            }, 120);
        },
    };
};

let apexChartsPromise;
let jobBoardMapPromise;
const glowCardSelector = '.glow-card, .app-card, .app-subcard, .nh-card, [data-glow-card]';
const silkBackgroundSelector = '[data-silk-bg]';

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
let silkBackgroundsCleanup;

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
        const shouldAnimate = canvas.dataset.silkAnimate === 'true' && !prefersReducedMotion;

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

            if (shouldAnimate && !document.hidden) {
                rafId = requestAnimationFrame(draw);
            }
        };

        resize();
        draw();

        const handleResize = () => {
            resize();
            if (!shouldAnimate) {
                draw();
            }
        };

        const handleVisibilityChange = () => {
            if (!shouldAnimate) {
                return;
            }

            if (document.hidden) {
                if (rafId) {
                    cancelAnimationFrame(rafId);
                    rafId = 0;
                }
                return;
            }

            if (!rafId) {
                rafId = requestAnimationFrame(draw);
            }
        };

        window.addEventListener('resize', handleResize, { passive: true });
        document.addEventListener('visibilitychange', handleVisibilityChange, { passive: true });

        cleanups.push(() => {
            if (rafId) {
                cancelAnimationFrame(rafId);
            }
            window.removeEventListener('resize', handleResize);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        });
    });

    silkBackgroundsCleanup = () => {
        cleanups.forEach((cleanup) => cleanup());
    };
}

function launchConfettiBurst(originX, originY) {
    const canvas = document.createElement('canvas');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    canvas.className = 'pointer-events-none fixed inset-0 z-[9999]';
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        canvas.remove();
        return;
    }

    const colors = ['#465fff', '#38bdf8', '#22c55e', '#f59e0b'];
    const particles = Array.from({ length: 52 }, (_, index) => {
        const angle = (Math.PI * 2 * index) / 52 + (Math.random() - 0.5) * 0.25;
        const speed = 3 + Math.random() * 6;
        return {
            x: originX,
            y: originY,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed - 3,
            size: 2 + Math.random() * 4,
            color: colors[Math.floor(Math.random() * colors.length)],
            life: 42 + Math.floor(Math.random() * 22),
        };
    });

    let rafId = 0;
    const tick = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach((particle) => {
            particle.x += particle.vx;
            particle.y += particle.vy;
            particle.vy += 0.16;
            particle.life -= 1;

            if (particle.life > 0) {
                ctx.globalAlpha = Math.max(0, particle.life / 60);
                ctx.fillStyle = particle.color;
                ctx.fillRect(particle.x, particle.y, particle.size, particle.size);
            }
        });

        if (particles.some((particle) => particle.life > 0)) {
            rafId = requestAnimationFrame(tick);
        } else {
            cancelAnimationFrame(rafId);
            canvas.remove();
        }
    };

    tick();
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
        const billedEls = Array.from(widget.querySelectorAll('[data-pricing-billed]'));
        const billingCycleInputs = Array.from(widget.querySelectorAll('[data-billing-cycle-input]'));
        let activeAnimationFrame = null;
        let switchAnimationFrame = null;

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
                    const annual = annualRaw > 0 ? annualRaw : (monthly * 12 * 0.8);
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
                    const annual = annualRaw > 0 ? annualRaw : (monthly * 12 * 0.8);
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

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', async () => {
    initLandingHeroes();

    // Local lucide icons (avoid third-party CDN blocking)
    createIcons({ icons });

    // Landing: reveal-on-scroll animations
    const revealEls = Array.from(document.querySelectorAll('[data-animate="reveal"]'));
    if (revealEls.length) {
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) {
            revealEls.forEach((el) => el.classList.add('is-in'));
        } else {
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
    }

    // Landing: split text (words) for hero headlines
    const splitEls = Array.from(document.querySelectorAll('[data-split="words"]'));
    if (splitEls.length) {
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        splitEls.forEach((el) => {
            if (el.dataset.splitDone === '1') return;
            const raw = (el.textContent || '').trim();
            if (!raw) return;

            const words = raw.split(/\s+/g);
            el.textContent = '';
            el.classList.add('nh-split');

            words.forEach((w, i) => {
                const span = document.createElement('span');
                span.textContent = w + (i === words.length - 1 ? '' : ' ');
                span.style.animationDelay = `${80 + i * 55}ms`;
                el.appendChild(span);
            });

            el.dataset.splitDone = '1';
            if (prefersReduced) {
                el.classList.add('is-in');
            }
        });
    }

    await initJobBoardMaps();

    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports
    if (document.querySelector('#chartOne')) {
        await ensureApexCharts();
        import('./components/chart/chart-1').then(module => module.initChartOne());
    }
    if (document.querySelector('#chartTwo')) {
        await ensureApexCharts();
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }
    if (document.querySelector('#chartThree')) {
        await ensureApexCharts();
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
    if (document.querySelector('#chartSix')) {
        await ensureApexCharts();
        import('./components/chart/chart-6').then(module => module.initChartSix());
    }
    if (document.querySelector('#chartEight')) {
        await ensureApexCharts();
        import('./components/chart/chart-8').then(module => module.initChartEight());
    }
    if (document.querySelector('#chartThirteen')) {
        await ensureApexCharts();
        import('./components/chart/chart-13').then(module => module.initChartThirteen());
    }

    // Calendar init
    if (document.querySelector('#calendar')) {
        const { Calendar } = await import('@fullcalendar/core');
        window.FullCalendar = Calendar;
        import('./components/calendar-init').then(module => module.calendarInit());
    }

    // Kanban drag/drop only needs Sortable on that page.
    if (document.querySelector('[data-kanban-board]')) {
        const { default: Sortable } = await import('sortablejs');
        window.Sortable = Sortable;
    }

    initGlowingCards();
    initSilkBackgrounds();
    initPricingWidgets();
});

document.addEventListener('livewire:navigated', () => {
    initLandingHeroes();
    initGlowingCards();
    initSilkBackgrounds();
    initPricingWidgets();
    initJobBoardMaps();
});
