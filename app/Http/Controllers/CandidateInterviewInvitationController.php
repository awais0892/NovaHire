<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Interview;
use App\Models\User;
use App\Notifications\InterviewInvitationResponded;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CandidateInterviewInvitationController extends Controller
{
    public function show(Request $request, Interview $interview)
    {
        if (!auth()->user()?->hasRole('candidate')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in with your candidate account to view interview invitation.',
            ]);
        }

        $candidate = $this->resolveCandidate();
        abort_unless($candidate && $interview->application?->candidate_id === $candidate->id, 403);

        $interview->loadMissing('application.jobListing.company', 'scheduler:id,name');

        return view('pages.candidate.interview-invitation', [
            'title' => 'Interview Invitation',
            'interview' => $interview,
        ]);
    }

    public function respond(Request $request, Interview $interview)
    {
        if (!auth()->user()?->hasRole('candidate')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in with your candidate account to respond to interview invitation.',
            ]);
        }

        $candidate = $this->resolveCandidate();
        abort_unless($candidate && $interview->application?->candidate_id === $candidate->id, 403);

        $validated = $request->validate([
            'response' => ['required', 'in:accepted,declined'],
        ]);

        abort_if($interview->status === 'cancelled', 422, 'This interview has already been cancelled.');

        $interview->update([
            'candidate_response' => $validated['response'],
            'candidate_responded_at' => now(),
        ]);

        AuditLogger::log('candidate.interview.response', $interview->application, [
            'interview_id' => $interview->id,
            'response' => $validated['response'],
        ]);

        $interview->loadMissing('application.candidate', 'application.jobListing');
        User::query()
            ->where('company_id', $interview->company_id)
            ->role('hr_admin')
            ->get()
            ->each(function (User $user) use ($interview, $validated) {
                $this->notifyUserSafely(
                    $user,
                    new InterviewInvitationResponded($interview, $validated['response']),
                    'candidate.interview.response',
                    $interview->application_id
                );
            });

        return redirect()
            ->route('candidate.interviews.invitation.show', $interview)
            ->with('success', 'Your response has been recorded and shared with the recruiting team.');
    }

    private function resolveCandidate(): ?Candidate
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        return Candidate::query()
            ->where('user_id', $user->id)
            ->first()
            ?? Candidate::query()->where('email', $user->email)->first();
    }

    private function notifyUserSafely(User $user, $notification, string $context, ?int $applicationId = null): void
    {
        try {
            $user->notify($notification);
        } catch (\Throwable $exception) {
            logger()->warning('Notification delivery failed. Continuing request.', [
                'context' => $context,
                'user_id' => $user->id,
                'application_id' => $applicationId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
