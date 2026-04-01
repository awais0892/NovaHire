@props([
    'tone' => 'default',
    'intensity' => '1',
    'animate' => false,
])

<div {{ $attributes->class(['public-silk-layer pointer-events-none absolute inset-0']) }} aria-hidden="true">
    <canvas
        class="public-silk-canvas"
        data-silk-bg
        data-silk-tone="{{ $tone }}"
        data-silk-intensity="{{ $intensity }}"
        data-silk-animate="{{ $animate ? 'true' : 'false' }}"></canvas>
    <div class="public-silk-overlay"></div>
    <div class="public-silk-vignette"></div>
    <div class="public-silk-noise"></div>
</div>
