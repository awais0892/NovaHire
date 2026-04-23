<?php

namespace App\Livewire\Candidate;

use App\Models\Candidate;
use App\Services\CloudinaryImageService;
use App\Services\ResumeParserService;
use Livewire\Component;
use Livewire\WithFileUploads;

class CandidateProfile extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $location = '';
    public string $linkedin = '';
    public string $github = '';
    public string $portfolio = '';
    public $newAvatar;
    public string $avatarUrl = '';
    public $newCv;
    public bool $saved = false;
    public bool $cvUploaded = false;
    public string $cvStatusMessage = '';
    public string $cvErrorMessage = '';
    public string $selectedCvName = '';
    public int $maxCvUploadKb = 5120;
    public bool $twoFactorEnabled = false;

    private const PROFILE_FIELDS = [
        'name',
        'phone',
        'location',
        'linkedin',
        'github',
        'portfolio',
        'cv',
    ];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:100',
            'linkedin' => 'nullable|url|max:200',
            'github' => 'nullable|url|max:200',
            'portfolio' => 'nullable|url|max:200',
            'newAvatar' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:2048',
            'newCv' => "nullable|file|mimes:pdf|mimetypes:application/pdf,application/x-pdf,application/octet-stream|max:{$this->maxCvUploadKb}",
            'twoFactorEnabled' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        $maxMb = max(1, (int) floor($this->maxCvUploadKb / 1024));

        return [
            'newCv.uploaded' => "Upload failed before validation. Please upload a smaller PDF (up to {$maxMb}MB).",
            'newCv.max' => "The CV must be {$maxMb}MB or smaller.",
            'newAvatar.uploaded' => 'Profile image upload failed before validation. Please choose a smaller image and try again.',
            'newAvatar.max' => 'The profile image must be 2MB or smaller.',
        ];
    }

    public function mount(): void
    {
        $this->maxCvUploadKb = $this->resolveServerUploadLimitKb();

        $user = auth()->user();
        $candidate = Candidate::where('user_id', $user->id)
            ->first()
            ?? Candidate::where('email', $user->email)->first();

        // Backfill user_id on legacy records
        if ($candidate && empty($candidate->user_id)) {
            $candidate->update(['user_id' => $user->id]);
        }

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $candidate?->phone ?? '';
        $this->location = $candidate?->location ?? '';
        $this->linkedin = $candidate?->linkedin ?? '';
        $this->github = $candidate?->github ?? '';
        $this->portfolio = $candidate?->portfolio ?? '';
        $this->avatarUrl = (string) ($user->avatar_url ?? '');
        $this->twoFactorEnabled = (bool) ($user->two_factor_enabled ?? false);
    }

    public function updatedNewAvatar(): void
    {
        $this->resetErrorBag('newAvatar');
        $this->validateOnly('newAvatar');
    }

    public function updatedNewCv(): void
    {
        $this->resetErrorBag('newCv');
        $this->cvUploaded = false;
        $this->cvStatusMessage = '';
        $this->cvErrorMessage = '';
        $this->validateOnly('newCv');
        $this->selectedCvName = $this->newCv?->getClientOriginalName() ?? '';
    }

    public function save(): void
    {
        $this->validate();
        $this->saved = false;

        // Update user name
        $user = auth()->user();
        $avatarUrl = null;
        if ($this->newAvatar) {
            try {
                $avatarUrl = app(CloudinaryImageService::class)->uploadAvatar($this->newAvatar, (int) $user->id);
            } catch (\Throwable $exception) {
                report($exception);
                $this->addError('newAvatar', 'Profile photo upload failed. Please check Cloudinary settings and try again.');
                return;
            }
        }

        $userPayload = [
            'name' => $this->name,
            'two_factor_enabled' => $this->twoFactorEnabled,
        ];

        if ($avatarUrl !== null) {
            $userPayload['avatar'] = $avatarUrl;
        }

        $user->update($userPayload);

        if ($avatarUrl !== null) {
            $this->avatarUrl = $avatarUrl;
            $this->newAvatar = null;
            $this->dispatch('profile-avatar-updated', url: $avatarUrl);
        }

        try {
            $candidate = $this->upsertCandidateProfile($user);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('name', $this->buildProfileSaveErrorMessage($exception));
            return;
        }

        if ($this->newCv) {
            $this->processResumeUpload($candidate, $user);
        }

        $this->saved = true;
    }

    public function uploadResume(): void
    {
        $this->resetErrorBag('newCv');
        $this->cvUploaded = false;
        $this->cvStatusMessage = '';
        $this->cvErrorMessage = '';

        if (! $this->newCv) {
            $this->addError('newCv', 'Choose a PDF before uploading your resume.');
            return;
        }

        $this->validateOnly('newCv');

        $user = auth()->user();
        try {
            $candidate = $this->upsertCandidateProfile($user);
        } catch (\Throwable $exception) {
            report($exception);
            $this->cvErrorMessage = $this->buildProfileSaveErrorMessage($exception);
            $this->addError('newCv', $this->cvErrorMessage);
            return;
        }

        $this->processResumeUpload($candidate, $user);
    }

    public function render()
    {
        $candidate = Candidate::where('user_id', auth()->id())
            ->first()
            ?? Candidate::where('email', auth()->user()->email)->first();
        $appCount = $candidate?->applications()->count() ?? 0;
        $interviewCount = $candidate?->applications()->where('status', 'interview')->count() ?? 0;
        $profileProgress = $this->buildProfileProgress($candidate);

        return view(
            'livewire.candidate.candidate-profile',
            compact('candidate', 'appCount', 'interviewCount', 'profileProgress')
        )->layout('layouts.app');
    }

    private function buildProfileProgress(?Candidate $candidate): array
    {
        $fieldValues = [
            'name' => trim($this->name),
            'phone' => trim($this->phone),
            'location' => trim($this->location),
            'linkedin' => trim($this->linkedin),
            'github' => trim($this->github),
            'portfolio' => trim($this->portfolio),
            'cv' => trim((string) ($candidate?->cv_original_name ?? '')),
        ];

        $labels = [
            'name' => 'Full Name',
            'phone' => 'Phone',
            'location' => 'Location',
            'linkedin' => 'LinkedIn URL',
            'github' => 'GitHub URL',
            'portfolio' => 'Portfolio URL',
            'cv' => 'Resume (PDF)',
        ];

        $completed = collect(self::PROFILE_FIELDS)
            ->filter(fn(string $field) => filled($fieldValues[$field] ?? null))
            ->values();

        $missing = collect(self::PROFILE_FIELDS)
            ->reject(fn(string $field) => filled($fieldValues[$field] ?? null))
            ->map(fn(string $field) => $labels[$field])
            ->values()
            ->all();

        $score = (int) round(($completed->count() / count(self::PROFILE_FIELDS)) * 100);

        return [
            'score' => $score,
            'completed' => $completed->count(),
            'total' => count(self::PROFILE_FIELDS),
            'missing' => $missing,
            'is_ready' => $score >= 70,
        ];
    }

    private function resolveServerUploadLimitKb(): int
    {
        $uploadMax = $this->iniSizeToKb((string) ini_get('upload_max_filesize'));
        $postMax = $this->iniSizeToKb((string) ini_get('post_max_size'));

        $serverLimit = min(
            $uploadMax > 0 ? $uploadMax : 5120,
            $postMax > 0 ? $postMax : 5120
        );

        // Keep some headroom for non-file form payload.
        return max(1024, $serverLimit - 256);
    }

    private function iniSizeToKb(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) round($number * 1024 * 1024),
            'm' => (int) round($number * 1024),
            'k' => (int) round($number),
            default => (int) round($number / 1024),
        };
    }

    private function upsertCandidateProfile($user): Candidate
    {
        $candidate = Candidate::where('user_id', $user->id)
            ->first()
            ?? Candidate::where('email', $user->email)->first()
            ?? new Candidate();

        if (empty($candidate->company_id) && !empty($user->company_id)) {
            $candidate->company_id = $user->company_id;
        }

        $candidate->user_id = $user->id;
        $candidate->email = $user->email;
        $candidate->name = $this->name;
        $candidate->phone = $this->phone;
        $candidate->location = $this->location;
        $candidate->linkedin = $this->linkedin;
        $candidate->github = $this->github;
        $candidate->portfolio = $this->portfolio;
        $candidate->save();

        return $candidate;
    }

    private function processResumeUpload(Candidate $candidate, $user): void
    {
        try {
            $path = $this->newCv->store('cvs/' . $user->id, 'private');
        } catch (\Throwable $exception) {
            report($exception);
            $this->cvErrorMessage = 'Resume upload failed before parsing. Check the file size and try again.';
            $this->addError('newCv', $this->cvErrorMessage);
            return;
        }

        $candidate->update([
            'cv_path' => $path,
            'cv_original_name' => $this->newCv->getClientOriginalName(),
            'cv_status' => 'processing',
            'cv_raw_text' => null,
        ]);

        try {
            $parsed = app(ResumeParserService::class)
                ->parsePdf(storage_path('app/private/' . $path));

            $filledFields = $this->fillEmptyProfileFieldsFromResume($parsed);

            $user->update([
                'name' => $this->name !== '' ? $this->name : $user->name,
            ]);

            $candidate->update([
                'name' => $this->name,
                'phone' => $this->phone,
                'location' => $this->location,
                'linkedin' => $this->linkedin,
                'github' => $this->github,
                'portfolio' => $this->portfolio,
                'cv_raw_text' => (string) ($parsed['raw_text'] ?? ''),
                'extracted_skills' => $parsed['skills'] ?? [],
                'extracted_experience' => $parsed['experience'] ?? [],
                'extracted_education' => $parsed['education'] ?? [],
                'cv_status' => 'processed',
            ]);

            $this->cvUploaded = true;
            $this->cvErrorMessage = '';
            $this->cvStatusMessage = $filledFields > 0
                ? "Resume uploaded and parsed successfully. {$filledFields} empty profile field(s) were filled from your CV."
                : 'Resume uploaded and parsed successfully. Review the extracted CV details below.';
        } catch (\Throwable $exception) {
            report($exception);

            $candidate->update([
                'cv_status' => 'failed',
            ]);

            $this->cvUploaded = false;
            $this->cvErrorMessage = 'Resume was uploaded, but parsing failed. Upload a text-based PDF and try again.';
            $this->addError('newCv', $this->cvErrorMessage);
        } finally {
            $this->selectedCvName = '';
            $this->newCv = null;
        }
    }

    private function fillEmptyProfileFieldsFromResume(array $parsed): int
    {
        $filledFields = 0;

        foreach ([
            'name' => 'name',
            'phone' => 'phone',
            'location' => 'location',
            'linkedin' => 'linkedin',
            'github' => 'github',
            'portfolio' => 'portfolio',
        ] as $property => $parsedKey) {
            $currentValue = trim((string) $this->{$property});
            $parsedValue = trim((string) ($parsed[$parsedKey] ?? ''));

            if ($this->shouldAutofillField($property, $currentValue, $parsedValue)) {
                $this->{$property} = $parsedValue;
                $filledFields++;
            }
        }

        return $filledFields;
    }

    private function shouldAutofillField(string $property, string $currentValue, string $parsedValue): bool
    {
        if ($parsedValue === '') {
            return false;
        }

        if ($currentValue === '') {
            if ($property === 'location') {
                return $this->looksLikeLocation($parsedValue);
            }
            return true;
        }

        if (in_array($property, ['linkedin', 'github', 'portfolio'], true)) {
            return !$this->isValidUrl($currentValue) && $this->isValidUrl($parsedValue);
        }

        if ($property === 'location') {
            if (!$this->looksLikeLocation($parsedValue)) {
                return false;
            }

            $currentLooksNoisy = str_contains($currentValue, '@')
                || str_contains(strtolower($currentValue), 'http')
                || preg_match('/\+?\d[\d\-\s()]{6,}/', $currentValue);

            $parsedLooksClean = !str_contains($parsedValue, '@')
                && !str_contains(strtolower($parsedValue), 'http')
                && !preg_match('/\+?\d[\d\-\s()]{6,}/', $parsedValue);

            return (bool) ($currentLooksNoisy && $parsedLooksClean);
        }

        return false;
    }

    private function isValidUrl(string $value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_URL);
    }

    private function looksLikeLocation(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) < 3 || mb_strlen($value) > 80) {
            return false;
        }

        $lower = strtolower($value);
        if (str_contains($value, '@') || str_contains($lower, 'http')) {
            return false;
        }

        if (preg_match('/\+?\d[\d\-\s()]{6,}/', $value)) {
            return false;
        }

        $wordCount = preg_split('/\s+/', $value);
        $wordCount = is_array($wordCount) ? count(array_filter($wordCount, fn(string $word) => $word !== '')) : 0;

        if (!str_contains($value, ',')) {
            // Allow simple city-style values like "Glasgow" while blocking full sentence fragments.
            if ($wordCount === 0 || $wordCount > 4) {
                return false;
            }

            if (!preg_match('/^[A-Za-z][A-Za-z\s\.\'-]+$/', $value)) {
                return false;
            }
        }

        foreach ([
            'provided',
            'developed',
            'managed',
            'responsible',
            'ensuring',
            'experience',
            'customer',
            'service',
            'project',
            'using',
        ] as $keyword) {
            if (str_contains($lower, $keyword)) {
                return false;
            }
        }

        return true;
    }

    private function buildProfileSaveErrorMessage(\Throwable $exception): string
    {
        $defaultMessage = 'Profile update failed. Please refresh and try again.';
        $message = (string) $exception->getMessage();

        if (str_contains(strtolower($message), 'company_id')) {
            return 'Your account is not linked to a company yet. Contact support to complete account setup.';
        }

        if (app()->environment('local') && $message !== '') {
            return $defaultMessage . " Error: {$message}";
        }

        return $defaultMessage;
    }
}
