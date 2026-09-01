<?php

declare(strict_types=1);

use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

it('lists the demo mail preview', function (): void {
    $this->get(route('login-link.examples.index'))
        ->assertSuccessful()
        ->assertSee('Process link', false)
        ->assertSee('Expired link', false);
});

it('renders the packaged html demo mail', function (): void {
    $this->get(route('login-link.examples.mail'))
        ->assertSuccessful()
        ->assertSee(__('login-link::translations.mail_cta'), false)
        ->assertSee('<!doctype html>', false);
});

it('renders the packaged html demo for an expired link', function (): void {
    $this->get(route('login-link.examples.unavailable', ['reason' => 'expired']))
        ->assertSuccessful()
        ->assertSee('<!doctype html>', false)
        ->assertSee(__('login-link::translations.public_expired_title'), false)
        ->assertDontSee('<mjml', false)
        ->assertDontSee('filament', false);
});

it('renders the packaged html demo for a used link', function (): void {
    $this->get(route('login-link.examples.unavailable', ['reason' => 'used']))
        ->assertSuccessful()
        ->assertSee(__('login-link::translations.public_used_title'), false);
});
