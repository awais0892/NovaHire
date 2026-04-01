<?php

namespace App\Http\Controllers;

use App\Models\SavedFilter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Support\AuditLogger;

class CandidateController extends Controller
{
    // Recruiter resource area
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $search = trim((string) request('q', ''));
        $status = trim((string) request('status', ''));

        $candidates = \App\Models\Candidate::with([
            'applications' => function ($q) {
                $q->latest();
            }
        ])
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->whereHas('applications', function ($q) use ($status) {
                    $q->where('status', $status);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.candidates.index', [
            'title' => 'Candidates Directory',
            'candidates' => $candidates,
            'savedFilters' => SavedFilter::query()
                ->where('user_id', auth()->id())
                ->where('page_key', 'recruiter_candidates')
                ->latest()
                ->get(),
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function saveFilter(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['applied', 'screening', 'shortlisted', 'interview', 'offer', 'hired', 'rejected'])],
        ]);

        $filters = [
            'q' => trim((string) ($validated['q'] ?? '')),
            'status' => (string) ($validated['status'] ?? ''),
        ];

        if (collect($filters)->every(fn($value) => $value === '')) {
            return back()->withErrors(['filters' => 'Add at least one filter before saving.']);
        }

        SavedFilter::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'page_key' => 'recruiter_candidates',
                'name' => trim((string) $validated['name']),
            ],
            [
                'company_id' => auth()->user()->company_id,
                'filters' => $filters,
            ]
        );

        AuditLogger::log('recruiter.candidates.filter.saved', null, [
            'name' => trim((string) $validated['name']),
            'filters' => $filters,
        ]);

        return back()->with('success', 'Filter saved.');
    }

    public function deleteFilter(SavedFilter $filter)
    {
        abort_unless($filter->user_id === auth()->id() && $filter->page_key === 'recruiter_candidates', 404);

        $name = $filter->name;
        $filter->delete();

        AuditLogger::log('recruiter.candidates.filter.deleted', null, [
            'name' => $name,
        ]);

        return back()->with('success', 'Saved filter removed.');
    }

    public function create()
    {
        return view('pages.candidates.create', ['title' => 'Add New Candidate']);
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;
        if (!$companyId) {
            return back()->withErrors(['company' => 'Your account is not linked to a company.'])->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('candidates', 'email')->where(fn($q) => $q->where('company_id', $companyId)),
            ],
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:100',
        ]);

        $candidate = \App\Models\Candidate::create(array_merge($validated, [
            'company_id' => $companyId
        ]));
        AuditLogger::log('recruiter.candidate.created', $candidate, [
            'email' => $candidate->email,
        ]);

        return redirect()->route('recruiter.candidates.index')->with('success', 'Candidate added successfully.');
    }

    public function show($id)
    {
        $candidate = \App\Models\Candidate::with([
            'applications.jobListing.company',
            'applications.aiAnalysis',
            'aiAnalyses',
        ])
            ->myCompany()
            ->findOrFail($id);

        return view('pages.candidates.show', [
            'title' => 'Candidate: ' . $candidate->name,
            'candidate' => $candidate
        ]);
    }

    public function edit($id)
    {
        $candidate = \App\Models\Candidate::myCompany()->findOrFail($id);
        return view('pages.candidates.edit', [
            'title' => 'Edit Candidate',
            'candidate' => $candidate
        ]);
    }

    public function update(Request $request, $id)
    {
        $candidate = \App\Models\Candidate::myCompany()->findOrFail($id);
        $companyId = auth()->user()->company_id;
        if (!$companyId) {
            return back()->withErrors(['company' => 'Your account is not linked to a company.'])->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('candidates', 'email')
                    ->where(fn($q) => $q->where('company_id', $companyId))
                    ->ignore($candidate->id),
            ],
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:100',
        ]);

        $candidate->update($validated);
        AuditLogger::log('recruiter.candidate.updated', $candidate, [
            'email' => $candidate->email,
        ]);

        return redirect()->route('recruiter.candidates.show', $id)->with('success', 'Candidate updated successfully.');
    }

    public function destroy($id)
    {
        $candidate = \App\Models\Candidate::myCompany()->findOrFail($id);
        AuditLogger::log('recruiter.candidate.deleted', $candidate, [
            'email' => $candidate->email,
        ]);
        $candidate->delete();
        return redirect()->route('recruiter.candidates.index')->with('success', 'Candidate deleted successfully.');
    }

    public function downloadResume($id)
    {
        $candidate = \App\Models\Candidate::myCompany()->findOrFail($id);

        if (!$candidate->cv_path) {
            abort(404);
        }

        $filename = $candidate->cv_original_name ?: ('resume-' . $candidate->id . '.pdf');
        $path = $candidate->cv_path;

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path, $filename);
        }

        if (Storage::exists($path)) {
            return Storage::download($path, $filename);
        }

        abort(404);
    }

    // Candidate self-area
    public function dashboard()
    {
        $candidate = \App\Models\Candidate::query()
            ->where('user_id', auth()->id())
            ->first()
            ?? \App\Models\Candidate::query()
                ->where('email', auth()->user()->email)
                ->first();

        if ($candidate && empty($candidate->user_id)) {
            $candidate->update(['user_id' => auth()->id()]);
        }

        $metrics = [
            'applications_submitted' => $candidate?->applications()->count() ?? 0,
            'interviews_scheduled' => $candidate?->applications()->where('status', 'interview')->count() ?? 0,
            'profile_views' => 0, // Placeholder for future feature
            'saved_jobs' => 0 // Placeholder for future feature
        ];

        $recent_applications = $candidate
            ? $candidate->applications()
                ->with('jobListing.company')
                ->latest()
                ->take(5)
                ->get()
            : collect();

        // Simple suggested jobs logic
        $suggested_jobs = \App\Models\JobListing::with('company')
            ->active()
            ->latest()
            ->take(3)
            ->get();

        return view('pages.dashboard.candidate', [
            'title' => 'My Dashboard',
            'metrics' => $metrics,
            'recent_applications' => $recent_applications,
            'suggested_jobs' => $suggested_jobs
        ]);
    }

}
