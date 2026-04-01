@php
    $manifestPath = public_path('build/manifest.json');
    $manifest = file_exists($manifestPath)
        ? json_decode(file_get_contents($manifestPath), true)
        : null;

    $cssEntry = is_array($manifest) ? ($manifest['resources/css/app.css'] ?? null) : null;
    $jsEntry = is_array($manifest) ? ($manifest['resources/js/app.js'] ?? null) : null;
@endphp

@if (is_array($cssEntry) && !empty($cssEntry['file']))
    <link rel="stylesheet" href="{{ asset('build/' . $cssEntry['file']) }}" data-navigate-track="reload" />
@endif

@if (is_array($jsEntry) && !empty($jsEntry['css']))
    @foreach ($jsEntry['css'] as $cssFile)
        <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}" data-navigate-track="reload" />
    @endforeach
@endif

@if (is_array($jsEntry) && !empty($jsEntry['file']))
    <script type="module" src="{{ asset('build/' . $jsEntry['file']) }}" data-navigate-track="reload"></script>
@endif
