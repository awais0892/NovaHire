# Stripe Subscriptions (AO Recruiter App)

## Environment

Add these in `.env`:

```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
STRIPE_CURRENCY=usd
STRIPE_PRICE_BASIC=price_xxx
STRIPE_PRICE_PRO=price_xxx
STRIPE_PRICE_ENTERPRISE=price_xxx
```

## Local Webhook Testing (Stripe CLI)

1. Install Stripe CLI: https://docs.stripe.com/stripe-cli
2. Authenticate:

```bash
stripe login
```

3. Forward events to Laravel:

```bash
stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook
```

4. Copy shown webhook signing secret into `STRIPE_WEBHOOK_SECRET`.

## Test Cards

- Success: `4242 4242 4242 4242`
- Payment failure: `4000 0000 0000 9995`

## Useful Event Triggers

```bash
stripe trigger checkout.session.completed
stripe trigger invoice.paid
stripe trigger invoice.payment_failed
stripe trigger customer.subscription.deleted
```
