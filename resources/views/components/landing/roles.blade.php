@props(['roles' => []])
@php
    $roleAnimationDefaults = [
        [
            'needle' => 'admin',
            'src' => '/animations/roles/manager-role.mp4',
            'poster' => '/animations/roles/manager-role.png',
            'label' => 'Planning and HR operations animation',
            'zoom' => 1.06,
            'position' => '50% 56%',
        ],
        [
            'needle' => 'recruiter',
            'src' => '/animations/roles/recruiter-role.mp4',
            'poster' => '/animations/roles/recruiter-role.png',
            'label' => 'Resume review and candidate screening animation',
            'zoom' => 1.05,
            'position' => '50% 50%',
        ],
        [
            'needle' => 'manager',
            'src' => '/animations/roles/hiring-manager-role.mp4',
            'poster' => '/animations/roles/hiring-manager-role.png',
            'label' => 'Hiring decision and review animation',
            'zoom' => 1.06,
            'position' => '50% 53%',
        ],
        [
            'needle' => 'candidate',
            'src' => '/animations/roles/candidate-role.mp4',
            'poster' => '/animations/roles/candidate-role.png',
            'label' => 'Job search and application animation',
            'zoom' => 1.04,
            'position' => '50% 60%',
        ],
    ];

    $roleCards = collect($roles)->values()->map(function ($card, $index) use ($roleAnimationDefaults) {
        $title = strtolower((string) data_get($card, 'title', ''));
        $asset = collect($roleAnimationDefaults)->first(function ($item) use ($title) {
            return str_contains($title, (string) data_get($item, 'needle', ''));
        });

        $fallbackAsset = data_get($roleAnimationDefaults, $index % count($roleAnimationDefaults), []);
        $resolvedAsset = is_array($asset) ? $asset : $fallbackAsset;

        return [
            'title' => data_get($card, 'title', 'Hiring Role'),
            'points' => (array) data_get($card, 'points', []),
            'animation_src' => data_get($resolvedAsset, 'src'),
            'animation_poster' => data_get($resolvedAsset, 'poster'),
            'animation_label' => data_get($resolvedAsset, 'label', 'Role animation'),
            'media_zoom' => (float) data_get($resolvedAsset, 'zoom', 1.2),
            'media_position' => (string) data_get($resolvedAsset, 'position', '50% 50%'),
        ];
    });
@endphp

<section id="roles" class="nh-section cv-auto overflow-hidden border-y border-slate-200 bg-white/70 backdrop-blur dark:border-slate-800 dark:bg-slate-950/60">
    <div class="nh-container">
        <p data-animate="reveal" class="nh-reveal nh-eyebrow">Role Experience</p>
        <h2 data-animate="reveal" data-delay="1" class="nh-reveal nh-h2">Designed for every hiring role</h2>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 lg:[grid-auto-rows:1fr]">
            @foreach($roleCards as $card)
                <div
                    data-animate="reveal"
                    class="nh-reveal nh-card role-exp-card h-full p-5 lg:p-6">
                    @php
                        $animationSrc = (string) data_get($card, 'animation_src', '');
                        $animationWebmSrc = str_ends_with(strtolower($animationSrc), '.mp4')
                            ? preg_replace('/\.mp4$/i', '.webm', $animationSrc)
                            : null;
                    @endphp
                    <div class="role-exp-media" style="--role-media-zoom: {{ max(1, min(1.6, (float) data_get($card, 'media_zoom', 1.08))) }}; --role-media-pos: {{ data_get($card, 'media_position', '50% 50%') }};">
                        <video
                            class="role-exp-media-video"
                            data-role-exp-video
                            data-video-priority="6"
                            data-video-src="{{ $animationSrc }}"
                            @if(filled($animationWebmSrc))
                                data-video-src-webm="{{ $animationWebmSrc }}"
                            @endif
                            data-video-src-mp4="{{ $animationSrc }}"
                            loop
                            muted
                            playsinline
                            preload="none"
                            disablepictureinpicture
                            poster="{{ data_get($card, 'animation_poster') }}"
                            aria-label="{{ data_get($card, 'animation_label') }}">
                            @if(filled($animationWebmSrc))
                                <source data-src="{{ $animationWebmSrc }}" type="video/webm">
                            @endif
                            <source data-src="{{ $animationSrc }}" type="video/mp4">
                        </video>
                    </div>
                    <h3 class="role-exp-title mt-4">{{ data_get($card, 'title') }}</h3>
                    <ul class="role-exp-points">
                        @foreach(data_get($card, 'points', []) as $point)
                            <li class="role-exp-point">
                                <span class="role-exp-point-dot"></span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>
