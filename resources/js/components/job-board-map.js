import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const DEFAULT_CENTER = [54.7, -2.5];
const DEFAULT_ZOOM = 5;
const DEFAULT_START_ZOOM = 4;
const MIN_ZOOM = 3;
const MAX_ZOOM = 17;
const FOCUS_DURATION_SECONDS = 0.78;
const WIDE_SHIFT_DURATION_SECONDS = 0.98;
const CINEMATIC_DURATION_SECONDS = 1.35;
const MEDIUM_SHIFT_DISTANCE_KM = 700;
const LARGE_SHIFT_DISTANCE_KM = 1800;

const TILE_PROVIDERS = {
    light: [
        {
            id: 'carto-light',
            url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            maxZoom: 20,
        },
        {
            id: 'osm',
            url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        },
    ],
    dark: [
        {
            id: 'carto-dark',
            url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            maxZoom: 20,
        },
        {
            id: 'osm',
            url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        },
    ],
};

const mapInstances = new Map();
let themeObserver = null;
let lastCinematicKey = null;

function clampZoom(zoom, fallback = DEFAULT_ZOOM) {
    const parsed = Number(zoom);
    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, parsed));
}

function normalizeLatLng(value, fallback = DEFAULT_CENTER) {
    if (!Array.isArray(value) || value.length < 2) {
        return fallback;
    }

    const latitude = Number(value[0]);
    const longitude = Number(value[1]);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return fallback;
    }

    const safeLatitude = Math.max(-90, Math.min(90, latitude));
    const safeLongitude = Math.max(-180, Math.min(180, longitude));

    return [safeLatitude, safeLongitude];
}

function isDarkMode() {
    return document.documentElement.classList.contains('dark');
}

function resolveThemeKey() {
    return isDarkMode() ? 'dark' : 'light';
}

function resolveTileProviders(themeKey = resolveThemeKey()) {
    return TILE_PROVIDERS[themeKey] || TILE_PROVIDERS.light;
}

function decodeBase64Json(encodedValue) {
    if (!encodedValue) {
        return null;
    }

    try {
        const binary = window.atob(encodedValue);
        const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
        const text = new TextDecoder().decode(bytes);
        return JSON.parse(text);
    } catch (error) {
        return null;
    }
}

