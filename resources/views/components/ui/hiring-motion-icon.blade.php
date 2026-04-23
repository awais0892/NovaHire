@props([
    'src' => null,
    'poster' => null,
    'label' => 'Hiring animation',
    'zoom' => 1.08,
    'position' => '50% 50%',
    'playbackRate' => 1,
    'priority' => 2,
])

@php
    $resolvedSrc = (string) $src;
    $resolvedWebmSrc = str_ends_with(strtolower($resolvedSrc), '.mp4')
        ? preg_replace('/\.mp4$/i', '.webm', $resolvedSrc)
        : null;
    $resolvedZoom = max(1, min(1.45, (float) $zoom));
    $resolvedRate = max(0.5, min(1.6, (float) $playbackRate));
    $resolvedPriority = max(1, min(10, (int) $priority));
    $style = '--hiring-icon-zoom: '.$resolvedZoom.'; --hiring-icon-pos: '.$position.'; --hiring-icon-rate: '.$resolvedRate.';';
@endphp

<span {{ $attributes->class('hiring-motion-icon')->merge(['style' => $style]) }}>
    @if(filled($src))
        <video
            class="hiring-motion-icon-video"
            data-hiring-loop-video
            data-video-priority="{{ $resolvedPriority }}"
            data-video-src="{{ $resolvedSrc }}"
            @if(filled($resolvedWebmSrc))
                data-video-src-webm="{{ $resolvedWebmSrc }}"
            @endif
            data-video-src-mp4="{{ $resolvedSrc }}"
            loop
            muted
            playsinline
            preload="none"
            disablepictureinpicture
            poster="{{ $poster }}"
            aria-label="{{ $label }}">
            @if(filled($resolvedWebmSrc))
                <source data-src="{{ $resolvedWebmSrc }}" type="video/webm">
            @endif
            <source data-src="{{ $resolvedSrc }}" type="video/mp4">
        </video>
    @endif
</span>
