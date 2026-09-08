<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Support\MailTemplateRenderer;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('mail-template.layouts', [
        'welcome' => 'Welcome',
        'theme-heco::emails.invoice' => 'Invoice',
    ]);
});

it('stores translatable fields on the translation and slug on the parent', function (): void {
    $template = MailTemplate::factory()
        ->translation([
            'title' => 'Dein Login-Link',
            'brand_name' => 'heco',
            'mail_content' => '<mj-text>Hallo</mj-text>',
            'footer' => null,
        ], 'de')
        ->create([
            'slug' => 'login',
            'layout' => 'welcome',
        ]);

    expect($template->slug)->toBe('login')
        ->and($template->layout)->toBe('welcome')
        ->and($template->hasTranslation('de'))->toBeTrue()
        ->and($template->translate('de')->title)->toBe('Dein Login-Link')
        ->and($template->translate('de')->brand_name)->toBe('heco')
        ->and($template->translate('de')->mail_content)->toBe('<mj-text>Hallo</mj-text>');

    $template->translateOrNew('en')->fill([
        'title' => 'Your login link',
        'brand_name' => 'heco',
        'mail_content' => '<mj-text>Hello</mj-text>',
        'footer' => null,
    ])->save();

    expect($template->fresh()->hasTranslation('en'))->toBeTrue()
        ->and($template->translate('en')->title)->toBe('Your login link')
        ->and($template->translate('de')->title)->toBe('Dein Login-Link');
});

it('builds a public logo url from a stored path', function (): void {
    $template = MailTemplate::factory()->create([
        'logo_path' => 'mail-templates/logo.png',
    ]);

    expect($template->logo_url)->toContain('mail-templates/logo.png');
});

it('finds a template by slug and applies the requested locale', function (): void {
    $template = MailTemplate::factory()
        ->translation([
            'title' => 'Ihre Rechnung',
        ])
        ->create([
            'slug' => 'invoice',
            'layout' => 'theme-heco::emails.invoice',
        ]);

    $template->translateOrNew('en')->fill([
        'title' => 'Your invoice',
        'brand_name' => 'Acme',
        'mail_content' => null,
        'footer' => null,
    ])->save();

    $found = app(MailTemplateRenderer::class)->find('invoice', 'en');

    expect($found)->not->toBeNull()
        ->and($found->getKey())->toBe($template->getKey())
        ->and($found->title)->toBe('Your invoice');
});

it('falls back to another translation when the locale is missing', function (): void {
    MailTemplate::factory()
        ->translation([
            'title' => 'Dein Login-Link',
        ])
        ->create([
            'slug' => 'login',
            'layout' => 'welcome',
        ]);

    $found = app(MailTemplateRenderer::class)->find('login', 'fr');

    expect($found)->not->toBeNull()
        ->and($found->title)->toBe('Dein Login-Link');
});

it('returns null when the slug does not exist', function (): void {
    expect(app(MailTemplateRenderer::class)->find('missing'))->toBeNull();
});

it('creates a default translation from the factory', function (): void {
    $template = MailTemplate::factory()->create();

    expect($template->translations)->not->toBeEmpty()
        ->and($template->title)->toBe('Demo');
});

it('enforces a unique slug on the parent', function (): void {
    MailTemplate::factory()->create(['slug' => 'login']);

    expect(fn (): MailTemplate => MailTemplate::factory()->create(['slug' => 'login']))
        ->toThrow(UniqueConstraintViolationException::class);
});
