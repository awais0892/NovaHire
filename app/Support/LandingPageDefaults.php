<?php

namespace App\Support;

class LandingPageDefaults
{
    public static function data(): array
    {
        return [
            'hero' => [
                'badge' => 'AI-first Recruiting Platform',
                'title' => 'Hire faster with structured AI screening and role-based workflows',
                'subtitle' => 'From job posting to candidate ranking, OVA Recruiter App helps teams evaluate CVs, reduce manual screening, and move qualified talent through the pipeline with confidence.',
                'primary_cta_text' => 'Start Free',
                'primary_cta_url' => '/register',
                'secondary_cta_text' => 'Browse Jobs',
                'secondary_cta_url' => '/jobs',
                'image' => '/images/large-vecteezy_ai-generated-a-silhouette-of-a-person-standing-on-top-of-a_40247032_large.jpg',
                'video' => '/images/vecteezy_tech-abstract-green-screen-transition-4k-hd-video_22653032.mp4',
            ],
            'stats' => [
                ['label' => 'Candidates Screened', 'value' => '1.2M+'],
                ['label' => 'Avg. Time-to-Hire Reduction', 'value' => '67%'],
                ['label' => 'Enterprise Customers', 'value' => '850+'],
            ],
            'features' => [
                [
                    'icon' => 'brain-circuit',
                    'title' => 'AI Resume Intelligence',
                    'desc' => 'Parse CVs, score fit, and extract skills with consistent, explainable analysis.',
                ],
                [
                    'icon' => 'users',
                    'title' => 'Role-Based Workspaces',
                    'desc' => 'Dedicated workflows for HR admins, recruiters, hiring managers, and candidates.',
                ],
                [
                    'icon' => 'clipboard-check',
                    'title' => 'Smart Pipeline Control',
                    'desc' => 'Move applications from screening to interview with live status and team notes.',
                ],
                [
                    'icon' => 'shield-check',
                    'title' => 'Bias-Aware Screening',
                    'desc' => 'Focus on job-relevant signals and structured criteria to improve hiring fairness.',
                ],
                [
                    'icon' => 'bar-chart-3',
                    'title' => 'Recruitment Analytics',
                    'desc' => 'Track source quality, conversion rates, and recruiter throughput by role and team.',
                ],
                [
                    'icon' => 'credit-card',
                    'title' => 'Secure Billing',
                    'desc' => 'Stripe-hosted subscriptions with webhook-backed updates and plan-based access.',
                ],
            ],
            'roles' => [
                [
                    'title' => 'HR Admin',
                    'points' => ['Team setup', 'Company controls', 'Global hiring metrics'],
                ],
                [
                    'title' => 'Recruiter',
                    'points' => ['AI screening', 'Candidate ranking', 'Pipeline actions'],
                ],
                [
                    'title' => 'Hiring Manager',
                    'points' => ['Shortlists', 'Interview review', 'Decision support'],
                ],
                [
                    'title' => 'Candidate',
                    'points' => ['Job discovery', 'Easy CV upload', 'Application tracking'],
                ],
            ],
            'plans' => [
                [
                    'name' => 'Basic',
                    'price' => '$0',
                    'desc' => 'Free forever for solo recruiters and evaluation.',
                    'cta' => 'Start Basic Free',
                    'features' => [
                        'No credit card required',
                        'Up to 2 active job postings',
                        'Core candidate pipeline tracking',
                        'Upgrade any time from settings',
                    ],
                ],
                [
                    'name' => 'Individual',
                    'price' => '$19',
                    'desc' => 'For individual recruiters with monthly hiring volume',
                    'cta' => 'Start Individual',
                    'features' => [
                        '30-day free monthly trial',
                        'Up to 10 active job postings',
                        'AI CV screening for 300 applicants/month',
                        'Auto-renewal unless canceled in settings',
                    ],
                ],
                [
                    'name' => 'Pro',
                    'price' => '$49',
                    'desc' => 'For scaling recruiting ops',
                    'cta' => 'Choose Pro',
                    'highlight' => true,
                    'features' => [
                        'Up to 20 active job postings',
                        'AI screening + candidate ranking for 2,000 applicants/month',
                        'Team collaboration notes and hiring manager reviews',
                        'Interview workflow and shortlist sharing',
                    ],
                ],
                [
                    'name' => 'Enterprise',
                    'price' => '$149',
                    'desc' => 'Advanced controls and volume',
                    'cta' => 'Contact Sales',
                    'features' => [
                        'Unlimited active job postings',
                        'High-volume AI screening with custom scoring rules',
                        'Role-based permissions and audit-ready reporting',
                        'Priority onboarding and dedicated success manager',
                    ],
                ],
            ],
            'logos' => [
                '/images/brand/brand-01.svg',
                '/images/brand/brand-02.svg',
                '/images/brand/brand-03.svg',
                '/images/brand/brand-04.svg',
                '/images/brand/brand-05.svg',
                '/images/brand/brand-06.svg',
            ],
        ];
    }
}
