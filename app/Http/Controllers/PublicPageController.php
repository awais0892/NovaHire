<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\DiscountVoucher;
use App\Models\JobListing;
use App\Services\Cms\LandingPageContentService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PublicPageController extends Controller
{
    public function __construct(private readonly LandingPageContentService $landingPageContent)
    {
    }

    public function product()
    {
        $content = $this->landingPageContent->mergedHomeContent();
        $features = collect((array) data_get($content, 'features', []))->values();
        $plans = collect((array) data_get($content, 'plans', []))->values();
        $platformMetrics = $this->platformMetrics();

        $featureExplorer = [
            [
                'key' => 'screening',
                'label' => 'Screening',
                'desc' => 'Automate CV parsing and shortlisting quality.',
                'items' => $features->filter(function ($feature): bool {
                    $title = strtolower((string) data_get($feature, 'title', ''));
                    $desc = strtolower((string) data_get($feature, 'desc', ''));
                    return str_contains($title, 'ai') || str_contains($title, 'screen') || str_contains($desc, 'cv');
                })->values()->all(),
            ],
            [
                'key' => 'pipeline',
                'label' => 'Pipeline',
                'desc' => 'Keep candidate flow visible across interview stages.',
                'items' => $features->filter(function ($feature): bool {
                    $title = strtolower((string) data_get($feature, 'title', ''));
                    $desc = strtolower((string) data_get($feature, 'desc', ''));
                    return str_contains($title, 'pipeline') || str_contains($title, 'workflow') || str_contains($desc, 'stage');
                })->values()->all(),
            ],
            [
                'key' => 'collaboration',
                'label' => 'Collaboration',
                'desc' => 'Recruiters and managers align on candidate decisions.',
                'items' => $features->filter(function ($feature): bool {
                    $title = strtolower((string) data_get($feature, 'title', ''));
                    $desc = strtolower((string) data_get($feature, 'desc', ''));
                    return str_contains($title, 'role') || str_contains($title, 'team') || str_contains($desc, 'manager');
                })->values()->all(),
            ],
            [
                'key' => 'analytics',
                'label' => 'Analytics',
                'desc' => 'Track conversion, source quality, and recruiter throughput.',
                'items' => $features->filter(function ($feature): bool {
                    $title = strtolower((string) data_get($feature, 'title', ''));
                    $desc = strtolower((string) data_get($feature, 'desc', ''));
                    return str_contains($title, 'analytics') || str_contains($desc, 'metric') || str_contains($desc, 'conversion');
                })->values()->all(),
            ],
        ];

        $mediaGallery = [
            [
                'type' => 'image',
                'title' => 'AI Screening Intelligence',
                'caption' => 'Recruiters get context-rich screening signals with transparent scoring.',
                'src' => asset('images/vecteezy_businessman-holding-global-internet-connection-technology_7252575.jpg'),
                'source_url' => 'https://www.vecteezy.com/photo/7252575-businessman-holding-global-internet-connection-technology-business-and-digital-marketing',
            ],
            [
                'type' => 'image',
                'title' => 'Leadership Decisions at Scale',
                'caption' => 'Align hiring managers and recruiters around consistent decision criteria.',
                'src' => asset('images/vecteezy_golden-chess-pawn-pieces-or-leader-businessman-stand-out-of_6614498.jpg'),
                'source_url' => 'https://www.vecteezy.com/photo/6614498-golden-chess-pawn-pieces-or-leader-businessman-stand-out-of-crowd-people-of-silver-men-leadership-business-team-teamwork-and-human-resource-management-concept',
            ],
            [
                'type' => 'image',
                'title' => 'Hiring Momentum',
                'caption' => 'Operate with confidence from first review to offer acceptance.',
                'src' => asset('images/large-vecteezy_ai-generated-a-silhouette-of-a-person-standing-on-top-of-a_40247032_large.jpg'),
                'source_url' => 'https://www.vecteezy.com/photo/40247032-ai-generated-a-silhouette-of-a-person-standing-on-top-of-a-mountain-peak-looking-out-at-a-sunset-symbolizing-reaching-business-goals',
            ],
            [
                'type' => 'video',
                'title' => 'Platform Motion Preview',
                'caption' => 'Visual layer used in the product hero to communicate live, data-driven operations.',
                'src' => asset('images/vecteezy_tech-abstract-green-screen-transition-4k-hd-video_22653032.mp4'),
                'source_url' => 'https://www.vecteezy.com/video/76380061-abstract-digital-technology-background-with-glowing-green-squares-motion',
            ],
        ];

        $workflow = [
            [
                'step' => 'Publish Jobs',
                'detail' => 'Create role-specific listings with required skills, location, and interview ownership.',
                'metric' => number_format((int) ($platformMetrics['active_jobs'] ?? 0)) . ' active jobs currently visible',
            ],
            [
                'step' => 'Collect Applications',
                'detail' => 'Capture candidate profiles and CV uploads in a structured submission flow.',
                'metric' => number_format((int) ($platformMetrics['applications'] ?? 0)) . ' total applications processed',
            ],
            [
                'step' => 'AI-Assisted Screening',
                'detail' => 'Use scoring, skill extraction, and shortlist ranking to prioritize recruiter attention.',
                'metric' => (int) ($platformMetrics['avg_ai_score'] ?? 0) . '% current average AI score',
            ],
            [
                'step' => 'Interview Orchestration',
                'detail' => 'Schedule interviews, trigger reminders, and keep candidate communication consistent.',
                'metric' => number_format((int) ($platformMetrics['scheduled_interviews'] ?? 0)) . ' interviews scheduled now',
            ],
            [
                'step' => 'Decision & Offer',
                'detail' => 'Move candidates through a transparent pipeline with notes, history, and approvals.',
                'metric' => number_format((int) ($platformMetrics['candidates'] ?? 0)) . ' candidate records maintained',
            ],
        ];

        return view('pages.public.product', [
            'title' => 'Product Overview',
            'metaDescription' => 'Explore NovaHire features for AI-powered recruitment workflows, screening, and interview operations.',
            'content' => $content,
            'platformMetrics' => $platformMetrics,
            'activeJobs' => Cache::remember(
                'public:product:active-jobs',
                now()->addMinutes(5),
                fn() => JobListing::query()
                    ->where('status', 'active')
                    ->orderByDesc('published_at')
                    ->limit(6)
                    ->get(['title', 'location', 'location_type', 'job_type', 'applications_count'])
            ),
            'roleCards' => collect((array) data_get($content, 'roles', []))->values(),
            'plans' => $plans,
            'features' => $features,
            'featureExplorer' => $featureExplorer,
            'mediaGallery' => $mediaGallery,
            'workflow' => $workflow,
            'metaImage' => asset('images/og/product.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Product', 'url' => route('public.product')],
            ],
        ]);
    }

    public function features()
    {
        $content = $this->landingPageContent->mergedHomeContent();
        $features = collect((array) data_get($content, 'features', []))->values();
        $roleCards = collect((array) data_get($content, 'roles', []))->values();
        $metrics = $this->platformMetrics();

        $featureModules = [
            [
                'key' => 'sourcing',
                'title' => 'Sourcing & Discovery',
                'desc' => 'Public job visibility and candidate-friendly application flows.',
                'items' => $features->filter(function ($feature): bool {
                    $title = strtolower((string) data_get($feature, 'title', ''));
                    $desc = strtolower((string) data_get($feature, 'desc', ''));
                    return str_contains($title, 'role') || str_contains($desc, 'candidate') || str_contains($desc, 'job');
                })->values()->all(),
            ],
            [
                'key' => 'screening',
                'title' => 'Screening & Evaluation',
                'desc' => 'AI-assisted scoring, skill mapping, and review-ready insights.',
                'items' => $features->filter(function ($feature): bool {
                    $title = strtolower((string) data_get($feature, 'title', ''));
                    $desc = strtolower((string) data_get($feature, 'desc', ''));
                    return str_contains($title, 'ai') || str_contains($title, 'screen') || str_contains($desc, 'score');
                })->values()->all(),
            ],
            [
                'key' => 'operations',
                'title' => 'Interview Operations',
                'desc' => 'Scheduling, reminders, invitations, and response tracking.',
                'items' => $features->filter(function ($feature): bool {
                    $title = strtolower((string) data_get($feature, 'title', ''));
                    $desc = strtolower((string) data_get($feature, 'desc', ''));
                    return str_contains($title, 'pipeline') || str_contains($desc, 'interview') || str_contains($desc, 'workflow');
                })->values()->all(),
            ],
            [
                'key' => 'governance',
                'title' => 'Collaboration & Governance',
                'desc' => 'Role-based access, analytics, and secure billing controls.',
                'items' => $features->filter(function ($feature): bool {
                    $title = strtolower((string) data_get($feature, 'title', ''));
                    $desc = strtolower((string) data_get($feature, 'desc', ''));
                    return str_contains($title, 'analytics') || str_contains($title, 'secure') || str_contains($desc, 'team');
                })->values()->all(),
            ],
        ];

        $featuresMedia = [
            [
                'title' => 'Connected Hiring Operations',
                'caption' => 'A unified recruitment surface for sourcing, screening, and collaboration.',
                'src' => asset('images/vecteezy_businessman-holding-global-internet-connection-technology_7252575.jpg'),
                'source_url' => 'https://www.vecteezy.com/photo/7252575-businessman-holding-global-internet-connection-technology-business-and-digital-marketing',
                'type' => 'image',
            ],
            [
                'title' => 'Decision Leadership',
                'caption' => 'Structured workflows help teams make consistent hiring decisions.',
                'src' => asset('images/vecteezy_golden-chess-pawn-pieces-or-leader-businessman-stand-out-of_6614498.jpg'),
                'source_url' => 'https://www.vecteezy.com/photo/6614498-golden-chess-pawn-pieces-or-leader-businessman-stand-out-of-crowd-people-of-silver-men-leadership-business-team-teamwork-and-human-resource-management-concept',
                'type' => 'image',
            ],
            [
                'title' => 'Platform Motion Layer',
                'caption' => 'Visual motion to reflect always-on recruitment pipelines and signals.',
                'src' => asset('images/vecteezy_tech-abstract-green-screen-transition-4k-hd-video_22653032.mp4'),
                'source_url' => 'https://www.vecteezy.com/video/76380061-abstract-digital-technology-background-with-glowing-green-squares-motion',
                'type' => 'video',
            ],
        ];

        $adoptionPlan = [
            ['phase' => 'Week 1', 'title' => 'Workflow Setup', 'detail' => 'Define roles, permissions, and active hiring pipelines.'],
            ['phase' => 'Week 2', 'title' => 'Data Intake', 'detail' => 'Publish jobs and normalize candidate intake + CV parsing.'],
            ['phase' => 'Week 3', 'title' => 'AI Calibration', 'detail' => 'Tune screening thresholds and shortlist scoring criteria.'],
            ['phase' => 'Week 4', 'title' => 'Operational Rollout', 'detail' => 'Launch team-wide interviews, reminders, and reporting loops.'],
        ];

        return view('pages.public.features', [
            'title' => 'Features',
            'metaDescription' => 'Discover ATS features, AI CV scoring, collaborative hiring workflows, and interview automation in NovaHire.',
            'content' => $content,
            'platformMetrics' => $metrics,
            'roleCards' => $roleCards,
            'features' => $features,
            'featureModules' => $featureModules,
            'featuresMedia' => $featuresMedia,
            'adoptionPlan' => $adoptionPlan,
            'metaImage' => asset('images/og/features.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Features', 'url' => route('public.features')],
            ],
        ]);
    }

    public function pricing()
    {
        $content = $this->landingPageContent->mergedHomeContent();
        $configuredPlans = collect((array) config('stripe.plans', []))
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
                    'interval' => 'month',
                    'annual_discount_percent' => $annualDiscount,
                    'trial_days' => (int) data_get($plan, 'trial_days', 0),
                    'description' => (string) ($plan['description'] ?? ''),
                    'limits' => (array) ($plan['limits'] ?? []),
                ];
            })
            ->values();

        return view('pages.public.pricing', [
            'title' => 'Pricing',
            'metaDescription' => 'Choose a NovaHire plan for your hiring team with transparent feature tiers and scalable recruitment tooling.',
            'content' => $content,
            'stripePlans' => $configuredPlans,
            'billingTrialDays' => (int) data_get(config('stripe.plans.basic'), 'trial_days', (int) config('stripe.trial_days', 30)),
            'activeVouchers' => DiscountVoucher::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->orderBy('code')
                ->get(['code', 'type', 'value', 'description', 'first_purchase_only', 'applies_to_plans', 'billing_cycles', 'min_subtotal', 'max_discount']),
            'platformMetrics' => $this->platformMetrics(),
            'metaImage' => asset('images/og/pricing.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Pricing', 'url' => route('public.pricing')],
            ],
        ]);
    }

    public function about()
    {
        return view('pages.public.about', [
            'title' => 'About',
            'metaDescription' => 'Learn how NovaHire helps recruiting teams hire faster with structured, data-driven decisions.',
            'platformMetrics' => $this->platformMetrics(),
            'metaImage' => asset('images/og/about.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'About', 'url' => route('public.about')],
            ],
        ]);
    }

    public function contact()
    {
        return view('pages.public.contact', [
            'title' => 'Contact',
            'metaDescription' => 'Contact NovaHire for demos, support, integrations, and enterprise onboarding.',
            'metaImage' => asset('images/og/contact.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Contact', 'url' => route('public.contact')],
            ],
        ]);
    }

    public function faq()
    {
        $faqs = [
            [
                'q' => 'Do candidates need an account to apply?',
                'a' => 'Yes. Candidates create an account and can track application status, interview invitations, and profile updates in one place.',
            ],
            [
                'q' => 'Can recruiters schedule interviews from the app?',
                'a' => 'Yes. Recruiters can schedule, cancel, and reschedule interviews directly from the applications pipeline and candidates receive notifications.',
            ],
            [
                'q' => 'Does NovaHire support real-time notifications?',
                'a' => 'Yes. The platform supports in-app notifications with realtime updates and configurable user-level notification preferences.',
            ],
            [
                'q' => 'Is there a public job board?',
                'a' => 'Yes. Active roles are publicly discoverable, and candidates can apply without needing recruiter-side access.',
            ],
            [
                'q' => 'Can hiring managers collaborate without full admin access?',
                'a' => 'Yes. Hiring managers have role-scoped dashboards and shortlist views for focused decision participation.',
            ],
            [
                'q' => 'How is candidate data handled?',
                'a' => 'Candidate data is stored within role-based access boundaries and can be audited via system activity logs.',
            ],
        ];

        return view('pages.public.faq', [
            'title' => 'FAQ',
            'metaDescription' => 'Frequently asked questions about NovaHire setup, roles, billing, security, and onboarding.',
            'faqs' => $faqs,
            'metaImage' => asset('images/og/faq.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'FAQ', 'url' => route('public.faq')],
            ],
        ]);
    }

    public function privacy()
    {
        return view('pages.public.privacy', [
            'title' => 'Privacy Policy',
            'metaDescription' => 'Read NovaHire privacy practices for candidate data handling, retention, and platform security.',
            'metaImage' => asset('images/og/privacy.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Privacy Policy', 'url' => route('public.privacy')],
            ],
        ]);
    }

    public function terms()
    {
        return view('pages.public.terms', [
            'title' => 'Terms of Service',
            'metaDescription' => 'Review NovaHire terms covering account usage, billing, acceptable use, and service limitations.',
            'metaImage' => asset('images/og/terms.svg'),
            'breadcrumbs' => [
                ['name' => 'Home', 'url' => route('home')],
                ['name' => 'Terms of Service', 'url' => route('public.terms')],
            ],
        ]);
    }

    public function sitemap(): Response
    {
        $staticPages = [
            route('home'),
            route('jobs.index'),
            route('public.product'),
            route('public.features'),
            route('public.pricing'),
            route('public.about'),
            route('public.contact'),
            route('public.faq'),
            route('public.privacy'),
            route('public.terms'),
        ];

        $jobUrls = JobListing::query()
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get(['slug', 'updated_at'])
            ->map(fn(JobListing $job) => [
                'loc' => route('jobs.show', ['job' => $job->slug]),
                'lastmod' => optional($job->updated_at)->toAtomString(),
            ]);

        $pages = collect($staticPages)->map(fn(string $url) => [
            'loc' => $url,
            'lastmod' => now()->toAtomString(),
        ])->merge($jobUrls);

        $xml = view('pages.public.sitemap', ['pages' => $pages])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function platformMetrics(): array
    {
        return Cache::remember('public:platform-metrics:v1', now()->addMinutes(10), function (): array {
            $interviewsCount = 0;
            $hasInterviewsTable = Cache::remember(
                'schema:interviews-table-exists',
                now()->addHours(1),
                fn() => Schema::hasTable('interviews')
            );

            if ($hasInterviewsTable) {
                $interviewsCount = (int) \DB::table('interviews')
                    ->where('status', 'scheduled')
                    ->count();
            }

            return [
                'companies' => Company::query()->count(),
                'active_jobs' => JobListing::query()->where('status', 'active')->count(),
                'candidates' => Candidate::query()->count(),
                'applications' => Application::query()->count(),
                'scheduled_interviews' => $interviewsCount,
                'avg_ai_score' => (int) round((float) Application::query()->whereNotNull('ai_score')->avg('ai_score')),
            ];
        });
    }
}
