<?php

namespace App\Http\Controllers;

class AiController extends Controller
{
    public function index()
    {
        $applications = \App\Models\Application::with(['candidate', 'jobListing'])
            ->myCompany()
            ->latest()
            ->get();

        return view('pages.ai.index', [
            'title' => 'AI Screening Pipeline',
            'applications' => $applications
        ]);
    }

    public function screen($applicationId)
    {
        \App\Models\Application::myCompany()->findOrFail($applicationId);

        // Single source of truth: use the Livewire analysis screen for all deep-dive views.
        return redirect()->route('recruiter.analysis', $applicationId);
    }
}
