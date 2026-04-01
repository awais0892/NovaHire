<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('public product and seo pages are accessible without login', function () {
    get(route('public.product'))->assertOk();
    get(route('public.features'))->assertOk();
    get(route('public.pricing'))->assertOk();
    get(route('public.about'))->assertOk();
    get(route('public.contact'))->assertOk();
    get(route('public.faq'))->assertOk();
    get(route('public.privacy'))->assertOk();
    get(route('public.terms'))->assertOk();
});

test('sitemap endpoint returns valid xml response', function () {
    get(route('public.sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<urlset', false)
        ->assertSee(route('home'), false);
});

