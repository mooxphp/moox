<?php

declare(strict_types=1);

use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

it('lists the three example mail previews', function (): void {
    $this->get(route('login-link.examples.index'))
        ->assertSuccessful()
        ->assertSee('Passwordless login', false)
        ->assertSee('Email verification', false)
        ->assertSee('Mass mail verification', false);
});

it('renders the example mail template', function (string $template, string $needle): void {
    $this->get(route('login-link.examples.mail', $template))
        ->assertSuccessful()
        ->assertSee($needle, false);
})->with([
    'login' => ['login', 'Sign in'],
    'verify-email' => ['verify-email', 'Verify email address'],
    'mass-mail' => ['mass-mail', 'Yes, I received this'],
]);

it('rejects unknown mail preview templates', function (): void {
    $this->get(route('login-link.examples.mail', 'dump'))
        ->assertNotFound();
});
