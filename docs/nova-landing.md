# Nova Hire Landing Architecture

## Files
- `resources/views/pages/landing.blade.php`: page-level content arrays and section composition.
- `resources/views/components/landing/nova/*.blade.php`: reusable landing sections (`nav`, `hero`, `trusted`, `pillars`, `process`, `testimonials`, `faq`, `footer`).
- `resources/js/nova-landing.ts`: animation/runtime layer.
- `resources/css/nova-landing.css`: landing visual system and interaction styling.

## Animation system
- `Lenis` handles smooth scrolling globally for the landing page unless `prefers-reduced-motion` is enabled.
- `GSAP + ScrollTrigger` drive:
  - hero load reveal (SplitText-style word animation)
  - scroll-linked section reveals
  - sticky testimonial card timeline through a long scroll section
  - FAQ panel height transitions
  - tab panel entrance transitions
- `Swiper` powers the 5-pillar carousel with navigation arrows and hover/de-hover card states.
- `Lottie` powers menu/ribbon decorative animations using `resources/js/assets/lottie/ai-analysis-loader.json`.

## Accessibility and performance
- `prefers-reduced-motion` disables heavy motion and preserves readable states.
- Tabs, FAQ, and mobile nav are keyboard-operable with ARIA states.
- Footer video is lazy-loaded using `IntersectionObserver` and `data-src` source hydration.

## Editing copy
- Update text arrays in `resources/views/pages/landing.blade.php`:
  - `$trustedLogos`
  - `$pillars`
  - `$beforeHire` / `$afterHire`
  - `$testimonialStories`
  - `$faqs`
  - `$footerMarquee`

## Editing media
- Hero and footer video paths are set in:
  - `resources/views/components/landing/nova/hero.blade.php`
  - `resources/views/components/landing/nova/footer.blade.php`
- Pillar card images are configured in `$pillars` inside `landing.blade.php`.
- Replace or add local files under `public/images/...` and update paths in the arrays/components.
