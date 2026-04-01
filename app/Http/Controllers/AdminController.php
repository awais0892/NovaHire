<?php

namespace App\Http\Controllers;

use App\Models\AiAnalysis;
use App\Models\Application;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Support\AuditLogger;

class AdminController extends Controller
{
    public function dashboard()
    {
        $metrics = [
            'total_companies' => Company::count(),
            'active_subscriptions' => Company::where('status', 'active')->count(),
            'total_users' => User::count(),
            'ai_tokens_used' => AiAnalysis::sum('tokens_used')
        ];

        $recent_companies = Company::withCount('users')
            ->latest()
            ->take(6)
            ->get();

        // Data for Company Growth Chart
        $company_growth = Company::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('pages.dashboard.admin', [
            'title' => 'Admin Dashboard',
            'metrics' => $metrics,
            'recent_companies' => $recent_companies,
            'growth_labels' => $company_growth->pluck('date'),
            'growth_data' => $company_growth->pluck('count'),
        ]);
    }

    public function users()
    {
        $search = trim((string) request('q', ''));
        $role = trim((string) request('role', ''));
        $status = trim((string) request('status', ''));

        $users = User::with(['company', 'roles'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn($q) => $q->role($role))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.users', [
            'title' => 'Platform Users',
            'users' => $users,
            'filters' => compact('search', 'role', 'status'),
        ]);
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $authUser = auth()->user();

        if ($authUser && $authUser->id === $user->id && $data['status'] !== 'active') {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        if ($user->hasRole('super_admin') && $data['status'] !== 'active') {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->withErrors(['status' => 'Cannot deactivate the last super admin account.']);
            }
        }

        $previousStatus = $user->status;
        $user->update(['status' => $data['status']]);
        AuditLogger::log('admin.user.status.updated', $user, [
            'from' => $previousStatus,
            'to' => $data['status'],
        ]);

        return back()->with('success', "User status updated to {$data['status']}.");
    }

    public function updateUserRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['super_admin', 'hr_admin', 'hiring_manager', 'candidate'])],
        ]);

        $authUser = auth()->user();
        $newRole = $data['role'];

        if ($authUser && $authUser->id === $user->id && $newRole !== 'super_admin') {
            return back()->withErrors(['role' => 'You cannot remove your own super admin role.']);
        }

        if ($user->hasRole('super_admin') && $newRole !== 'super_admin') {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->withErrors(['role' => 'Cannot demote the last super admin account.']);
            }
        }

        $user->syncRoles([$newRole]);
        AuditLogger::log('admin.user.role.updated', $user, [
            'to' => $newRole,
        ]);

        return back()->with('success', "User role updated to {$newRole}.");
    }

    public function bulkUpdateUsers(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['status', 'role'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'role' => ['nullable', Rule::in(['super_admin', 'hr_admin', 'hiring_manager', 'candidate'])],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $authUser = auth()->user();
        $userIds = collect($data['user_ids'])->map(fn($id) => (int) $id)->unique()->values();
        $users = User::with('roles')->whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return back()->withErrors(['bulk' => 'No valid users were selected.']);
        }

        if ($data['action'] === 'status') {
            $newStatus = $data['status'] ?? null;
            if (!$newStatus) {
                return back()->withErrors(['bulk' => 'Please choose a target status for bulk update.']);
            }

            if ($newStatus !== 'active') {
                if ($authUser && $userIds->contains((int) $authUser->id)) {
                    return back()->withErrors(['bulk' => 'You cannot deactivate your own account in bulk action.']);
                }

                $superAdminTotal = User::role('super_admin')->count();
                $superAdminsSelected = $users->filter(fn($u) => $u->hasRole('super_admin'))->count();
                if (($superAdminTotal - $superAdminsSelected) < 1) {
                    return back()->withErrors(['bulk' => 'Cannot deactivate the last super admin account.']);
                }
            }

            User::whereIn('id', $userIds)->update(['status' => $newStatus]);
            AuditLogger::log('admin.users.bulk.status.updated', null, [
                'user_ids' => $userIds->all(),
                'to' => $newStatus,
            ]);

            return back()->with('success', "Updated status to {$newStatus} for {$users->count()} users.");
        }

        $newRole = $data['role'] ?? null;
        if (!$newRole) {
            return back()->withErrors(['bulk' => 'Please choose a target role for bulk update.']);
        }

        if ($newRole !== 'super_admin') {
            if ($authUser && $userIds->contains((int) $authUser->id)) {
                return back()->withErrors(['bulk' => 'You cannot remove your own super admin role in bulk action.']);
            }

            $superAdminTotal = User::role('super_admin')->count();
            $superAdminsSelected = $users->filter(fn($u) => $u->hasRole('super_admin'))->count();
            if (($superAdminTotal - $superAdminsSelected) < 1) {
                return back()->withErrors(['bulk' => 'Cannot demote the last super admin account.']);
            }
        }

        DB::transaction(function () use ($users, $newRole) {
            foreach ($users as $user) {
                $user->syncRoles([$newRole]);
            }
        });
        AuditLogger::log('admin.users.bulk.role.updated', null, [
            'user_ids' => $users->pluck('id')->all(),
            'to' => $newRole,
        ]);

        return back()->with('success', "Updated role to {$newRole} for {$users->count()} users.");
    }

    public function aiInsights()
    {
        $periodDays = (int) request('days', 30);
        $periodDays = in_array($periodDays, [7, 30, 90], true) ? $periodDays : 30;

        $since = now()->subDays($periodDays);

        $summary = [
            'total_analyses' => AiAnalysis::where('created_at', '>=', $since)->count(),
            'tokens_used' => (int) AiAnalysis::where('created_at', '>=', $since)->sum('tokens_used'),
            'avg_match_score' => round((float) AiAnalysis::where('created_at', '>=', $since)->avg('match_score'), 1),
            'applications_processed' => Application::where('created_at', '>=', $since)->count(),
        ];

        $dailyUsage = AiAnalysis::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, SUM(tokens_used) as tokens, COUNT(*) as runs')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $recommendationBreakdown = AiAnalysis::where('created_at', '>=', $since)
            ->selectRaw('recommendation, COUNT(*) as count')
            ->groupBy('recommendation')
            ->orderByDesc('count')
            ->get();

        $recentAnalyses = AiAnalysis::with(['candidate', 'jobListing', 'application'])
            ->latest()
            ->take(15)
            ->get();

        return view('pages.admin.ai-insights', [
            'title' => 'AI Insights',
            'summary' => $summary,
            'periodDays' => $periodDays,
            'dailyUsage' => $dailyUsage,
            'recommendationBreakdown' => $recommendationBreakdown,
            'recentAnalyses' => $recentAnalyses,
        ]);
    }

    public function activity()
    {
        $recentUsers = User::with('company')->latest()->take(10)->get();
        $recentCompanies = Company::latest()->take(10)->get();
        $recentApplications = Application::with(['candidate', 'jobListing'])->latest()->take(12)->get();

        return view('pages.admin.activity', [
            'title' => 'Platform Activity',
            'recentUsers' => $recentUsers,
            'recentCompanies' => $recentCompanies,
            'recentApplications' => $recentApplications,
        ]);
    }
}
