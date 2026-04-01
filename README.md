# NovaHire

NovaHire is an AI-assisted recruitment platform built with Laravel and Tailwind. It supports multi-role hiring workflows across recruiters, hiring managers, and candidates.

## Core Capabilities

- Job listing and application pipeline management
- Candidate profiles, CV upload, and screening workflows
- AI-assisted CV analysis and recruiter notes
- Interview scheduling, reminders, and slot management
- Role-based access control for admin, recruiter, hiring manager, and candidate flows
- Public marketing pages and employer-facing onboarding
- Billing and subscription hooks (Stripe-ready)

## Tech Stack

- Backend: Laravel 12, PHP 8.2+
- Frontend: Blade, Livewire, Alpine.js, Tailwind CSS, Vite
- Database: MySQL/PostgreSQL/SQLite
- Optional Integrations: OpenAI, Stripe, Google OAuth/Calendar

## Local Setup

1. Clone and install dependencies.

```bash
git clone https://github.com/awais0892/NovaHire.git
cd NovaHire
composer install
npm install
```

2. Configure environment.

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure database in `.env`, then run migrations.

```bash
php artisan migrate --seed
```

4. Start development services.

```bash
composer run dev
```

## Testing

```bash
php artisan test
```

## Security Checklist

- Never commit `.env` or real credentials
- Rotate API keys if they are ever exposed
- Use least-privilege keys for OpenAI, Stripe, and Google APIs
- Restrict webhook endpoints and validate webhook signatures
- Enable HTTPS in all non-local environments
- Review role and policy changes before deployment

## Git Hygiene

- `.gitignore` is configured to exclude secrets, local caches, build artifacts, and temp probe files
- Keep only placeholders in `.env.example`
- Run a secret scan before pushing:

```bash
rg -n "(OPENAI_API_KEY|STRIPE_SECRET|GOOGLE_CLIENT_SECRET|BEGIN RSA PRIVATE KEY|ghp_)" -g "!.git" -g "!node_modules" -g "!vendor"
```

## Deployment Notes

Before production deploy:

- Set all required environment variables
- Run `php artisan config:cache` and `php artisan route:cache`
- Run migrations in a maintenance-safe window
- Ensure queue workers and cron are configured

## License

This project includes upstream TailAdmin Laravel starter assets and NovaHire custom application code. Review third-party licenses before commercial redistribution.
