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
            'mail_content' => '<mj-text>Hallo</mj-text>',
            'footer' => null,
        ], 'de_DE')
        ->create([
            'slug' => 'login',
            'layout' => 'welcome',
        ]);

    expect($template->slug)->toBe('login')
        ->and($template->layout)->toBe('welcome')
        ->and($template->hasTranslation('de_DE'))->toBeTrue()
        ->and($template->translate('de_DE')->title)->toBe('Dein Login-Link')
        ->and($template->translate('de_DE')->mail_content)->toBe('<mj-text>Hallo</mj-text>');

    $template->translateOrNew('en_US')->fill([
        'title' => 'Your login link',
        'mail_content' => '<mj-text>Hello</mj-text>',
        'footer' => null,
    ])->save();

    expect($template->fresh()->hasTranslation('en_US'))->toBeTrue()
        ->and($template->translate('en_US')->title)->toBe('Your login link')
        ->and($template->translate('de_DE')->title)->toBe('Dein Login-Link');
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

    $template->translateOrNew('en_US')->fill([
        'title' => 'Your invoice',
        'mail_content' => null,
        'footer' => null,
    ])->save();

    $found = app(MailTemplateRenderer::class)->find('invoice', 'en_US');

    expect($found)->not->toBeNull()
        ->and($found->getKey())->toBe($template->getKey())
        ->and($found->getDefaultLocale())->toBe('en_US')
        ->and($found->translate('en_US')->title)->toBe('Your invoice');
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
        ->and($found->getDefaultLocale())->toBe('de_DE')
        ->and($found->translate('de_DE')->title)->toBe('Dein Login-Link');
});

it('applies a regional translation when the requested locale is the language code', function (): void {
    $template = MailTemplate::factory()
        ->translation([
            'title' => 'Dein Login-Link',
        ], 'de_DE')
        ->create([
            'slug' => 'login',
            'layout' => 'welcome',
        ]);

    $template->translateOrNew('en_US')->fill([
        'title' => 'Your login link',
        'mail_content' => null,
        'footer' => null,
    ])->save();

    $german = app(MailTemplateRenderer::class)->find('login', 'de');
    $english = app(MailTemplateRenderer::class)->find('login', 'en');

    expect($german)->not->toBeNull()
        ->and($german->getDefaultLocale())->toBe('de_DE')
        ->and($german->translate('de_DE')->title)->toBe('Dein Login-Link')
        ->and($english->getDefaultLocale())->toBe('en_US')
        ->and($english->translate('en_US')->title)->toBe('Your login link');
});

it('returns null when the slug does not exist', function (): void {
    expect(app(MailTemplateRenderer::class)->find('missing'))->toBeNull();
});

it('creates a default translation from the factory', function (): void {
    $template = MailTemplate::factory()->create();

    expect($template->translations)->not->toBeEmpty()
        ->and($template->hasTranslation('de_DE'))->toBeTrue()
        ->and($template->translate('de_DE')->title)->toBe('Demo');
});

it('enforces a unique slug on the parent', function (): void {
    MailTemplate::factory()->create(['slug' => 'login']);

    expect(fn (): MailTemplate => MailTemplate::factory()->create(['slug' => 'login']))
        ->toThrow(UniqueConstraintViolationException::class);
});
 MailTemplate => MailTemplate::factory()->create(['slug' => 'login']))
        ->toThrow(UniqueConstraintViolationException::class);
});
;
 MailTemplate => MailTemplate::factory()->create(['slug' => 'login']))
        ->toThrow(UniqueConstraintViolationException::class);
});
);
;
 MailTemplate => MailTemplate::factory()->create(['slug' => 'login']))
        ->toThrow(UniqueConstraintViolationException::class);
});
