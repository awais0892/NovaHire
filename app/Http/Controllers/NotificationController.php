<?php

namespace App\Http\Controllers;

use App\Notifications\ApplicationStatusChanged;
use App\Notifications\InterviewCancelled;
use App\Notifications\InterviewInvitationResponded;
use App\Notifications\InterviewReminder;
use App\Notifications\InterviewScheduled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $category = (string) $request->query('category', 'all');

        $query = $user->notifications();
        $this->applyCategoryFilter($query, $category);

        $paginator = $query
            ->latest()
            ->paginate(20);

        $items = $paginator->getCollection()
            ->map(fn(DatabaseNotification $notification) => $this->transform($notification));

        $paginator->setCollection($items);

        return view('pages.account.notifications', [
            'title' => 'Notifications',
            'notifications' => $paginator,
            'unreadCount' => $user->unreadNotifications()->count(),
            'category' => $category,
            'categoryCounts' => $this->categoryCounts($user),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $category = (string) $request->query('category', 'all');

        $query = $user->notifications();
        $this->applyCategoryFilter($query, $category);

        $notifications = $query
            ->latest()
            ->take(15)
            ->get()
            ->map(fn(DatabaseNotification $notification) => $this->transform($notification))
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }

    private function transform(DatabaseNotification $notification): array
    {
        $data = (array) ($notification->data ?? []);
        $type = (string) $notification->type;

        return [
            'id' => $notification->id,
            'title' => $this->titleFor($type, $data),
            'message' => $this->messageFor($type, $data),
            'url' => $this->urlFor($type, $data),
            'category' => $this->categoryFor($type),
            'read_at' => optional($notification->read_at)->toIso8601String(),
            'time' => optional($notification->created_at)->diffForHumans(),
            'created_at' => optional($notification->created_at)->toIso8601String(),
        ];
    }

    private function titleFor(string $type, array $data): string
    {
        if (Str::endsWith($type, 'ApplicationStatusChanged')) {
            return 'Application Status Updated';
        }

        if (Str::endsWith($type, 'InterviewScheduled')) {
            return 'Interview Scheduled';
        }

        if (Str::endsWith($type, 'InterviewCancelled')) {
            return 'Interview Cancelled';
        }

        if (Str::endsWith($type, 'InterviewInvitationResponded')) {
            return 'Candidate Interview Response';
        }

        if (Str::endsWith($type, 'InterviewReminder')) {
            return 'Interview Reminder';
        }

        return 'New Notification';
    }

    private function messageFor(string $type, array $data): string
    {
        if (Str::endsWith($type, 'ApplicationStatusChanged')) {
            $job = $data['job_title'] ?? 'your application';
            $status = ucfirst((string) ($data['status'] ?? 'updated'));
            $note = trim((string) ($data['note_excerpt'] ?? ''));

            if ($note !== '') {
                return "Status for {$job} changed to {$status}. AI note: {$note}";
            }

            return "Status for {$job} changed to {$status}.";
        }

        if (Str::endsWith($type, 'InterviewScheduled')) {
            $job = $data['job_title'] ?? 'your application';

            return "Interview has been scheduled for {$job}.";
        }

        if (Str::endsWith($type, 'InterviewCancelled')) {
            $job = $data['job_title'] ?? 'your application';

            return "Interview for {$job} has been cancelled.";
        }

        if (Str::endsWith($type, 'InterviewInvitationResponded')) {
            $candidate = $data['candidate_name'] ?? 'Candidate';
            $response = ucfirst((string) ($data['response'] ?? 'responded'));

            return "{$candidate} has {$response} the interview invitation.";
        }

        if (Str::endsWith($type, 'InterviewReminder')) {
            $job = $data['job_title'] ?? 'your interview';
            $window = (string) ($data['window'] ?? 'soon');
            $windowLabel = $window === '1h' ? 'in 1 hour' : ($window === '24h' ? 'in 24 hours' : 'soon');

            return "Reminder: interview for {$job} starts {$windowLabel}.";
        }

        return 'You have a new update.';
    }

    private function urlFor(string $type, array $data): string
    {
        if (Str::endsWith($type, 'InterviewScheduled') || Str::endsWith($type, 'InterviewCancelled')) {
            if (isset($data['interview_id']) && auth()->user()?->hasRole('candidate')) {
                return route('candidate.interviews.invitation.show', ['interview' => $data['interview_id']]);
            }
        }

        if (Str::endsWith($type, 'InterviewReminder') && isset($data['interview_id']) && auth()->user()?->hasRole('candidate')) {
            return route('candidate.interviews.invitation.show', ['interview' => $data['interview_id']]);
        }

        if (Str::endsWith($type, 'InterviewInvitationResponded') && auth()->user()?->hasRole('hr_admin')) {
            return route('recruiter.applications');
        }

        if (Str::endsWith($type, 'ApplicationStatusChanged')) {
            if (auth()->user()?->hasRole('candidate')) {
                return route('candidate.applications');
            }
            if (auth()->user()?->hasRole('hr_admin')) {
                return route('recruiter.applications');
            }
        }

        if (auth()->user()?->hasRole('super_admin')) {
            return route('admin.dashboard');
        }
        if (auth()->user()?->hasRole('hiring_manager')) {
            return route('manager.dashboard');
        }
        if (auth()->user()?->hasRole('hr_admin')) {
            return route('recruiter.dashboard');
        }
        if (auth()->user()?->hasRole('candidate')) {
            return route('candidate.dashboard');
        }

        return route('home');
    }

    private function categoryFor(string $type): string
    {
        if ($type === ApplicationStatusChanged::class) {
            return 'applications';
        }

        if (in_array($type, [
            InterviewScheduled::class,
            InterviewCancelled::class,
            InterviewInvitationResponded::class,
            InterviewReminder::class,
        ], true)) {
            return 'interviews';
        }

        return 'system';
    }

    private function applyCategoryFilter($query, string $category): void
    {
        if ($category === 'applications') {
            $query->where('type', ApplicationStatusChanged::class);
            return;
        }

        if ($category === 'interviews') {
            $query->whereIn('type', [
                InterviewScheduled::class,
                InterviewCancelled::class,
                InterviewInvitationResponded::class,
                InterviewReminder::class,
            ]);
            return;
        }

        if ($category === 'system') {
            $query->whereNotIn('type', [
                ApplicationStatusChanged::class,
                InterviewScheduled::class,
                InterviewCancelled::class,
                InterviewInvitationResponded::class,
                InterviewReminder::class,
            ]);
        }
    }

    private function categoryCounts($user): array
    {
        return [
            'all' => $user->notifications()->count(),
            'applications' => $user->notifications()->where('type', ApplicationStatusChanged::class)->count(),
            'interviews' => $user->notifications()->whereIn('type', [
                InterviewScheduled::class,
                InterviewCancelled::class,
                InterviewInvitationResponded::class,
                InterviewReminder::class,
            ])->count(),
            'system' => $user->notifications()->whereNotIn('type', [
                ApplicationStatusChanged::class,
                InterviewScheduled::class,
                InterviewCancelled::class,
                InterviewInvitationResponded::class,
                InterviewReminder::class,
            ])->count(),
        ];
    }
}
