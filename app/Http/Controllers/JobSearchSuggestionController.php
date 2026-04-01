<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobListing;
use App\Models\JobSkill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobSearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $query = trim($validated['q']);

        $titleSuggestions = JobListing::query()
            ->active()
            ->where('title', 'like', '%' . $query . '%')
            ->select('title')
            ->distinct()
            ->limit(4)
            ->pluck('title')
            ->map(fn (string $title) => [
                'label' => $title,
                'type' => 'Role',
                'secondary' => 'Job title',
            ]);

        $companySuggestions = Company::query()
            ->where('name', 'like', '%' . $query . '%')
            ->select('name')
            ->distinct()
            ->limit(3)
            ->pluck('name')
            ->map(fn (string $name) => [
                'label' => $name,
                'type' => 'Company',
                'secondary' => 'Employer',
            ]);

        $skillSuggestions = JobSkill::query()
            ->where('skill', 'like', '%' . $query . '%')
            ->select('skill')
            ->distinct()
            ->limit(4)
            ->pluck('skill')
            ->map(fn (string $skill) => [
                'label' => $skill,
                'type' => 'Skill',
                'secondary' => 'Required skill',
            ]);

        $locationSuggestions = JobListing::query()
            ->active()
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('location_label', 'like', '%' . $query . '%')
                    ->orWhere('location', 'like', '%' . $query . '%');
            })
            ->selectRaw('COALESCE(location_label, location) as location_text')
            ->distinct()
            ->limit(4)
            ->pluck('location_text')
            ->filter()
            ->map(fn (string $location) => [
                'label' => $location,
                'type' => 'Location',
                'secondary' => 'Job location',
            ]);

        $suggestions = $titleSuggestions
            ->concat($companySuggestions)
            ->concat($skillSuggestions)
            ->concat($locationSuggestions)
            ->unique(fn (array $item) => strtolower($item['label']) . '|' . strtolower($item['type']))
            ->take(10)
            ->values()
            ->all();

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }
}
