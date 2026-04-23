<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\DiscountVoucher;
use App\Models\JobListing;
use App\Services\CloudinaryImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function profile(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasRole('candidate')) {
            return redirect()->route('candidate.profile');
        }

        $stats = $this->buildStats($user);

        return view('pages.account.profile', [
            'title' => 'My Profile',
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function updateProfile(Request $request, CloudinaryImageService $cloudinaryImageService): RedirectResponse
    {
        $user = auth()->user();
        $isCandidate = $user->hasRole('candidate');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:120'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'github' => ['nullable', 'url', 'max:255'],
            'portfolio' => ['nullable', 'url', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:2048'],
        ]);

        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            try {
                $avatarUrl = $cloudinaryImageService->uploadAvatar($request->file('avatar'), (int) $user->id);
            } catch (\Throwable $exception) {
                report($exception);

                return back()
                    ->withInput()
                    ->withErrors([
                        'avatar' => 'Profile photo upload failed. Please check Cloudinary settings and try again.',
                    ]);
            }
        }

        $oldEmail = $user->email;

        $userPayload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($avatarUrl !== null) {
            $userPayload['avatar'] = $avatarUrl;
        }

        $user->update($userPayload);

        if ($isCandidate) {
            $candidate = Candidate::where('user_id', $user->id)
                ->first()
                ?? Candidate::where('email', $oldEmail)->first()
                ?? new Candidate();

            if (empty($candidate->company_id) && !empty($user->company_id)) {
                $candidate->company_id = $user->company_id;
            }

            $candidate->user_id = $user->id;
            $candidate->name = $validated['name'];
            $candidate->email = $validated['email'];
            $candidate->phone = $validated['phone'] ?? null;
            $candidate->location = $validated['location'] ?? null;
            $candidate->linkedin = $validated['linkedin'] ?? null;
            $candidate->github = $validated['github'] ?? null;
            $candidate->portfolio = $validated['portfolio'] ?? null;
            $candidate->save();
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    public function settings(): View
    {
        $user = auth()->user();
        $company = $user->company;
        $notificationPreferences = $user->mergedNotificationPreferences();
        $rawPlans = (array) config('stripe.plans', []);
        $stripePlans = collect($rawPlans)->map(function (array $plan, string $key): array {
            $monthlyAmount = (int) data_get($plan, 'monthly.amount', data_get($plan, 'amount', 0));
            $annualAmount = (int) data_get($plan, 'annual.amount', max(0, $monthlyAmount * 12));
            return [
                'key' => $key,
                'name' => (string) ($plan['name'] ?? ucfirst($key)),
                'description' => (string) ($plan['description'] ?? ''),
                'display_amount' => number_format($monthlyAmount / 100, 0),
                'display_amount_annual' => number_format($annualAmount / 100, 0),
                'monthly_amount' => $monthlyAmount,
                'annual_amount' => $annualAmount,
                'trial_days' => (int) data_get($plan, 'trial_days', 0),
                'limits' => (array) ($plan['limits'] ?? []),
            ];
        })->values();
        $billingStatus = strtolower((string) ($company->billing_status ?? 'inactive'));
        $currentPlan = strtolower((string) ($company->plan ?? 'free'));
        $currentPlanName = (string) data_get(config('stripe.plans'), $currentPlan . '.name', ucfirst($currentPlan));

        $statusClass = match ($billingStatus) {
            'active', 'trialing' => 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300',
            'past_due', 'incomplete', 'unpaid' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300',
            'canceled', 'incomplete_expired' => 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300',
            default => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
        };

        return view('pages.account.settings', [
            'title' => 'Account Settings',
            'user' => $user,
            'company' => $company,
            'stripePlans' => $stripePlans,
            'stripeConfigured' => filled(config('services.stripe.key')) && filled(config('services.stripe.secret')),
            'currentPlan' => $currentPlan,
            'currentPlanName' => $currentPlanName,
            'billingStatus' => $billingStatus,
            'statusClass' => $statusClass,
            'isSubscribed' => in_array($currentPlan, ['basic', 'pro', 'enterprise'], true) && in_array($billingStatus, ['active', 'trialing'], true),
            'notificationPreferences' => $notificationPreferences,
            'individualMonthlyTrialDays' => (int) data_get(config('stripe.plans.basic'), 'trial_days', (int) config('stripe.trial_days', 30)),
            'activeVouchers' => DiscountVoucher::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['code', 'type', 'value', 'description']),
        ]);
    }

    public function updateNotificationSettings(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $types = array_keys(\App\Models\User::DEFAULT_NOTIFICATION_PREFERENCES);
        $channels = ['mail', 'database', 'broadcast'];
        $preferences = [];

        foreach ($types as $type) {
            $preferences[$type] = [];
            foreach ($channels as $channel) {
                $preferences[$type][$channel] = $request->boolean("preferences.{$type}.{$channel}");
            }
        }

        $user->update([
            'notification_preferences' => $preferences,
        ]);

        return back()->with('success', 'Notification preferences updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function companySettings(): View
    {
        $user = auth()->user();
        $company = $user->company;

        abort_unless($company, 404);

        return view('pages.account.company-settings', [
            'title' => 'Company Settings',
            'company' => $company,
            'memberCount' => $company->users()->count(),
        ]);
    }

    public function updateCompanySettings(Request $request): RedirectResponse
    {
        $company = auth()->user()->company;
        abort_unless($company, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($company->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $company->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
        ]);

        return back()->with('success', 'Company settings updated successfully.');
    }

    private function buildStats($user): array
    {
        if ($user->hasRole('candidate')) {
            $candidate = Candidate::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();
            $apps = $candidate
                ? Application::where('candidate_id', $candidate->id)->get()
                : collect();

            return [
                'primary' => $apps->count(),
                'primary_label' => 'Applications',
                'secondary' => $apps->where('status', 'interview')->count(),
                'secondary_label' => 'Interviews',
            ];
        }

        $companyId = $user->company_id;
        if (!$companyId) {
            return [
                'primary' => 0,
                'primary_label' => 'Jobs',
                'secondary' => 0,
                'secondary_label' => 'Applications',
            ];
        }

        return [
            'primary' => JobListing::where('company_id', $companyId)->count(),
            'primary_label' => 'Jobs',
            'secondary' => Application::where('company_id', $companyId)->count(),
            'secondary_label' => 'Applications',
        ];
    }
}
