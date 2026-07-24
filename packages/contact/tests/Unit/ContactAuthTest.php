<?php

declare(strict_types=1);

use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\Auth;
use Moox\Contact\Models\Contact;

it('is authenticatable and can log in via the contact guard', function (): void {
    $contact = Contact::factory()->authenticatable('secret-pass')->create();

    expect($contact)->toBeInstanceOf(AuthenticatableContract::class)
        ->and($contact->canAuthenticate())->toBeTrue()
        ->and(Auth::guard('contact')->attempt([
            'username' => $contact->username,
            'password' => 'secret-pass',
        ]))->toBeTrue()
        ->and(Auth::guard('contact')->id())->toBe($contact->getKey());
});

it('is a filament user for non-admin panels when active and authenticatable', function (): void {
    $contact = Contact::factory()->authenticatable()->create();
    $portal = Panel::make()->id('portal');
    $admin = Panel::make()->id('admin');

    expect($contact->canAccessPanel($portal))->toBeTrue()
        ->and($contact->canAccessPanel($admin))->toBeFalse()
        ->and($contact->getFilamentName())->toBe($contact->displayLabel());
});

it('cannot access filament panels without credentials or when inactive', function (): void {
    $withoutPassword = Contact::factory()->create([
        'status' => 'active',
        'username' => 'active.user',
        'email' => 'active@example.com',
        'password' => null,
    ]);
    $inactive = Contact::factory()->authenticatable()->inactive()->create();
    $portal = Panel::make()->id('portal');

    expect($withoutPassword->canAccessPanel($portal))->toBeFalse()
        ->and($inactive->canAccessPanel($portal))->toBeFalse();
});
