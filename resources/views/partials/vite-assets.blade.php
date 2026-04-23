@php
    $manifestPath = public_path('build/manifest.json');
    $manifest = file_exists($manifestPath)
        ? json_decode(file_get_contents($manifestPath), true)
        : null;

    $cssEntryKey = $cssEntry ?? 'resources/css/app.css';
    $jsEntryKey = $jsEntry ?? 'resources/js/app.js';

    $cssManifestEntry = is_array($manifest) ? ($manifest[$cssEntryKey] ?? null) : null;
    $jsManifestEntry = is_array($manifest) ? ($manifest[$jsEntryKey] ?? null) : null;

    $styleFiles = [];
    if (is_array($cssManifestEntry) && !empty($cssManifestEntry['file'])) {
        $styleFiles[] = asset('build/' . $cssManifestEntry['file']);
    }
    if (is_array($jsManifestEntry) && !empty($jsManifestEntry['css'])) {
        foreach ($jsManifestEntry['css'] as $cssFile) {
            $styleFiles[] = asset('build/' . $cssFile);
        }
    }

    $styleFiles = array_values(array_unique(array_filter($styleFiles)));
@endphp

@foreach ($styleFiles as $styleFile)
    <link rel="stylesheet" href="{{ $styleFile }}" data-navigate-track="reload" />
@endforeach

@if (is_array($jsManifestEntry) && !empty($jsManifestEntry['file']))
    <script type="module" src="{{ asset('build/' . $jsManifestEntry['file']) }}" data-navigate-track="reload"></script>
@endif
