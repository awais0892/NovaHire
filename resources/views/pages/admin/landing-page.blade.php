@extends('layouts.app')

@section('content')
    @php
        $heroData = old('hero_badge') !== null
            ? [
                'badge' => old('hero_badge'),
                'title' => old('hero_title'),
                'subtitle' => old('hero_subtitle'),
                'primary_cta_text' => old('hero_primary_cta_text'),
                'primary_cta_url' => old('hero_primary_cta_url'),
                'secondary_cta_text' => old('hero_secondary_cta_text'),
                'secondary_cta_url' => old('hero_secondary_cta_url'),
                'image' => old('hero_image'),
                'video' => old('hero_video'),
            ]
            : data_get($formData, 'hero', []);
    @endphp

    <div class="mx-auto max-w-7xl p-4 md:p-6 space-y-6"
        x-data="landingEditor({
            stats: @js(old('stats', data_get($formData, 'stats', []))),
            features: @js(old('features', data_get($formData, 'features', []))),
            roles: @js(old('roles', data_get($formData, 'roles', []))),
            plans: @js(old('plans', data_get($formData, 'plans', []))),
            logos: @js(old('logos', data_get($formData, 'logos', []))),
        })">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Landing Page CMS</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Component-style editor with repeatable blocks (no raw JSON editing).</p>
            </div>
            <a href="{{ route('home') }}" target="_blank" class="btn btn-outline">Preview Landing</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.landing.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-admin.landing.hero-fields :hero-data="$heroData" />
            <x-admin.landing.stats-repeater />
            <x-admin.landing.features-repeater />
            <x-admin.landing.roles-repeater />
            <x-admin.landing.plans-repeater />
            <x-admin.landing.logos-repeater />

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Cancel</a>
                <button class="btn btn-primary">Save Landing Content</button>
            </div>
        </form>
    </div>

    <script>
        function landingEditor(initial) {
            return {
                stats: Array.isArray(initial.stats) ? initial.stats : [],
                features: Array.isArray(initial.features) ? initial.features : [],
                roles: Array.isArray(initial.roles) ? initial.roles : [],
                plans: Array.isArray(initial.plans) ? initial.plans : [],
                logos: Array.isArray(initial.logos) ? initial.logos : [],
            };
        }
    </script>
@endsection
