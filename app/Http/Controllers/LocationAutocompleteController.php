<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use App\Services\LocationSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationAutocompleteController extends Controller
{
    public function __invoke(Request $request, LocationSearchService $locationSearch): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'session_token' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim($validated['q']);
        $providerSuggestions = $locationSearch->autocomplete($query, $validated['session_token'] ?? null);
        $fallbackSuggestions = $locationSearch->fallbackSuggestions($query);

        $databaseSuggestions = JobListing::query()
            ->active()
            ->where(function ($queryBuilder) use ($query) {
                $queryBuilder
                    ->where('location_label', 'like', '%' . $query . '%')
                    ->orWhere('location', 'like', '%' . $query . '%');
            })
            ->selectRaw('COALESCE(location_label, location) as description')
            ->distinct()
            ->orderBy('description')
            ->limit(8)
            ->pluck('description')
            ->filter()
            ->values()
            ->map(fn (string $description) => [
                'description' => $description,
                'place_id' => null,
                'main_text' => $description,
                'secondary_text' => 'Saved location',
                'latitude' => null,
                'longitude' => null,
                'city' => null,
                'region' => null,
                'country_code' => null,
                'source' => 'local',
            ])
            ->all();

        $suggestions = collect($providerSuggestions)
            ->concat($fallbackSuggestions)
            ->concat($databaseSuggestions)
            ->unique(fn (array $item) => mb_strtolower((string) ($item['description'] ?? '')))
            ->take(8)
            ->values()
            ->all();

        $usedFallbackOnly = empty($providerSuggestions)
            && (!empty($fallbackSuggestions) || !empty($databaseSuggestions));

        return response()->json([
            'configured' => $locationSearch->isConfigured(),
            'provider' => $locationSearch->provider(),
            'used_fallback_only' => $usedFallbackOnly,
            'suggestions' => $suggestions,
        ]);
    }
}
