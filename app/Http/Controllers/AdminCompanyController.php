<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCompanyController extends Controller
{
    public function index()
    {
        $search = trim((string) request('q', ''));
        $status = trim((string) request('status', ''));
        $plan = trim((string) request('plan', ''));

        $companies = Company::withCount('users')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($plan !== '', fn($q) => $q->where('plan', $plan))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages.admin.companies.index', [
            'title' => 'Companies',
            'companies' => $companies,
            'filters' => compact('search', 'status', 'plan'),
        ]);
    }

    public function create()
    {
        return view('pages.admin.companies.create', ['title' => 'Create Company']);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $slug = Str::slug($validated['name']);
        $baseSlug = $slug !== '' ? $slug : Str::random(8);
        $slug = $baseSlug;
        $counter = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        Company::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'plan' => $validated['plan'],
            'status' => $validated['status'],
            'trial_ends_at' => $validated['trial_ends_at'] ?? null,
        ]);

        return redirect()->route('admin.companies.index')->with('success', 'Company created successfully.');
    }

    public function show($id)
    {
        $company = Company::with(['users.roles'])->withCount('users')->findOrFail($id);

        return view('pages.admin.companies.show', [
            'title' => 'Company Details',
            'company' => $company,
        ]);
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);
        return view('pages.admin.companies.edit', [
            'title' => 'Edit Company',
            'company' => $company,
        ]);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $validated = $this->validatePayload($request, $company->id);

        $company->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'plan' => $validated['plan'],
            'status' => $validated['status'],
            'trial_ends_at' => $validated['trial_ends_at'] ?? null,
        ]);

        return redirect()->route('admin.companies.show', $id)->with('success', 'Company updated successfully.');
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return redirect()->route('admin.companies.index')->with('success', 'Company archived successfully.');
    }

    private function validatePayload(Request $request, ?int $companyId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($companyId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'plan' => ['required', Rule::in(['free', 'basic', 'pro', 'enterprise'])],
            'status' => ['required', Rule::in(['active', 'suspended', 'trial'])],
            'trial_ends_at' => ['nullable', 'date'],
        ]);
    }
}
