<div class="cv-scan-paper-fallback">
    <div class="cv-fallback-header">
        <h3>{{ $application->candidate->name ?? 'Candidate Profile' }}</h3>
        <p>{{ $primaryHeadline }}</p>
        <div class="cv-fallback-contact">
            @if($application->candidate->email)
                <span>{{ $application->candidate->email }}</span>
            @endif
            @if($application->candidate->phone)
                <span>{{ $application->candidate->phone }}</span>
            @endif
            @if($application->candidate->location)
                <span>{{ $application->candidate->location }}</span>
            @endif
        </div>
    </div>

    <section class="cv-fallback-section">
        <p class="cv-fallback-heading">Professional Summary</p>
        @foreach($summaryPreview as $line)
            <p class="cv-fallback-copy">{{ \Illuminate\Support\Str::limit($line, 135) }}</p>
        @endforeach
    </section>

    <section class="cv-fallback-section">
        <p class="cv-fallback-heading">Work Experience</p>
        @forelse($experiencePreview as $experience)
            <div class="cv-fallback-entry">
                <div class="cv-fallback-entry-head">
                    <p>{{ $experience['title'] ?? 'Role' }}</p>
                    <span>{{ $experience['duration'] ?? 'Tenure mapped' }}</span>
                </div>
                <p class="cv-fallback-meta">{{ $experience['company'] ?? 'Company details extracted from CV' }}</p>
                <p class="cv-fallback-copy">{{ \Illuminate\Support\Str::limit($experience['description'] ?? 'Responsibilities and delivery highlights are being structured for recruiter review.', 130) }}</p>
            </div>
        @empty
            <p class="cv-fallback-copy">Career history is being segmented into recruiter-ready entries.</p>
        @endforelse
    </section>

    <section class="cv-fallback-section cv-fallback-grid">
        <div>
            <p class="cv-fallback-heading">Education</p>
            @forelse($educationPreview as $education)
                <div class="cv-fallback-entry compact">
                    <div class="cv-fallback-entry-head">
                        <p>{{ $education['degree'] ?? 'Qualification' }}</p>
                        <span>{{ $education['year'] ?? 'Year' }}</span>
                    </div>
                    <p class="cv-fallback-meta">{{ $education['institution'] ?? 'Institution extracted from CV' }}</p>
                </div>
            @empty
                <p class="cv-fallback-copy">Qualifications and certifications are being indexed.</p>
            @endforelse
        </div>

        <div>
            <p class="cv-fallback-heading">Skills</p>
            <div class="cv-fallback-tags">
                @forelse($skillsPreview as $skill)
                    <span>{{ $skill }}</span>
                @empty
                    <span>Role-fit mapping</span>
                    <span>Skill extraction</span>
                    <span>Experience parsing</span>
                @endforelse
            </div>
        </div>
    </section>

    <section class="cv-fallback-section">
        <p class="cv-fallback-heading">Certifications / Other</p>
        @forelse($profileSignals as $signal)
            <p class="cv-fallback-copy">{{ \Illuminate\Support\Str::limit($signal, 110) }}</p>
        @empty
            <p class="cv-fallback-copy">Portfolio, social links, and supporting credentials are being cross-checked.</p>
        @endforelse
    </section>
</div>
