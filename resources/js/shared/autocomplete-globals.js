function registerAutocompleteGlobals() {
    if (typeof window.locationAutocomplete !== 'function') {
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
    }

    if (typeof window.getLivewireComponentById !== 'function') {
        window.getLivewireComponentById = function getLivewireComponentById(componentId) {
            if (!componentId || !window.Livewire || typeof window.Livewire.find !== 'function') {
                return null;
            }

            return window.Livewire.find(componentId) || null;
        };
    }

    if (typeof window.callLivewireMethod !== 'function') {
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
    }

    if (typeof window.livewireLocationAutocomplete !== 'function') {
        window.livewireLocationAutocomplete = function livewireLocationAutocomplete({
            componentId,
            endpoint,
            initialValue = '',
            selectMethod,
            clearMethod,
            browserLocationMethod = 'useBrowserLocation',
            allowCurrentLocation = false,
            autoRequestCurrentLocation = false,
            locationCacheTtlMs = 6 * 60 * 60 * 1000,
            autoRequestCooldownMs = 12 * 60 * 60 * 1000,
        } = {}) {
            const baseAutocomplete = window.locationAutocomplete({
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
            });

            return {
                componentId,
                usingCurrentLocation: false,
                geolocationError: '',
                autoLocationBootstrapDone: false,
                ...baseAutocomplete,
                async init() {
                    if (typeof baseAutocomplete.init === 'function') {
                        baseAutocomplete.init.call(this);
                    }

                    await this.bootstrapLocationPreferences();
                },
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
                readCachedLocation() {
                    if (!window.localStorage) {
                        return null;
                    }

                    try {
                        const rawValue = window.localStorage.getItem('novahire:geo:last-location:v1');
                        if (!rawValue) {
                            return null;
                        }

                        const parsed = JSON.parse(rawValue);
                        const timestamp = Number(parsed.timestamp || 0);
                        const latitude = Number(parsed.latitude);
                        const longitude = Number(parsed.longitude);
                        const label = typeof parsed.label === 'string' ? parsed.label : '';

                        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                            return null;
                        }

                        if (!Number.isFinite(timestamp) || Date.now() - timestamp > locationCacheTtlMs) {
                            window.localStorage.removeItem('novahire:geo:last-location:v1');
                            return null;
                        }

                        return { latitude, longitude, label, timestamp };
                    } catch (error) {
                        return null;
                    }
                },
                writeCachedLocation(latitude, longitude, label = '') {
                    if (!window.localStorage) {
                        return;
                    }

                    try {
                        window.localStorage.setItem(
                            'novahire:geo:last-location:v1',
                            JSON.stringify({
                                latitude,
                                longitude,
                                label,
                                timestamp: Date.now(),
                            }),
                        );
                    } catch (error) {
                        // no-op
                    }
                },
                hasRecentAutoRequest() {
                    const inSession = window.sessionStorage
                        ? window.sessionStorage.getItem('novahire:geo:auto-requested:session:v1')
                        : null;

                    if (inSession === '1') {
                        return true;
                    }

                    if (!window.localStorage) {
                        return false;
                    }

                    try {
                        const rawValue = window.localStorage.getItem('novahire:geo:auto-requested-at:v1');
                        const timestamp = Number(rawValue || 0);
                        return Number.isFinite(timestamp) && (Date.now() - timestamp) < autoRequestCooldownMs;
                    } catch (error) {
                        return false;
                    }
                },
                markAutoRequest() {
                    if (window.sessionStorage) {
                        try {
                            window.sessionStorage.setItem('novahire:geo:auto-requested:session:v1', '1');
                        } catch (error) {
                            // no-op
                        }
                    }

                    if (!window.localStorage) {
                        return;
                    }

                    try {
                        window.localStorage.setItem('novahire:geo:auto-requested-at:v1', String(Date.now()));
                    } catch (error) {
                        // no-op
                    }
                },
                buildFallbackLabel(latitude, longitude) {
                    return `Current location (${latitude.toFixed(4)}, ${longitude.toFixed(4)})`;
                },
                isAutoRequestBlocked() {
                    if (!window.localStorage) {
                        return false;
                    }

                    try {
                        return window.localStorage.getItem('novahire:geo:auto-blocked:v1') === '1';
                    } catch (error) {
                        return false;
                    }
                },
                setAutoRequestBlocked(value) {
                    if (!window.localStorage) {
                        return;
                    }

                    try {
                        if (value) {
                            window.localStorage.setItem('novahire:geo:auto-blocked:v1', '1');
                        } else {
                            window.localStorage.removeItem('novahire:geo:auto-blocked:v1');
                        }
                    } catch (error) {
                        // no-op
                    }
                },
                mapGeolocationError(error) {
                    const code = Number(error && error.code);

                    if (code === 1) {
                        return 'Location access was blocked. Enable location permission to see nearby jobs.';
                    }

                    if (code === 2) {
                        return 'Current location is unavailable right now. Check your GPS/network and try again.';
                    }

                    if (code === 3) {
                        return 'Location request timed out. Try again or use a city manually.';
                    }

                    return (error && error.message) || 'Could not access your current location.';
                },
                requestCurrentPosition(options) {
                    return new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, options);
                    });
                },
                async requestBestEffortCoordinates() {
                    const quickAttemptOptions = {
                        enableHighAccuracy: false,
                        timeout: 15000,
                        maximumAge: 15 * 60 * 1000,
                    };
                    const preciseAttemptOptions = {
                        enableHighAccuracy: true,
                        timeout: 20000,
                        maximumAge: 2 * 60 * 1000,
                    };

                    try {
                        return await this.requestCurrentPosition(quickAttemptOptions);
                    } catch (firstError) {
                        const code = Number(firstError && firstError.code);
                        const shouldRetryWithPrecise = code === 2 || code === 3 || !Number.isFinite(code);
                        if (!shouldRetryWithPrecise) {
                            throw firstError;
                        }

                        return this.requestCurrentPosition(preciseAttemptOptions);
                    }
                },
                async applyCoordinates(latitude, longitude, { preferredLabel = '', reportErrors = true, cache = true } = {}) {
                    const component = this.getLivewireComponent();

                    if (!component) {
                        if (reportErrors) {
                            this.geolocationError = 'Livewire component is not available. Refresh the page and try again.';
                        }
                        return false;
                    }

                    const fallbackLabel = preferredLabel || this.buildFallbackLabel(latitude, longitude);

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

                        const finalLabel = resolvedLabel || fallbackLabel;
                        this.query = finalLabel;
                        this.selectedDescription = finalLabel;

                        if (cache) {
                            this.writeCachedLocation(latitude, longitude, finalLabel);
                        }

                        this.setAutoRequestBlocked(false);

                        return true;
                    } catch (error) {
                        if (reportErrors) {
                            this.geolocationError = 'Could not resolve your current location.';
                        }
                        return false;
                    }
                },
                async bootstrapLocationPreferences() {
                    if (!allowCurrentLocation || this.autoLocationBootstrapDone) {
                        return;
                    }

                    this.autoLocationBootstrapDone = true;

                    const hasInitialLocationQuery = String(initialValue || '').trim().length > 0;
                    const cachedLocation = this.readCachedLocation();

                    if (cachedLocation && !hasInitialLocationQuery) {
                        await this.applyCoordinates(cachedLocation.latitude, cachedLocation.longitude, {
                            preferredLabel: cachedLocation.label || '',
                            reportErrors: false,
                            cache: false,
                        });
                    }

                    if (!autoRequestCurrentLocation || hasInitialLocationQuery) {
                        return;
                    }

                    let permissionState = null;
                    if (navigator.permissions && typeof navigator.permissions.query === 'function') {
                        try {
                            const permissionStatus = await navigator.permissions.query({ name: 'geolocation' });
                            permissionState = permissionStatus.state;
                        } catch (error) {
                            permissionState = null;
                        }
                    }

                    if (permissionState === 'denied') {
                        return;
                    }

                    if (this.isAutoRequestBlocked() && permissionState !== 'granted') {
                        return;
                    }

                    if (cachedLocation && permissionState !== 'granted') {
                        return;
                    }

                    if (this.hasRecentAutoRequest() && permissionState !== 'granted') {
                        return;
                    }

                    this.markAutoRequest();
                    await this.useMyLocation({ auto: true, reportErrors: false });
                },
                async useMyLocation({ auto = false, reportErrors = true } = {}) {
                    if (!allowCurrentLocation) {
                        return;
                    }

                    if (!navigator.geolocation) {
                        if (reportErrors) {
                            this.geolocationError = 'Geolocation is not supported in this browser.';
                        }
                        return;
                    }

                    const component = this.getLivewireComponent();

                    if (!component) {
                        if (reportErrors && !auto) {
                            this.geolocationError = 'Livewire component is not available. Refresh the page and try again.';
                        }
                        return;
                    }

                    this.usingCurrentLocation = true;
                    if (reportErrors && !auto) {
                        this.geolocationError = '';
                    }

                    try {
                        const position = await this.requestBestEffortCoordinates();
                        await this.applyCoordinates(position.coords.latitude, position.coords.longitude, {
                            reportErrors: reportErrors && !auto,
                            cache: true,
                        });
                    } catch (error) {
                        if (Number(error && error.code) === 1) {
                            this.setAutoRequestBlocked(true);
                        }

                        if (reportErrors && !auto) {
                            this.geolocationError = this.mapGeolocationError(error);
                        }
                    } finally {
                        this.usingCurrentLocation = false;
                    }
                },
            };
        };
    }

    if (typeof window.asyncSuggestionBox !== 'function') {
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
    }
}

registerAutocompleteGlobals();
