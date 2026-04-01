<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCompanyController;
use App\Http\Controllers\RecruiterController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\CandidateController as CandidateControllerClass;
use App\Http\Controllers\HiringManagerController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\RecruiterApplicationController;
use App\Http\Controllers\RecruiterInterviewSlotController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\AdminLandingPageController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\CandidateInterviewInvitationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\LocationAutocompleteController;
use App\Http\Controllers\JobSearchSuggestionController;

// Role-based root redirect
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasRole('super_admin'))
            return redirect()->route('admin.dashboard');
        if ($user->hasRole('hr_admin'))
            return redirect()->route('recruiter.dashboard');
        if ($user->hasRole('hr_standard'))
            return redirect()->route('recruiter.applications');
        if ($user->hasRole('hiring_manager'))
            return redirect()->route('manager.dashboard');
        if ($user->hasRole('candidate'))
            return redirect()->route('candidate.dashboard');
    }
    return app(LandingPageController::class)();
})->name('home');

Route::get('/dashboard', function () {
    return redirect()->route('home');
})->name('dashboard');

Route::get('/health', HealthController::class)->name('health');
Route::get('/locations/autocomplete', LocationAutocompleteController::class)
    ->middleware('throttle:60,1')
    ->name('locations.autocomplete');
Route::get('/jobs/search-suggestions', JobSearchSuggestionController::class)
    ->middleware('throttle:60,1')
    ->name('jobs.search-suggestions');
Route::get('/sitemap.xml', [PublicPageController::class, 'sitemap'])->name('public.sitemap');
Route::get('/product', [PublicPageController::class, 'product'])->name('public.product');
Route::get('/features', [PublicPageController::class, 'features'])->name('public.features');
Route::get('/pricing', [PublicPageController::class, 'pricing'])->name('public.pricing');
Route::get('/about', [PublicPageController::class, 'about'])->name('public.about');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('public.contact');
Route::get('/faq', [PublicPageController::class, 'faq'])->name('public.faq');
Route::get('/privacy-policy', [PublicPageController::class, 'privacy'])->name('public.privacy');
Route::get('/terms-of-service', [PublicPageController::class, 'terms'])->name('public.terms');

// calender pages
Route::get('/calendar', function () {
    return view('pages.calender', ['title' => 'Calendar']);
})->name('calendar');

// form pages
Route::get('/form-elements', function () {
    return view('pages.form.form-elements', ['title' => 'Form Elements']);
})->name('form-elements');

// tables pages
Route::get('/basic-tables', function () {
    return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
})->name('basic-tables');

// pages

Route::get('/blank', function () {
    return view('pages.blank', ['title' => 'Blank']);
})->name('blank');

// error pages
Route::get('/error-404', function () {
    return view('pages.errors.error-404', ['title' => 'Error 404']);
})->name('error-404');

// chart pages
Route::get('/line-chart', function () {
    return view('pages.chart.line-chart', ['title' => 'Line Chart']);
})->name('line-chart');

Route::get('/bar-chart', function () {
    return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
})->name('bar-chart');


// authentication pages
Route::get('/signin', function () {
    return redirect()->route('login');
})->name('signin');

Route::get('/signup', function () {
    return redirect()->route('register');
})->name('signup');

// Guest auth routes (mount Livewire Register)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register/verify', [AuthController::class, 'showRegistrationVerificationNotice'])->name('register.verify.notice');
    Route::post('/register/verify/resend', [AuthController::class, 'resendRegistrationVerification'])->name('register.verify.resend');
    Route::get('/two-factor/challenge', [AuthController::class, 'showTwoFactorChallenge'])->name('auth.2fa.challenge.show');
    Route::post('/two-factor/challenge', [AuthController::class, 'verifyTwoFactorChallenge'])->name('auth.2fa.challenge.verify');
    Route::post('/two-factor/challenge/resend', [AuthController::class, 'resendTwoFactorChallenge'])->name('auth.2fa.challenge.resend');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});
Route::get('/register/verify/{id}/{hash}', [AuthController::class, 'verifyRegistrationEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('register.verify');

// --- Multi-tenant & role-based route structure (controllers to be added) ---

// Public examples
// Public Job Board (Managed below at line 158)

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/candidate/interviews/invitation/{interview}', [CandidateInterviewInvitationController::class, 'show'])
        ->name('candidate.interviews.invitation.show');
    Route::post('/candidate/interviews/invitation/{interview}/respond', [CandidateInterviewInvitationController::class, 'respond'])
        ->name('candidate.interviews.invitation.respond');
});

