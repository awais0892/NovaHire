<?php

use App\Models\Company;
use App\Models\User;
use App\Services\Billing\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Stripe\Checkout\Session as StripeCheckoutSession;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

function billingUser(): User
{
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

    $company = Company::create([
        'name' => 'Billing Co',
        'slug' => 'billing-co',
        'email' => 'billing-co@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $user->assignRole('hr_admin');

    return $user;
}

test('billing checkout redirects to stripe checkout url', function () {
    $user = billingUser();

    $stripeSession = StripeCheckoutSession::constructFrom([
        'id' => 'cs_test_123',
        'object' => 'checkout.session',
        'url' => 'https://checkout.stripe.test/session_123',
    ]);

    $billing = Mockery::mock(StripeBillingService::class);
    $billing->shouldReceive('availablePlanKeys')
        ->once()
        ->andReturn(['basic', 'pro', 'enterprise']);
    $billing->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn($stripeSession);

    $this->app->instance(StripeBillingService::class, $billing);

    actingAs($user);

    post(route('billing.checkout'), ['plan' => 'pro'])
        ->assertRedirect('https://checkout.stripe.test/session_123');
});

test('billing success without session id returns validation-style error redirect', function () {
    $user = billingUser();
    actingAs($user);

    get(route('billing.success'))
        ->assertRedirect(route('account.settings'))
        ->assertSessionHasErrors('billing');
});

test('stripe webhook endpoint delegates to billing service and returns service status', function () {
    $billing = Mockery::mock(StripeBillingService::class);
    $billing->shouldReceive('handleWebhook')
        ->once()
        ->andReturn(['ok' => true, 'status' => 200]);

    $this->app->instance(StripeBillingService::class, $billing);

    postJson(route('stripe.webhook'), ['type' => 'invoice.paid'], [
        'Stripe-Signature' => 't=1,v1=fake',
    ])
        ->assertOk()
        ->assertJson(['ok' => true]);
});

test('stripe webhook endpoint returns non-200 when billing service rejects payload', function () {
    $billing = Mockery::mock(StripeBillingService::class);
    $billing->shouldReceive('handleWebhook')
        ->once()
        ->andReturn(['ok' => false, 'status' => 400]);

    $this->app->instance(StripeBillingService::class, $billing);

    postJson(route('stripe.webhook'), ['type' => 'invalid'], [
        'Stripe-Signature' => 't=1,v1=bad',
    ])
        ->assertStatus(400)
        ->assertJson(['ok' => false]);
});
