@props(['heroData'])

<section class="card p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Hero</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="label">Badge</label>
            <input class="input" name="hero_badge" value="{{ data_get($heroData, 'badge') }}">
        </div>
        <div>
            <label class="label">Hero Image Path</label>
            <input class="input" name="hero_image" value="{{ data_get($heroData, 'image') }}">
        </div>
        <div>
            <label class="label">Hero Background Video Path</label>
            <input class="input" name="hero_video" value="{{ data_get($heroData, 'video') }}" placeholder="/images/your-video.mp4">
        </div>
        <div class="md:col-span-2">
            <label class="label">Title</label>
            <input class="input" name="hero_title" value="{{ data_get($heroData, 'title') }}">
        </div>
        <div class="md:col-span-2">
            <label class="label">Subtitle</label>
            <textarea class="input min-h-24" name="hero_subtitle">{{ data_get($heroData, 'subtitle') }}</textarea>
        </div>
        <div>
            <label class="label">Primary CTA Text</label>
            <input class="input" name="hero_primary_cta_text" value="{{ data_get($heroData, 'primary_cta_text') }}">
        </div>
        <div>
            <label class="label">Primary CTA URL</label>
            <input class="input" name="hero_primary_cta_url" value="{{ data_get($heroData, 'primary_cta_url') }}">
        </div>
        <div>
            <label class="label">Secondary CTA Text</label>
            <input class="input" name="hero_secondary_cta_text" value="{{ data_get($heroData, 'secondary_cta_text') }}">
        </div>
        <div>
            <label class="label">Secondary CTA URL</label>
            <input class="input" name="hero_secondary_cta_url" value="{{ data_get($heroData, 'secondary_cta_url') }}">
        </div>
    </div>
</section>
