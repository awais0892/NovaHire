<?php

namespace App\Services\Cms;

use App\Models\CmsPage;
use App\Support\LandingPageDefaults;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class LandingPageContentService
{
    private const HOME_PAGE_CACHE_KEY = 'cms:home-page';

    private const HOME_CONTENT_CACHE_KEY = 'cms:home-content:merged';

    public function getHomePage(bool $createIfMissing = false): ?CmsPage
    {
        if ($createIfMissing) {
            try {
                $page = CmsPage::query()->firstOrCreate(
                    ['slug' => 'home'],
                    ['title' => 'Home', 'content' => LandingPageDefaults::data()]
                );

                $this->flushHomeCache();

                return $page;
            } catch (Throwable $exception) {
                Log::warning('Landing page CMS record could not be created. Falling back to defaults.', [
                    'exception' => $exception->getMessage(),
                ]);

                return null;
            }
        }

        try {
            return Cache::remember(
                self::HOME_PAGE_CACHE_KEY,
                now()->addMinutes(5),
                fn () => CmsPage::query()->where('slug', 'home')->first()
            );
        } catch (Throwable $exception) {
            Log::warning('Landing page CMS content could not be loaded. Falling back to defaults.', [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function mergedHomeContent(): array
    {
        try {
            return Cache::remember(
                self::HOME_CONTENT_CACHE_KEY,
                now()->addMinutes(5),
                function (): array {
                    $defaults = LandingPageDefaults::data();
                    $stored = $this->getHomePage();

                    return array_replace_recursive($defaults, (array) ($stored?->content ?? []));
                }
            );
        } catch (Throwable $exception) {
            Log::warning('Merged landing page content could not be built from CMS. Falling back to defaults.', [
                'exception' => $exception->getMessage(),
            ]);

            return LandingPageDefaults::data();
        }
    }

    public function updateHomeContent(array $payload, ?int $updatedBy): CmsPage
    {
        $page = $this->getHomePage(true);
        if (!$page) {
            throw new \RuntimeException('Landing page content could not be persisted because the CMS page is unavailable.');
        }

        $page->update([
            'title' => 'Home',
            'content' => $payload,
            'updated_by' => $updatedBy,
        ]);

        $this->flushHomeCache();

        return $page->refresh();
    }

    private function flushHomeCache(): void
    {
        Cache::forget(self::HOME_PAGE_CACHE_KEY);
        Cache::forget(self::HOME_CONTENT_CACHE_KEY);
    }

    public function toEditorFormData(array $content): array
    {
        return [
            'hero' => [
                'badge' => (string) data_get($content, 'hero.badge', ''),
                'title' => (string) data_get($content, 'hero.title', ''),
                'subtitle' => (string) data_get($content, 'hero.subtitle', ''),
                'primary_cta_text' => (string) data_get($content, 'hero.primary_cta_text', ''),
                'primary_cta_url' => (string) data_get($content, 'hero.primary_cta_url', ''),
                'secondary_cta_text' => (string) data_get($content, 'hero.secondary_cta_text', ''),
                'secondary_cta_url' => (string) data_get($content, 'hero.secondary_cta_url', ''),
                'image' => (string) data_get($content, 'hero.image', ''),
                'video' => (string) data_get($content, 'hero.video', ''),
            ],
            'stats' => collect((array) data_get($content, 'stats', []))
                ->map(fn (array $row) => [
                    'label' => (string) ($row['label'] ?? ''),
                    'value' => (string) ($row['value'] ?? ''),
                ])->values()->all(),
            'features' => collect((array) data_get($content, 'features', []))
                ->map(fn (array $row) => [
                    'icon' => (string) ($row['icon'] ?? 'sparkles'),
                    'title' => (string) ($row['title'] ?? ''),
                    'desc' => (string) ($row['desc'] ?? ''),
                ])->values()->all(),
            'roles' => collect((array) data_get($content, 'roles', []))
                ->map(fn (array $row) => [
                    'title' => (string) ($row['title'] ?? ''),
                    'points_text' => implode("\n", array_filter(array_map('strval', (array) ($row['points'] ?? [])))),
                ])->values()->all(),
            'plans' => collect((array) data_get($content, 'plans', []))
                ->map(fn (array $row) => [
                    'name' => (string) ($row['name'] ?? ''),
                    'price' => (string) ($row['price'] ?? ''),
                    'desc' => (string) ($row['desc'] ?? ''),
                    'cta' => (string) ($row['cta'] ?? ''),
                    'highlight' => (bool) ($row['highlight'] ?? false),
                ])->values()->all(),
            'logos' => collect((array) data_get($content, 'logos', []))
                ->map(fn ($logo) => ['path' => (string) $logo])
                ->values()
                ->all(),
        ];
    }

    public function normalizeFromValidated(array $validated): array
    {
        $stats = collect((array) ($validated['stats'] ?? []))
            ->map(fn (array $row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
            ])
            ->filter(fn (array $row) => $row['label'] !== '' && $row['value'] !== '')
            ->values()
            ->all();

        $features = collect((array) ($validated['features'] ?? []))
            ->map(fn (array $row) => [
                'icon' => trim((string) ($row['icon'] ?? 'sparkles')),
                'title' => trim((string) ($row['title'] ?? '')),
                'desc' => trim((string) ($row['desc'] ?? '')),
            ])
            ->filter(fn (array $row) => $row['title'] !== '' && $row['desc'] !== '')
            ->values()
            ->all();

        $roles = collect((array) ($validated['roles'] ?? []))
            ->map(function (array $row): array {
                $title = trim((string) ($row['title'] ?? ''));
                $pointsText = trim((string) ($row['points_text'] ?? ''));
                $points = collect(preg_split('/\r\n|\r|\n/', $pointsText) ?: [])
                    ->map(fn ($line) => trim((string) $line))
                    ->filter()
                    ->values()
                    ->all();

                return ['title' => $title, 'points' => $points];
            })
            ->filter(fn (array $row) => $row['title'] !== '' && !empty($row['points']))
            ->values()
            ->all();

        $plans = collect((array) ($validated['plans'] ?? []))
            ->map(fn (array $row) => [
                'name' => trim((string) ($row['name'] ?? '')),
                'price' => trim((string) ($row['price'] ?? '')),
                'desc' => trim((string) ($row['desc'] ?? '')),
                'cta' => trim((string) ($row['cta'] ?? '')),
                'highlight' => (bool) ($row['highlight'] ?? false),
            ])
            ->filter(fn (array $row) => $row['name'] !== '' && $row['price'] !== '' && $row['cta'] !== '')
            ->values()
            ->all();

        $logos = collect((array) ($validated['logos'] ?? []))
            ->map(fn (array $row) => trim((string) ($row['path'] ?? '')))
            ->filter()
            ->values()
            ->all();

        return [
            'hero' => [
                'badge' => trim((string) $validated['hero_badge']),
                'title' => trim((string) $validated['hero_title']),
                'subtitle' => trim((string) $validated['hero_subtitle']),
                'primary_cta_text' => trim((string) $validated['hero_primary_cta_text']),
                'primary_cta_url' => trim((string) $validated['hero_primary_cta_url']),
                'secondary_cta_text' => trim((string) $validated['hero_secondary_cta_text']),
                'secondary_cta_url' => trim((string) $validated['hero_secondary_cta_url']),
                'image' => trim((string) $validated['hero_image']),
                'video' => trim((string) ($validated['hero_video'] ?? '')),
            ],
            'stats' => $stats,
            'features' => $features,
            'roles' => $roles,
            'plans' => $plans,
            'logos' => $logos,
        ];
    }
}
