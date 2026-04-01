import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const DEFAULT_CENTER = [54.7, -2.5];
const DEFAULT_ZOOM = 5;
const DEFAULT_START_ZOOM = 4;
const MIN_ZOOM = 3;
const MAX_ZOOM = 17;
const FOCUS_DURATION_SECONDS = 1.2;
const CINEMATIC_DURATION_SECONDS = 2.8;

const TILE_CONFIG = {
    light: {
        url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    },
    dark: {
        url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    },
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

function resolveTileConfig() {
    return isDarkMode() ? TILE_CONFIG.dark : TILE_CONFIG.light;
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
        for (const instance of mapInstances.values()) {
            const tileConfig = resolveTileConfig();

            if (instance.tileUrl === tileConfig.url) {
                continue;
            }

            instance.tileUrl = tileConfig.url;
            instance.tileLayer.setUrl(tileConfig.url);
        }
    });

    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}

function createMapInstance(element) {
    const tileConfig = resolveTileConfig();

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

    const tileLayer = L.tileLayer(tileConfig.url, {
        attribution: tileConfig.attribution,
        maxZoom: 20,
        detectRetina: true,
    });

    tileLayer.addTo(map);

    const markerLayer = L.layerGroup().addTo(map);

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

    const instance = {
        map,
        tileLayer,
        tileUrl: tileConfig.url,
        markerLayer,
        attributeObserver,
        lastConfigHash: '',
    };

    mapInstances.set(element, instance);
    ensureThemeObserver();

    window.setTimeout(() => {
        map.invalidateSize({
            pan: false,
        });
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

function applyMapConfig(instance, config) {
    const configHash = JSON.stringify(config || {});
    if (instance.lastConfigHash === configHash) {
        return;
    }
    instance.lastConfigHash = configHash;

    const tileConfig = resolveTileConfig();
    if (instance.tileUrl !== tileConfig.url) {
        instance.tileUrl = tileConfig.url;
        instance.tileLayer.setUrl(tileConfig.url);
    }

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

    if (shouldRunCinematic) {
        instance.map.stop();
        instance.map.setView(DEFAULT_CENTER, startZoom, {
            animate: false,
        });

        window.requestAnimationFrame(() => {
            instance.map.flyTo(center, zoom, {
                animate: true,
                duration: CINEMATIC_DURATION_SECONDS,
                easeLinearity: 0.18,
                noMoveStart: true,
            });
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

    if (hasMeaningfulShift(instance.map, center, zoom)) {
        instance.map.flyTo(center, zoom, {
            animate: true,
            duration: FOCUS_DURATION_SECONDS,
            easeLinearity: 0.25,
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
