@props(['logos' => []])

@php
    $curatedLogos = [
        ['name' => 'Barclays', 'path' => '/images/partners/uk/barclays.svg'],
        ['name' => 'Lloyds Bank', 'path' => '/images/partners/uk/lloyds-bank.svg'],
        ['name' => 'Bank of Scotland', 'path' => '/images/partners/uk/bank-of-scotland.svg'],
        ['name' => 'Royal Bank of Scotland', 'path' => '/images/partners/uk/royal-bank-of-scotland.svg'],
        ['name' => 'HSBC', 'path' => '/images/partners/uk/hsbc.svg'],
        ['name' => 'Standard Chartered', 'path' => '/images/partners/uk/standard-chartered.svg'],
        ['name' => 'Monzo', 'path' => '/images/partners/uk/monzo.svg'],
        ['name' => 'Starling Bank', 'path' => '/images/partners/uk/starling-bank.svg'],
        ['name' => 'Revolut', 'path' => '/images/partners/uk/revolut.svg'],
        ['name' => 'Wise', 'path' => '/images/partners/uk/wise.svg'],
        ['name' => 'Arm', 'path' => '/images/partners/uk/arm.svg'],
        ['name' => 'Sage', 'path' => '/images/partners/uk/sage.svg'],
        ['name' => 'BT', 'path' => '/images/partners/uk/bt.svg'],
        ['name' => 'Vodafone', 'path' => '/images/partners/uk/vodafone.svg'],
        ['name' => 'O2', 'path' => '/images/partners/uk/o2.svg'],
        ['name' => 'Sky', 'path' => '/images/partners/uk/sky.svg'],
        ['name' => 'Virgin Media', 'path' => '/images/partners/uk/virgin-media.svg'],
        ['name' => 'Virgin Atlantic', 'path' => '/images/partners/uk/virgin-atlantic.svg'],
        ['name' => 'Deliveroo', 'path' => '/images/partners/uk/deliveroo.svg'],
        ['name' => 'Just Eat', 'path' => '/images/partners/uk/just-eat.svg'],
        ['name' => 'Tesco', 'path' => '/images/partners/uk/tesco.svg'],
        ['name' => 'Morrisons', 'path' => '/images/partners/uk/morrisons.svg'],
        ['name' => 'ASDA', 'path' => '/images/partners/uk/asda.svg'],
        ['name' => 'Boots', 'path' => '/images/partners/uk/boots.svg'],
        ['name' => 'Pearson', 'path' => '/images/partners/uk/pearson.svg'],
        ['name' => 'GSK', 'path' => '/images/partners/uk/gsk.svg'],
        ['name' => 'National Grid', 'path' => '/images/partners/uk/national-grid.svg'],
        ['name' => 'Rolls-Royce', 'path' => '/images/partners/uk/rolls-royce.svg'],
        ['name' => 'Aston Martin', 'path' => '/images/partners/uk/aston-martin.svg'],
        ['name' => 'Bentley', 'path' => '/images/partners/uk/bentley.svg'],
        ['name' => 'McLaren', 'path' => '/images/partners/uk/mclaren.svg'],
        ['name' => 'Channel 4', 'path' => '/images/partners/uk/channel-4.svg'],
        ['name' => 'DeepMind', 'path' => '/images/partners/uk/deepmind.svg'],
        ['name' => 'easyJet', 'path' => '/images/partners/uk/easyjet.svg'],
        ['name' => 'British Airways', 'path' => '/images/partners/uk/british-airways.svg'],
        ['name' => 'Unilever', 'path' => '/images/partners/uk/unilever.svg'],
        ['name' => 'Shell', 'path' => '/images/partners/uk/shell.svg'],
        ['name' => 'BP', 'path' => '/images/partners/uk/bp.svg'],
        ['name' => 'Marks & Spencer', 'path' => '/images/partners/uk/marks-and-spencer.svg'],
        ['name' => 'Skyscanner', 'path' => '/images/partners/uk/skyscanner.svg'],
    ];

    $incomingLogos = collect($logos)
        ->filter()
        ->map(function ($logo): array {
            if (is_array($logo)) {
                return [
                    'name' => (string) ($logo['name'] ?? 'Partner'),
                    'path' => (string) ($logo['path'] ?? ''),
                ];
            }

            return [
                'name' => 'Partner',
                'path' => (string) $logo,
            ];
        })
        ->filter(fn (array $logo): bool => $logo['path'] !== '')
        ->values();

    $looksLikeLegacyPlaceholderSet = $incomingLogos->isEmpty()
        || $incomingLogos->contains(
            fn (array $logo): bool => str_starts_with($logo['path'], '/images/brand/brand-')
        );

    $displayLogos = ($looksLikeLegacyPlaceholderSet ? collect($curatedLogos) : $incomingLogos)
        ->take(40)
        ->values();

    $rowSplitIndex = (int) ceil($displayLogos->count() / 2);
    $firstRow = $displayLogos->slice(0, $rowSplitIndex)->values();
    $secondRow = $displayLogos->slice($rowSplitIndex)->values();

    if ($secondRow->isEmpty()) {
        $secondRow = $firstRow;
    }

    $firstTrack = $firstRow->merge($firstRow)->values();
    $secondTrack = $secondRow->merge($secondRow)->values();
@endphp

<section class="cv-auto relative overflow-hidden border-y border-slate-200/70 bg-white/60 py-14 backdrop-blur dark:border-slate-800/80 dark:bg-slate-950/60">
    <div class="pointer-events-none absolute inset-0 opacity-40">
        <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-brand-500/10 blur-3xl"></div>
        <div class="absolute -right-24 -bottom-10 h-72 w-72 rounded-full bg-emerald-400/10 blur-3xl"></div>
    </div>
    <div class="relative nh-container">
        <h2 data-animate="reveal" class="nh-reveal text-center">
            <span class="nh-logo-trust-title">Trusted by modern UK hiring teams</span>
        </h2>
        <div class="nh-logo-marquee mt-7">
            <div class="nh-logo-marquee-row">
                <div class="nh-logo-marquee-track nh-logo-marquee-track-left">
                    @foreach($firstTrack as $logo)
                        <div class="nh-logo-marquee-item">
                            <div class="nh-logo-orbit-card">
                                <img src="{{ $logo['path'] }}"
                                    alt="{{ $logo['name'] }} logo"
                                    loading="lazy"
                                    decoding="async"
                                    class="nh-logo-brand">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="nh-logo-marquee-row">
                <div class="nh-logo-marquee-track nh-logo-marquee-track-right">
                    @foreach($secondTrack as $logo)
                        <div class="nh-logo-marquee-item">
                            <div class="nh-logo-orbit-card">
                                <img src="{{ $logo['path'] }}"
                                    alt="{{ $logo['name'] }} logo"
                                    loading="lazy"
                                    decoding="async"
                                    class="nh-logo-brand">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
