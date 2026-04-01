<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use App\Services\Cms\LandingPageContentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class LandingPageController extends Controller
{
    public function __construct(private readonly LandingPageContentService $landingPageContent)
    {
    }

    public function __invoke()
    {
        $stripePlans = collect((array) config('stripe.plans', []))
            ->map(function (array $plan, string $key): array {
                $monthlyAmount = (int) data_get($plan, 'monthly.amount', data_get($plan, 'amount', 0));
                $annualAmount = (int) data_get($plan, 'annual.amount', max(0, $monthlyAmount * 12));
                $annualDiscount = (int) data_get($plan, 'annual.discount_percent', config('stripe.annual_discount_percent', 20));

                return [
                    'key' => $key,
                    'name' => (string) ($plan['name'] ?? ucfirst($key)),
                    'price' => '$' . number_format($monthlyAmount / 100, 0),
                    'annual_price' => '$' . number_format($annualAmount / 100, 0),
                    'monthly_cents' => $monthlyAmount,
                    'annual_cents' => $annualAmount,
                    'trial_days' => (int) data_get($plan, 'trial_days', 0),
                    'annual_discount_percent' => $annualDiscount,
                    'desc' => (string) ($plan['description'] ?? ''),
                    'limits' => (array) ($plan['limits'] ?? []),
                    'highlight' => $key === 'basic',
                    'cta' => $key === 'enterprise' ? 'Contact Sales' : 'Get Started',
                ];
            })
            ->values();

        $content = $this->landingPageContent->mergedHomeContent();
        $featuredJobs = collect();

        try {
            $featuredJobs = Cache::remember(
                'landing:featured-jobs',
                now()->addMinutes(5),
                fn () => JobListing::query()
                    ->select([
                        'id',
                        'company_id',
                        'slug',
                        'title',
                        'location',
                        'location_type',
                        'job_type',
                        'experience_level',
                        'salary_min',
                        'salary_max',
                        'salary_visible',
                        'published_at',
                        'status',
                    ])
                    ->with([
                        'company:id,name',
                        'skills:id,job_listing_id,skill',
                    ])
                    ->active()
                    ->orderByDesc('published_at')
                    ->limit(12)
                    ->get()
            );
        } catch (Throwable $exception) {
            Log::warning('Featured jobs could not be loaded for the landing page. Rendering without jobs.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return view('pages.landing', [
            'title' => 'OVA Recruiter App',
            'content' => $content,
            'stripePlans' => $stripePlans,
            'featuredJobs' => $featuredJobs,
        ]);
    }
}
