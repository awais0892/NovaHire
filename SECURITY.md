# Security Policy

## Supported Versions

This repository follows a rolling release model on `main`.

## Reporting a Vulnerability

If you discover a security issue, do not open a public issue with exploit details.

1. Send a private report to the project owner.
2. Include reproduction steps, impact, and affected files/routes.
3. Provide mitigation ideas when possible.

## Secure Development Guidelines

- Keep secrets in environment variables, never in source code.
- Do not commit `.env`, API keys, tokens, credentials, or private keys.
- Validate all external webhook signatures (for example Stripe).
- Enforce authorization via policies/middleware for every protected route.
- Sanitize and validate all user inputs with request validation.
- Keep dependencies updated and patch known CVEs quickly.

## Operational Checklist

- Rotate leaked keys immediately.
- Enable HTTPS and secure cookies in production.
- Restrict CORS and trusted proxy settings to known origins.
- Ensure queue workers and cron jobs run with least privilege.
- Monitor logs and alert on repeated authentication or webhook failures.