function markerIcon(kind = 'job') {
    const normalizedKind = kind === 'user' ? 'user' : 'job';

    return L.divIcon({
        className: 'job-board-pin-host',
        html: `<span class="job-board-pin job-board-pin--${normalizedKind}"><span class="job-board-pin-core"></span><span class="job-board-pin-wave"></span></span>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
        popupAnchor: [0, -12],
    });
}

function clearDetachedMaps() {
    for (const [element, instance] of mapInstances.entries()) {
        if (document.body.contains(element)) {
            continue;
        }

        if (typeof instance.clearLoadingFallbackTimer === 'function') {
            instance.clearLoadingFallbackTimer();
        }
        if (typeof instance.clearRuntimeTimers === 'function') {
            instance.clearRuntimeTimers();
        }
        instance.attributeObserver.disconnect();
        instance.map.remove();
        mapInstances.delete(element);
    }

    if (!mapInstances.size && themeObserver) {
        themeObserver.disconnect();
        themeObserver = null;
    }
}

function ensureThemeObserver() {
    if (themeObserver) {
        return;
    }

    themeObserver = new MutationObserver(() => {
        const themeKey = resolveThemeKey();

        for (const instance of mapInstances.values()) {
            instance.applyTheme(themeKey);
        }
    });

    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}

function createMapInstance(element) {
    const initialTheme = resolveThemeKey();
    const initialProviders = resolveTileProviders(initialTheme);
    const initialProvider = initialProviders[0];

    const map = L.map(element, {
        zoomControl: true,
        attributionControl: true,
        preferCanvas: true,
        minZoom: MIN_ZOOM,
        maxZoom: MAX_ZOOM,
        zoomAnimation: true,
        markerZoomAnimation: true,
        fadeAnimation: true,
        worldCopyJump: true,
    });

    const markerLayer = L.layerGroup().addTo(map);
    let tileLayer = null;
    let loadingFallbackTimerId = 0;
    let tileLoadSettledTimerId = 0;
    let isLoading = false;
    let didReceiveTileSuccess = false;
    let tileErrorBurstCount = 0;
    let activeTheme = initialTheme;
    let activeProviders = initialProviders;
    let activeProviderIndex = 0;
    let instance = null;

    const clearLoadingFallbackTimer = () => {
        if (!loadingFallbackTimerId) {
            return;
        }

        window.clearTimeout(loadingFallbackTimerId);
        loadingFallbackTimerId = 0;
    };

    const clearTileLoadSettledTimer = () => {
        if (!tileLoadSettledTimerId) {
            return;
        }

        window.clearTimeout(tileLoadSettledTimerId);
        tileLoadSettledTimerId = 0;
    };

    const clearRuntimeTimers = () => {
        clearLoadingFallbackTimer();
        clearTileLoadSettledTimer();
    };

    const setLoadingState = (isLoading) => {
        const loading = Boolean(isLoading);
        element.classList.toggle('map-is-loading', loading);
        element.dataset.mapLoading = loading ? 'true' : 'false';
    };

    const clearLoading = () => {
        clearRuntimeTimers();
        isLoading = false;
        setLoadingState(false);
    };

    const scheduleLoadingFailover = () => {
        clearLoadingFallbackTimer();
        loadingFallbackTimerId = window.setTimeout(() => {
            if (!isLoading) {
                return;
            }

            if (!didReceiveTileSuccess && instance?.switchToNextTileProvider('timeout')) {
                return;
            }

            clearLoading();
        }, 5200);
    };

    const markLoading = () => {
        if (!isLoading) {
            didReceiveTileSuccess = false;
            tileErrorBurstCount = 0;
        }

        isLoading = true;
        setLoadingState(true);
        scheduleLoadingFailover();
    };

    const createTileLayer = (provider) => {
        if (!provider) {
            return null;
        }

        return L.tileLayer(provider.url, {
            attribution: provider.attribution,
            maxZoom: provider.maxZoom ?? 20,
            detectRetina: true,
            keepBuffer: 6,
            updateWhenIdle: true,
            updateWhenZooming: false,
        });
    };

    const handleTileLoad = () => {
        didReceiveTileSuccess = true;
        tileErrorBurstCount = 0;
        clearTileLoadSettledTimer();
        tileLoadSettledTimerId = window.setTimeout(() => {
            clearLoading();
        }, 140);
    };

    const handleTileError = () => {
        tileErrorBurstCount += 1;
        const failoverThreshold = didReceiveTileSuccess ? 12 : 6;

        if (tileErrorBurstCount >= failoverThreshold && instance?.switchToNextTileProvider('tile-error')) {
            tileErrorBurstCount = 0;
            return;
        }

        scheduleLoadingFailover();
    };

    const bindTileEvents = (layer) => {
        if (!layer) {
            return;
        }

        layer.on('loading', markLoading);
        layer.on('tileloadstart', markLoading);
        layer.on('tileload', handleTileLoad);
        layer.on('load', clearLoading);
        layer.on('tileerror', handleTileError);
    };

    const unbindTileEvents = (layer) => {
        if (!layer) {
            return;
        }

        layer.off('loading', markLoading);
        layer.off('tileloadstart', markLoading);
        layer.off('tileload', handleTileLoad);
        layer.off('load', clearLoading);
        layer.off('tileerror', handleTileError);
    };

    const setTileProvider = (themeKey = activeTheme, providerIndex = 0) => {
        const providers = resolveTileProviders(themeKey);
        if (!providers.length) {
            return false;
        }

        const safeProviderIndex = Math.max(0, Math.min(providers.length - 1, providerIndex));
        const provider = providers[safeProviderIndex];

        if (
            tileLayer &&
            activeTheme === themeKey &&
            activeProviderIndex === safeProviderIndex
        ) {
            return false;
        }

        markLoading();

        const nextTileLayer = createTileLayer(provider);
        bindTileEvents(nextTileLayer);

        if (tileLayer) {
            unbindTileEvents(tileLayer);
            tileLayer.remove();
        }

        tileLayer = nextTileLayer;
        tileLayer.addTo(map);

        activeTheme = themeKey;
        activeProviders = providers;
        activeProviderIndex = safeProviderIndex;
        tileErrorBurstCount = 0;
        didReceiveTileSuccess = false;

        element.dataset.mapTileTheme = activeTheme;
        element.dataset.mapTileProvider = provider.id || '';

        if (instance) {
            instance.tileLayer = tileLayer;
            instance.tileUrl = provider.url;
            instance.tileProviderId = provider.id || '';
            instance.activeTheme = activeTheme;
        }

        return true;
    };

    setTileProvider(initialTheme, 0);

    map.on('moveend zoomend', () => {
        window.setTimeout(clearLoading, 220);
    });

    map.setView(DEFAULT_CENTER, DEFAULT_ZOOM, {
        animate: false,
    });

    const attributeObserver = new MutationObserver((mutations) => {
        if (!mutations.some((mutation) => mutation.attributeName === 'data-map-config')) {
            return;
        }

        mountJobBoardMap(element);
    });

    attributeObserver.observe(element, {
        attributes: true,
        attributeFilter: ['data-map-config'],
    });

    instance = {
        map,
        tileLayer,
        tileUrl: initialProvider?.url || '',
        tileProviderId: initialProvider?.id || '',
        markerLayer,
        attributeObserver,
        lastConfigHash: '',
        activeTheme,
        setTileProvider,
        applyTheme: (themeKey = resolveThemeKey()) => setTileProvider(themeKey, 0),
        syncTheme: (themeKey = resolveThemeKey()) => (
            themeKey !== activeTheme ? setTileProvider(themeKey, 0) : false
        ),
        switchToNextTileProvider: () => {
            const nextProviderIndex = activeProviderIndex + 1;
            if (nextProviderIndex >= activeProviders.length) {
                return false;
            }

            return setTileProvider(activeTheme, nextProviderIndex);
        },
        markLoading,
        clearLoading,
        clearLoadingFallbackTimer,
        clearRuntimeTimers,
    };

    mapInstances.set(element, instance);
    ensureThemeObserver();

    window.setTimeout(() => {
        map.invalidateSize({
            pan: false,
        });
        clearLoading();
    }, 0);

    return instance;
}

function getOrCreateMapInstance(element) {
    const existing = mapInstances.get(element);
    if (existing) {
        return existing;
    }

    return createMapInstance(element);
}

function addMarkers(markerLayer, markers) {
    const coordinates = [];
    let firstPopupMarker = null;

    markers.forEach((marker) => {
        const latitude = Number(marker?.lat);
        const longitude = Number(marker?.lng);

        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            return;
        }

        const markerInstance = L.marker([latitude, longitude], {
            icon: markerIcon(marker?.kind),
            keyboard: false,
        });

        const label = typeof marker?.label === 'string' ? marker.label.trim() : '';
        if (label) {
            markerInstance.bindPopup(label, {
                closeButton: true,
                autoPan: true,
            });
        }

        markerInstance.addTo(markerLayer);
        coordinates.push([latitude, longitude]);

        if (!firstPopupMarker && marker?.openPopup === true && label) {
            firstPopupMarker = markerInstance;
        }
    });

    if (firstPopupMarker) {
        firstPopupMarker.openPopup();
    }

    return coordinates;
}

function hasMeaningfulShift(map, center, zoom) {
    const currentCenter = map.getCenter();
    const currentZoom = map.getZoom();
    const latDiff = Math.abs(currentCenter.lat - center[0]);
    const lngDiff = Math.abs(currentCenter.lng - center[1]);

    return latDiff > 0.0005 || lngDiff > 0.0005 || currentZoom !== zoom;
}

function centerShiftDistanceKm(map, center) {
    const currentCenter = map.getCenter();
    const targetCenter = L.latLng(center[0], center[1]);
    return map.distance(currentCenter, targetCenter) / 1000;
}

function applyMapConfig(instance, config) {
    const configHash = JSON.stringify(config || {});
    if (instance.lastConfigHash === configHash) {
        return;
    }
    instance.lastConfigHash = configHash;

    instance.syncTheme(resolveThemeKey());

    const markers = Array.isArray(config?.markers) ? config.markers : [];
    instance.markerLayer.clearLayers();
    const markerCoordinates = addMarkers(instance.markerLayer, markers);

    const center = normalizeLatLng(
        config?.center,
        markerCoordinates[0] || DEFAULT_CENTER
    );
    const zoom = clampZoom(config?.zoom, markerCoordinates.length ? 12 : DEFAULT_ZOOM);
    const startZoom = clampZoom(config?.startZoom, DEFAULT_START_ZOOM);
    const cinematicKey = typeof config?.animateKey === 'string' ? config.animateKey : null;
    const shouldRunCinematic =
        config?.cinematic === true &&
        cinematicKey &&
        cinematicKey !== lastCinematicKey;

    instance.markLoading();
    instance.map.invalidateSize({ pan: false });

    if (shouldRunCinematic) {
        instance.map.stop();
        const cinematicStartZoom = clampZoom(startZoom, Math.min(zoom, 8));
        instance.map.setView(center, cinematicStartZoom, { animate: false });
        instance.map.flyTo(center, zoom, {
            animate: true,
            duration: CINEMATIC_DURATION_SECONDS,
            easeLinearity: 0.26,
            noMoveStart: true,
        });

        lastCinematicKey = cinematicKey;
        return;
    }

    const fitToMarkers = config?.fitToMarkers === true && markerCoordinates.length > 1;
    if (fitToMarkers) {
        const bounds = L.latLngBounds(markerCoordinates);
        if (bounds.isValid()) {
            instance.map.flyToBounds(bounds.pad(0.16), {
                animate: true,
                duration: 1,
                maxZoom: zoom,
            });
            return;
        }
    }

    const shiftDistanceKm = centerShiftDistanceKm(instance.map, center);
    const zoomDelta = Math.abs(instance.map.getZoom() - zoom);

    if (shiftDistanceKm >= LARGE_SHIFT_DISTANCE_KM || zoomDelta >= 6) {
        const stagingZoom = clampZoom(Math.min(zoom, 9), DEFAULT_ZOOM);
        instance.map.setView(center, stagingZoom, { animate: false });

        if (stagingZoom !== zoom) {
            instance.map.flyTo(center, zoom, {
                animate: true,
                duration: 0.9,
                easeLinearity: 0.28,
                noMoveStart: true,
            });
        }

        return;
    }

    const transitionDuration = shiftDistanceKm >= MEDIUM_SHIFT_DISTANCE_KM
        ? WIDE_SHIFT_DURATION_SECONDS
        : FOCUS_DURATION_SECONDS;

    if (hasMeaningfulShift(instance.map, center, zoom)) {
        instance.map.flyTo(center, zoom, {
            animate: true,
            duration: transitionDuration,
            easeLinearity: 0.28,
            noMoveStart: true,
        });
        return;
    }

    instance.map.setView(center, zoom, {
        animate: false,
    });
}

export function mountJobBoardMap(element) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    clearDetachedMaps();

    const config = decodeBase64Json(element.dataset.mapConfig || '');
    if (!config) {
        return;
    }

    const instance = getOrCreateMapInstance(element);
    applyMapConfig(instance, config);
}

export function initJobBoardMaps(root = document) {
    const scope = root instanceof Element || root instanceof Document ? root : document;
    const mapElements = scope.querySelectorAll('[data-job-board-map]');

    mapElements.forEach((element) => {
        mountJobBoardMap(element);
    });

    clearDetachedMaps();
}

window.mountJobBoardMap = mountJobBoardMap;
window.initJobBoardMaps = initJobBoardMaps;
window.dispatchEvent(new CustomEvent('job-board-map:ready'));