Route::middleware(['auth', 'company.active'])->group(function () {
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::put('/account/settings/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::put('/account/settings/notifications', [AccountController::class, 'updateNotificationSettings'])->name('account.notifications.update');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])
        ->middleware('throttle:checkout-session')
        ->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');

    // Super Admin
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::patch('/users/bulk', [AdminController::class, 'bulkUpdateUsers'])->name('users.bulk');
        Route::patch('/users/{user}/status', [AdminController::class, 'updateUserStatus'])->name('users.status');
        Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
        Route::get('/ai-insights', [AdminController::class, 'aiInsights'])->name('ai.insights');
        Route::get('/activity', [AdminController::class, 'activity'])->name('activity');
        Route::resource('companies', AdminCompanyController::class);
        Route::get('/landing-page', [AdminLandingPageController::class, 'edit'])->name('landing.edit');
        Route::put('/landing-page', [AdminLandingPageController::class, 'update'])->name('landing.update');
    });

    // HR Admin
    Route::middleware('role:hr_admin')->prefix('recruiter')->name('recruiter.')->group(function () {
        Route::get('/dashboard', [RecruiterController::class, 'dashboard'])->name('dashboard');

        Route::get('/settings', [AccountController::class, 'companySettings'])->name('settings');
        Route::put('/settings', [AccountController::class, 'updateCompanySettings'])->name('settings.update');
        Route::get('/interview-slots', [RecruiterInterviewSlotController::class, 'index'])->name('interview-slots.index');
        Route::patch('/interview-slots/settings', [RecruiterInterviewSlotController::class, 'updateSettings'])->name('interview-slots.settings.update');
        Route::post('/interview-slots/generate', [RecruiterInterviewSlotController::class, 'generate'])->name('interview-slots.generate');
        Route::post('/interview-slots/exceptions', [RecruiterInterviewSlotController::class, 'storeException'])->name('interview-slots.exceptions.store');
        Route::delete('/interview-slots/exceptions/{exception}', [RecruiterInterviewSlotController::class, 'deleteException'])->name('interview-slots.exceptions.delete');
        Route::patch('/interview-slots/{slot}', [RecruiterInterviewSlotController::class, 'updateSlot'])->name('interview-slots.update');

        // Livewire-powered Jobs module
        Route::get('/jobs', \App\Livewire\Jobs\JobIndex::class)->name('jobs.index');
        Route::get('/jobs/create', \App\Livewire\Jobs\JobForm::class)->name('jobs.create');
        Route::get('/jobs/{job}/edit', \App\Livewire\Jobs\JobForm::class)->name('jobs.edit');
        Route::get('/jobs/{job}', \App\Livewire\Jobs\JobShow::class)->name('jobs.show');
        Route::get('/jobs/{job}/kanban', \App\Livewire\Recruiter\KanbanBoard::class)->name('jobs.kanban');

        // AI Results & Candidate Rankings
        Route::get(
            '/jobs/{job}/candidates',
            \App\Livewire\Recruiter\CandidateRankings::class
        )->name('jobs.candidates');

        Route::get(
            '/applications/{application}/analysis',
            \App\Livewire\Recruiter\AiAnalysisResult::class
        )->name('analysis');
        Route::get(
            '/applications/{application}/analysis/status',
            [RecruiterApplicationController::class, 'analysisStatus']
        )->name('analysis.status');
        Route::get(
            '/applications/{application}/analysis/report',
            [RecruiterApplicationController::class, 'downloadAnalysisReport']
        )->name('analysis.report');

        Route::get('/analytics', \App\Livewire\Recruiter\AnalyticsDashboard::class)
            ->middleware(['subscribed', 'plan:pro'])
            ->name('analytics');

        Route::resource('candidates', CandidateControllerClass::class);
        Route::post('/candidates/filters', [CandidateControllerClass::class, 'saveFilter'])
            ->name('candidates.filters.store');
        Route::delete('/candidates/filters/{filter}', [CandidateControllerClass::class, 'deleteFilter'])
            ->name('candidates.filters.delete');
        Route::get('/candidates/{candidate}/resume', [CandidateControllerClass::class, 'downloadResume'])
            ->name('candidates.resume.download');
        // AI Results & Candidate Rankings
        Route::get('/ai/screen', [AiController::class, 'index'])->name('ai.index');
        Route::get('/ai/screen/{application}', [AiController::class, 'screen'])->name('ai.screen');
    });

    Route::middleware('role:hr_admin|hr_standard')->prefix('recruiter')->name('recruiter.')->group(function () {
        Route::get('/applications', [RecruiterApplicationController::class, 'index'])
            ->name('applications');
        Route::get('/applications/export/csv', [RecruiterApplicationController::class, 'exportCsv'])
            ->name('applications.export.csv');
        Route::get('/applications/export/pdf', [RecruiterApplicationController::class, 'exportPdf'])
            ->name('applications.export.pdf');
        Route::post('/applications/filters', [RecruiterApplicationController::class, 'saveFilter'])
            ->name('applications.filters.store');
        Route::delete('/applications/filters/{filter}', [RecruiterApplicationController::class, 'deleteFilter'])
            ->name('applications.filters.delete');
        Route::get('/applications/{application}/details', [RecruiterApplicationController::class, 'details'])
            ->name('applications.details');
        Route::post('/applications/{application}/notes/override', [RecruiterApplicationController::class, 'overrideNote'])
            ->name('applications.notes.override');
        Route::patch('/applications/bulk-status', [RecruiterApplicationController::class, 'bulkUpdateStatus'])
            ->name('applications.bulk-status');
        Route::patch('/applications/{application}/status', [RecruiterApplicationController::class, 'updateStatus'])
            ->name('applications.status');
        Route::post('/applications/{application}/interviews', [RecruiterApplicationController::class, 'scheduleInterview'])
            ->name('applications.interviews.schedule');
        Route::get('/applications/{application}/interview-slots', [RecruiterApplicationController::class, 'availableSlots'])
            ->name('applications.interviews.slots');
        Route::patch('/applications/{application}/interviews/{interview}/cancel', [RecruiterApplicationController::class, 'cancelInterview'])
            ->name('applications.interviews.cancel');
    });

    // Hiring Manager
    Route::middleware('role:hiring_manager')->prefix('manager')->name('manager.')->group(function () {
        Route::get('/dashboard', [HiringManagerController::class, 'dashboard'])->name('dashboard');
        Route::get('/shortlisted', [HiringManagerController::class, 'shortlisted'])->name('shortlisted');
    });

    // Candidate
    Route::middleware('role:candidate')->prefix('candidate')->group(function () {
        Route::get('/dashboard', [CandidateControllerClass::class, 'dashboard'])->name('candidate.dashboard');

        // Candidate job board inside dashboard shell
        Route::get('/jobs', \App\Livewire\Candidate\JobBoard::class)->name('candidate.jobs.index');
        Route::get('/jobs/{job:slug}', \App\Livewire\Candidate\JobDetail::class)->name('candidate.jobs.show');

        // Applications
        Route::get('/my-applications', \App\Livewire\Candidate\MyApplications::class)->name('candidate.applications');
        Route::get('/applications', \App\Livewire\Candidate\MyApplications::class); // Alias

        // Profile
        Route::get('/my-profile', \App\Livewire\Candidate\CandidateProfile::class)->name('candidate.profile');
        Route::get('/profile', \App\Livewire\Candidate\CandidateProfile::class); // Alias

        Route::get('/jobs/{job:slug}/apply', \App\Livewire\Candidates\JobApply::class)->name('candidate.apply');

        // Chat placeholder to avoid 404
        Route::get('/chat', function () {
            return view('pages.blank', ['title' => 'Messages (Coming Soon)']);
        })->name('candidate.chat');
    });

    // We can also have an application screen explicitly for candidates and public
    Route::get('/jobs/{job:slug}/apply', \App\Livewire\Candidates\JobApply::class)->name('jobs.apply');
});

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

// ── Public Job Board ──────────────────────────────────────
Route::get('/jobs', \App\Livewire\Candidate\JobBoard::class)->name('jobs.index');
Route::get('/jobs/{job:slug}', \App\Livewire\Candidate\JobDetail::class)->name('jobs.show');

// ui elements pages
Route::get('/alerts', function () {
    return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
})->name('alerts');

Route::get('/avatars', function () {
    return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
})->name('avatars');

Route::get('/badge', function () {
    return view('pages.ui-elements.badges', ['title' => 'Badges']);
})->name('badges');

Route::get('/buttons', function () {
    return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
})->name('buttons');

Route::get('/image', function () {
    return view('pages.ui-elements.images', ['title' => 'Images']);
})->name('images');

Route::get('/videos', function () {
    return view('pages.ui-elements.videos', ['title' => 'Videos']);
})->name('videos');
