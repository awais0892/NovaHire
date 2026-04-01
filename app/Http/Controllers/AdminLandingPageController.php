<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateLandingPageRequest;
use App\Services\Cms\LandingPageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminLandingPageController extends Controller
{
    public function __construct(private readonly LandingPageContentService $landingPageContent)
    {
    }

    public function edit(): View
    {
        $page = $this->landingPageContent->getHomePage(true);
        $content = $this->landingPageContent->mergedHomeContent();
        return view('pages.admin.landing-page', [
            'title' => 'Landing Page CMS',
            'page' => $page,
            'content' => $content,
            'formData' => $this->landingPageContent->toEditorFormData($content),
        ]);
    }

    public function update(UpdateLandingPageRequest $request): RedirectResponse
    {
        $payload = $this->landingPageContent->normalizeFromValidated($request->validated());
        $this->landingPageContent->updateHomeContent($payload, auth()->id());

        return back()->with('success', 'Landing page content updated successfully.');
    }
}
