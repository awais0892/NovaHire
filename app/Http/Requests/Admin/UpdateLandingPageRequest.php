<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'hero_badge' => ['required', 'string', 'max:120'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['required', 'string', 'max:1000'],
            'hero_primary_cta_text' => ['required', 'string', 'max:60'],
            'hero_primary_cta_url' => ['required', 'string', 'max:255'],
            'hero_secondary_cta_text' => ['required', 'string', 'max:60'],
            'hero_secondary_cta_url' => ['required', 'string', 'max:255'],
            'hero_image' => ['required', 'string', 'max:255'],
            'hero_video' => ['nullable', 'string', 'max:255'],

            'stats' => ['nullable', 'array'],
            'stats.*.label' => ['nullable', 'string', 'max:80'],
            'stats.*.value' => ['nullable', 'string', 'max:80'],

            'features' => ['nullable', 'array'],
            'features.*.icon' => ['nullable', 'string', 'max:80'],
            'features.*.title' => ['nullable', 'string', 'max:120'],
            'features.*.desc' => ['nullable', 'string', 'max:500'],

            'roles' => ['nullable', 'array'],
            'roles.*.title' => ['nullable', 'string', 'max:120'],
            'roles.*.points_text' => ['nullable', 'string', 'max:1200'],

            'plans' => ['nullable', 'array'],
            'plans.*.name' => ['nullable', 'string', 'max:80'],
            'plans.*.price' => ['nullable', 'string', 'max:40'],
            'plans.*.desc' => ['nullable', 'string', 'max:300'],
            'plans.*.cta' => ['nullable', 'string', 'max:80'],
            'plans.*.highlight' => ['nullable', 'boolean'],

            'logos' => ['nullable', 'array'],
            'logos.*.path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
