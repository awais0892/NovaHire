<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LocationSearchService
{
    private const POPULAR_LOCATION_FALLBACKS = [
        ['label' => 'London, United Kingdom', 'city' => 'London', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 51.5074, 'longitude' => -0.1278],
        ['label' => 'Manchester, United Kingdom', 'city' => 'Manchester', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 53.4808, 'longitude' => -2.2426],
        ['label' => 'Birmingham, United Kingdom', 'city' => 'Birmingham', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 52.4862, 'longitude' => -1.8904],
        ['label' => 'Liverpool, United Kingdom', 'city' => 'Liverpool', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 53.4084, 'longitude' => -2.9916],
        ['label' => 'Leeds, United Kingdom', 'city' => 'Leeds', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 53.8008, 'longitude' => -1.5491],
        ['label' => 'Bristol, United Kingdom', 'city' => 'Bristol', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 51.4545, 'longitude' => -2.5879],
        ['label' => 'Sheffield, United Kingdom', 'city' => 'Sheffield', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 53.3811, 'longitude' => -1.4701],
        ['label' => 'Newcastle upon Tyne, United Kingdom', 'city' => 'Newcastle upon Tyne', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 54.9783, 'longitude' => -1.6178],
        ['label' => 'Nottingham, United Kingdom', 'city' => 'Nottingham', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 52.9548, 'longitude' => -1.1581],
        ['label' => 'Leicester, United Kingdom', 'city' => 'Leicester', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 52.6369, 'longitude' => -1.1398],
        ['label' => 'Southampton, United Kingdom', 'city' => 'Southampton', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 50.9097, 'longitude' => -1.4044],
        ['label' => 'Oxford, United Kingdom', 'city' => 'Oxford', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 51.7520, 'longitude' => -1.2577],
        ['label' => 'Cambridge, United Kingdom', 'city' => 'Cambridge', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 52.2053, 'longitude' => 0.1218],
        ['label' => 'Edinburgh, United Kingdom', 'city' => 'Edinburgh', 'region' => 'Scotland', 'country_code' => 'GB', 'latitude' => 55.9533, 'longitude' => -3.1883],
        ['label' => 'Glasgow, United Kingdom', 'city' => 'Glasgow', 'region' => 'Scotland', 'country_code' => 'GB', 'latitude' => 55.8642, 'longitude' => -4.2518],
        ['label' => 'Cardiff, United Kingdom', 'city' => 'Cardiff', 'region' => 'Wales', 'country_code' => 'GB', 'latitude' => 51.4816, 'longitude' => -3.1791],
        ['label' => 'Belfast, United Kingdom', 'city' => 'Belfast', 'region' => 'Northern Ireland', 'country_code' => 'GB', 'latitude' => 54.5973, 'longitude' => -5.9301],
        ['label' => 'Aberdeen, United Kingdom', 'city' => 'Aberdeen', 'region' => 'Scotland', 'country_code' => 'GB', 'latitude' => 57.1497, 'longitude' => -2.0943],
        ['label' => 'York, United Kingdom', 'city' => 'York', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 53.9600, 'longitude' => -1.0873],
        ['label' => 'Brighton, United Kingdom', 'city' => 'Brighton', 'region' => 'England', 'country_code' => 'GB', 'latitude' => 50.8225, 'longitude' => -0.1372],
    ];
    public function provider(): string
    {
        return (string) config('services.maps.provider', 'geoapify');
    }

    public function isConfigured(): bool
    {
        return match ($this->provider()) {
            'google' => filled(config('services.google_maps.key')),
            'geoapify' => filled(config('services.geoapify.key')),
            default => false,
        };
    }

    private function httpClient()
    {
        return Http::timeout((int) config('services.maps.timeout', 5))
            ->retry(
                (int) config('services.maps.retry_times', 2),
                (int) config('services.maps.retry_sleep_ms', 200)
            );
    }

    private function mapsLocale(): string
    {
        return (string) (config('services.maps.locale') ?: app()->getLocale());
    }

    private function buildGeoapifyUrl(string $pathConfigKey, bool $mapsHost = false): string
    {
        $path = (string) config($pathConfigKey);
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $baseKey = $mapsHost ? 'services.geoapify.maps_base_url' : 'services.geoapify.base_url';
        $baseUrl = rtrim((string) config($baseKey), '/');
        $normalizedPath = '/' . ltrim($path, '/');

        return $baseUrl . $normalizedPath;
    }

    private function safeGet(string $url, array $query, string $operation, array $context = [])
    {
        try {
            return $this->httpClient()->get($url, $query);
        } catch (Throwable $exception) {
            $this->logProviderIssue($operation, 'request threw exception', array_merge($context, [
                'url' => $url,
                'exception' => $exception->getMessage(),
            ]));

            return null;
        }
    }

    private function shouldLogProviderIssues(): bool
    {
        return (bool) config('services.maps.log_failures', true);
    }

    private function logProviderIssue(string $operation, string $message, array $context = []): void
    {
        if (!$this->shouldLogProviderIssues()) {
            return;
        }

        Log::warning('Location provider issue: ' . $message, array_merge([
            'provider' => $this->provider(),
            'operation' => $operation,
            'country' => $this->providerCountry(),
        ], $context));
    }

    public function autocomplete(string $query, ?string $sessionToken = null): array
    {
        if (!$this->isConfigured()) {
            $this->logProviderIssue('autocomplete', 'provider not configured', [
                'query' => $query,
            ]);
            return [];
        }

        $normalizedQuery = trim($query);
        if (mb_strlen($normalizedQuery) < 3) {
            return [];
        }

        if (str_starts_with(mb_strtolower($normalizedQuery), 'current location')) {
            return [];
        }

        $cacheKey = sprintf(
            'locations:autocomplete:%s:%s:%s',
            md5($normalizedQuery),
            md5((string) $sessionToken),
            $this->provider() . ':' . ($this->providerCountry() ?: 'global')
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $this->provider() === 'google'
                ? $this->googleAutocomplete($normalizedQuery, $sessionToken)
                : $this->geoapifyAutocomplete($normalizedQuery)
        );
    }

    public function placeDetails(string $placeId, ?string $sessionToken = null): ?array
    {
        if (!$this->isConfigured() || blank($placeId)) {
            return null;
        }

        return Cache::remember(
            'locations:details:' . $this->provider() . ':' . $placeId,
            now()->addDays(30),
            fn () => $this->provider() === 'google'
                ? $this->googlePlaceDetails($placeId, $sessionToken)
                : $this->geoapifyPlaceDetails($placeId)
        );
    }

    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        if (!$this->isConfigured()) {
            return [
                'label' => sprintf('Current location (%.4f, %.4f)', $latitude, $longitude),
                'place_id' => null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => null,
                'region' => null,
                'country_code' => null,
            ];
        }

        return $this->provider() === 'google'
            ? $this->googleReverseGeocode($latitude, $longitude)
            : $this->geoapifyReverseGeocode($latitude, $longitude);
    }

    public function fallbackSuggestions(string $query): array
    {
        $normalized = mb_strtolower(trim($query));
        if (mb_strlen($normalized) < 2) {
            return [];
        }

        return collect(self::POPULAR_LOCATION_FALLBACKS)
            ->filter(function (array $item) use ($normalized) {
                return str_contains(mb_strtolower($item['label']), $normalized)
                    || str_contains(mb_strtolower($item['city']), $normalized)
                    || str_contains(mb_strtolower($item['region']), $normalized);
            })
            ->map(function (array $item): array {
                return [
                    'description' => $item['label'],
                    'place_id' => null,
                    'main_text' => $item['city'],
                    'secondary_text' => $item['region'] . ', United Kingdom',
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'city' => $item['city'],
                    'region' => $item['region'],
                    'country_code' => $item['country_code'],
                    'source' => 'fallback',
                ];
            })
            ->take(8)
            ->values()
            ->all();
    }

    private function extractAddressComponent(array $result, array $types, bool $useShortName = false): ?string
    {
        $components = $result['address_components'] ?? [];
        foreach ($components as $component) {
            $componentTypes = $component['types'] ?? [];
            foreach ($types as $type) {
                if (in_array($type, $componentTypes, true)) {
                    return $useShortName
                        ? ($component['short_name'] ?? null)
                        : ($component['long_name'] ?? null);
                }
            }
        }

        return null;
    }

    private function providerCountry(): ?string
    {
        $country = match ($this->provider()) {
            'google' => config('services.google_maps.country'),
            'geoapify' => config('services.geoapify.country'),
            default => null,
        };

        if (blank($country)) {
            return null;
        }

        $normalized = strtolower(trim((string) $country));

        return match ($normalized) {
            'uk' => 'gb',
            default => $normalized,
        };
    }

    private function googleAutocomplete(string $query, ?string $sessionToken): array
    {
        $response = $this->httpClient()
            ->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', array_filter([
                'input' => $query,
                'types' => '(regions)',
                'language' => $this->mapsLocale(),
                'key' => config('services.google_maps.key'),
                'sessiontoken' => $sessionToken,
                'components' => $this->providerCountry()
                    ? 'country:' . $this->providerCountry()
                    : null,
            ]));

        if (!$response->successful()) {
            $this->logProviderIssue('google_autocomplete', 'google autocomplete request failed', [
                'status' => $response->status(),
                'query' => $query,
                'body' => $response->json(),
            ]);
            return [];
        }

        return collect($response->json('predictions', []))
            ->map(function (array $prediction): array {
                return [
                    'description' => $prediction['description'] ?? '',
                    'place_id' => $prediction['place_id'] ?? null,
                    'main_text' => data_get($prediction, 'structured_formatting.main_text'),
                    'secondary_text' => data_get($prediction, 'structured_formatting.secondary_text'),
                    'latitude' => null,
                    'longitude' => null,
                    'city' => null,
                    'region' => null,
                    'country_code' => null,
                    'source' => 'google',
                ];
            })
            ->filter(fn (array $prediction) => filled($prediction['description']))
            ->take(8)
            ->values()
            ->all();
    }

    private function googlePlaceDetails(string $placeId, ?string $sessionToken): ?array
    {
        $response = $this->httpClient()
            ->get('https://maps.googleapis.com/maps/api/place/details/json', array_filter([
                'place_id' => $placeId,
                'fields' => 'place_id,formatted_address,geometry,address_component',
                'language' => $this->mapsLocale(),
                'key' => config('services.google_maps.key'),
                'sessiontoken' => $sessionToken,
            ]));

        if (!$response->successful()) {
            $this->logProviderIssue('google_place_details', 'google place details request failed', [
                'status' => $response->status(),
                'place_id' => $placeId,
                'body' => $response->json(),
            ]);
            return null;
        }

        $result = $response->json('result');
        if (!is_array($result)) {
            return null;
        }

        return [
            'label' => $result['formatted_address'] ?? '',
            'place_id' => $result['place_id'] ?? $placeId,
            'latitude' => data_get($result, 'geometry.location.lat'),
            'longitude' => data_get($result, 'geometry.location.lng'),
            'city' => $this->extractAddressComponent($result, ['locality', 'postal_town']),
            'region' => $this->extractAddressComponent($result, ['administrative_area_level_1']),
            'country_code' => strtoupper(
                (string) $this->extractAddressComponent($result, ['country'], true)
            ),
        ];
    }

    private function googleReverseGeocode(float $latitude, float $longitude): ?array
    {
        $response = $this->httpClient()
            ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $latitude . ',' . $longitude,
                'language' => $this->mapsLocale(),
                'key' => config('services.google_maps.key'),
            ]);

        if (!$response->successful()) {
            $this->logProviderIssue('google_reverse_geocode', 'google reverse geocode request failed', [
                'status' => $response->status(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'body' => $response->json(),
            ]);
            return null;
        }

        $result = collect($response->json('results', []))
            ->first(fn (array $item) => filled($item['formatted_address'] ?? null));

        if (!is_array($result)) {
            return [
                'label' => sprintf('Current location (%.4f, %.4f)', $latitude, $longitude),
                'place_id' => null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => null,
                'region' => null,
                'country_code' => null,
            ];
        }

        return [
            'label' => $result['formatted_address'] ?? sprintf('Current location (%.4f, %.4f)', $latitude, $longitude),
            'place_id' => $result['place_id'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city' => $this->extractAddressComponent($result, ['locality', 'postal_town']),
            'region' => $this->extractAddressComponent($result, ['administrative_area_level_1']),
            'country_code' => strtoupper(
                (string) $this->extractAddressComponent($result, ['country'], true)
            ),
        ];
    }

    private function geoapifyAutocomplete(string $query): array
    {
        $response = $this->safeGet(
            $this->buildGeoapifyUrl('services.geoapify.autocomplete_path'),
            array_filter([
                'text' => $query,
                'limit' => (int) config('services.geoapify.autocomplete_limit', 8),
                'format' => 'json',
                'lang' => $this->mapsLocale(),
                'filter' => $this->providerCountry()
                    ? 'countrycode:' . strtolower((string) $this->providerCountry())
                    : null,
                'apiKey' => config('services.geoapify.key'),
            ]),
            'geoapify_autocomplete',
            ['query' => $query]
        );

        if (!$response) {
            return $this->geoapifySearch($query);
        }

        if (!$response->successful()) {
            $this->logProviderIssue('geoapify_autocomplete', 'geoapify autocomplete request failed', [
                'status' => $response->status(),
                'query' => $query,
                'body' => $response->json(),
            ]);
            return [];
        }

        $results = collect($response->json('results', []))
            ->map(fn (array $result) => $this->mapGeoapifyResult($result))
            ->filter(fn (array $result) => filled($result['description']))
            ->values()
            ->all();

        if (empty($results)) {
            $this->logProviderIssue('geoapify_autocomplete', 'geoapify autocomplete returned no usable results', [
                'query' => $query,
                'body' => $response->json(),
            ]);

            return $this->geoapifySearch($query);
        }

        return $results;
    }

    private function geoapifySearch(string $query): array
    {
        $response = $this->safeGet(
            $this->buildGeoapifyUrl('services.geoapify.search_path'),
            array_filter([
                'text' => $query,
                'limit' => (int) config('services.geoapify.autocomplete_limit', 8),
                'format' => 'json',
                'lang' => $this->mapsLocale(),
                'filter' => $this->providerCountry()
                    ? 'countrycode:' . strtolower((string) $this->providerCountry())
                    : null,
                'apiKey' => config('services.geoapify.key'),
            ]),
            'geoapify_search',
            ['query' => $query]
        );

        if (!$response || !$response->successful()) {
            if ($response) {
                $this->logProviderIssue('geoapify_search', 'geoapify search request failed', [
                    'status' => $response->status(),
                    'query' => $query,
                    'body' => $response->json(),
                ]);
            }

            return [];
        }

        return collect($response->json('results', []))
            ->map(fn (array $result) => $this->mapGeoapifyResult($result))
            ->filter(fn (array $result) => filled($result['description']))
            ->values()
            ->all();
    }

    private function geoapifyPlaceDetails(string $placeId): ?array
    {
        $response = $this->safeGet(
            $this->buildGeoapifyUrl('services.geoapify.place_details_path'),
            [
                'id' => $placeId,
                'apiKey' => config('services.geoapify.key'),
            ],
            'geoapify_place_details',
            ['place_id' => $placeId]
        );

        if (!$response) {
            return null;
        }

        if (!$response->successful()) {
            $this->logProviderIssue('geoapify_place_details', 'geoapify place details request failed', [
                'status' => $response->status(),
                'place_id' => $placeId,
                'body' => $response->json(),
            ]);
            return null;
        }

        $feature = data_get($response->json(), 'features.0');
        if (!is_array($feature)) {
            $this->logProviderIssue('geoapify_place_details', 'geoapify place details returned no feature', [
                'place_id' => $placeId,
                'body' => $response->json(),
            ]);
            return null;
        }

        return $this->mapGeoapifyFeature($feature);
    }

    private function geoapifyReverseGeocode(float $latitude, float $longitude): ?array
    {
        $response = $this->safeGet(
            $this->buildGeoapifyUrl('services.geoapify.reverse_path'),
            [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'lang' => $this->mapsLocale(),
                'apiKey' => config('services.geoapify.key'),
            ],
            'geoapify_reverse_geocode',
            ['latitude' => $latitude, 'longitude' => $longitude]
        );

        if (!$response) {
            return [
                'label' => sprintf('Current location (%.4f, %.4f)', $latitude, $longitude),
                'place_id' => null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => null,
                'region' => null,
                'country_code' => null,
            ];
        }

        if (!$response->successful()) {
            $this->logProviderIssue('geoapify_reverse_geocode', 'geoapify reverse geocode request failed', [
                'status' => $response->status(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'body' => $response->json(),
            ]);
            return null;
        }

        $result = collect($response->json('results', []))->first();
        if (!is_array($result)) {
            return [
                'label' => sprintf('Current location (%.4f, %.4f)', $latitude, $longitude),
                'place_id' => null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => null,
                'region' => null,
                'country_code' => null,
            ];
        }

        return [
            'label' => $result['formatted'] ?? sprintf('Current location (%.4f, %.4f)', $latitude, $longitude),
            'place_id' => $result['place_id'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'city' => $result['city'] ?? $result['county'] ?? null,
            'region' => $result['state'] ?? null,
            'country_code' => strtoupper((string) ($result['country_code'] ?? '')),
        ];
    }

    private function mapGeoapifyResult(array $result): array
    {
        return [
            'description' => $result['formatted'] ?? '',
            'place_id' => $result['place_id'] ?? null,
            'main_text' => $result['city'] ?? $result['state'] ?? $result['country'] ?? ($result['formatted'] ?? ''),
            'secondary_text' => collect([
                $result['state'] ?? null,
                $result['country'] ?? null,
            ])->filter()->unique()->implode(', '),
            'latitude' => $result['lat'] ?? null,
            'longitude' => $result['lon'] ?? null,
            'city' => $result['city'] ?? $result['county'] ?? null,
            'region' => $result['state'] ?? null,
            'country_code' => strtoupper((string) ($result['country_code'] ?? '')),
            'source' => 'geoapify',
        ];
    }

    private function mapGeoapifyFeature(array $feature): array
    {
        $properties = $feature['properties'] ?? [];
        return [
            'label' => $properties['formatted'] ?? '',
            'place_id' => $properties['place_id'] ?? null,
            'latitude' => data_get($feature, 'geometry.coordinates.1'),
            'longitude' => data_get($feature, 'geometry.coordinates.0'),
            'city' => $properties['city'] ?? $properties['county'] ?? null,
            'region' => $properties['state'] ?? null,
            'country_code' => strtoupper((string) ($properties['country_code'] ?? '')),
        ];
    }
}
