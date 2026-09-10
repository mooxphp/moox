<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Resources\MailTemplateResource;
use Moox\MailTemplate\Support\MailMedia;
use Moox\MailTemplate\Support\MailTemplateRenderer;
use Moox\Media\Forms\Components\MediaPicker;

uses(RefreshDatabase::class);

it('stores translatable fields on the translation and slug on the parent', function (): void {
    $template = MailTemplate::factory()
        ->translation([
            'title' => 'Dein Login-Link',
            'mail_content' => '<mj-text>Hallo</mj-text>',
            'footer' => null,
        ], 'de_DE')
        ->create([
            'slug' => 'login',
        ]);

    expect($template->slug)->toBe('login')
        ->and($template->mailLayout)->not->toBeNull()
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
        'logo' => 'mail-templates/logo.png',
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

it('falls back to layout logo and footer when the template leaves them empty', function (): void {
    $layout = MailLayout::factory()
        ->translation([
            'title' => 'Login-Link',
            'footer' => '<mj-text>LAYOUT-FOOTER</mj-text>',
        ], 'de_DE')
        ->create([
            'slug' => 'login-link',
            'logo' => 'mail-layouts/layout-logo.png',
        ]);

    $template = MailTemplate::factory()
        ->translation([
            'title' => 'Dein Login-Link',
            'mail_content' => '<mj-text>Hallo</mj-text>',
            'footer' => null,
        ], 'de_DE')
        ->create([
            'slug' => 'login',
            'mail_layout_id' => $layout->getKey(),
            'logo' => null,
        ]);

    $data = app(MailTemplateRenderer::class)->viewData($template);

    expect($data['footer'])->toBe('<mj-text>LAYOUT-FOOTER</mj-text>')
        ->and($data['logoUrl'])->toContain('mail-layouts/layout-logo.png');
});

it('prefers template logo and footer over the layout', function (): void {
    $layout = MailLayout::factory()
        ->translation([
            'title' => 'Login-Link',
            'footer' => '<mj-text>LAYOUT-FOOTER</mj-text>',
        ], 'de_DE')
        ->create([
            'slug' => 'login-link',
            'logo' => 'mail-layouts/layout-logo.png',
        ]);

    $template = MailTemplate::factory()
        ->translation([
            'title' => 'Dein Login-Link',
            'mail_content' => '<mj-text>Hallo</mj-text>',
            'footer' => '<mj-text>TEMPLATE-FOOTER</mj-text>',
        ], 'de_DE')
        ->create([
            'slug' => 'login',
            'mail_layout_id' => $layout->getKey(),
            'logo' => 'mail-templates/template-logo.png',
        ]);

    $data = app(MailTemplateRenderer::class)->viewData($template);

    expect($data['footer'])->toBe('<mj-text>TEMPLATE-FOOTER</mj-text>')
        ->and($data['logoUrl'])->toContain('mail-templates/template-logo.png');
});

it('renders the package default view without a theme override', function (): void {
    config()->set('mail-template.view', 'mail-template::emails.layout');

    $template = MailTemplate::factory()
        ->translation([
            'mail_content' => '<mj-text>PACKAGE-CONTENT</mj-text>',
            'footer' => '<mj-text>PACKAGE-FOOTER</mj-text>',
        ], 'de_DE')
        ->create();

    $mjml = app(MailTemplateRenderer::class)->toMjml($template);

    expect($mjml)
        ->toContain('<mjml>')
        ->toContain('PACKAGE-CONTENT')
        ->toContain('PACKAGE-FOOTER');
});

it('stores layout title and footer on the translation', function (): void {
    $layout = MailLayout::factory()
        ->translation([
            'title' => 'Login-Link',
            'footer' => '<mj-text>Footer</mj-text>',
        ], 'de_DE')
        ->create(['slug' => 'login-link']);

    expect($layout->slug)->toBe('login-link')
        ->and($layout->hasTranslation('de_DE'))->toBeTrue()
        ->and($layout->translate('de_DE')->title)->toBe('Login-Link')
        ->and($layout->translate('de_DE')->footer)->toBe('<mj-text>Footer</mj-text>');
});

it('passes layout colors into the mail view data', function (): void {
    $layout = MailLayout::factory()
        ->translation(['title' => 'Brand'], 'de_DE')
        ->create([
            'slug' => 'brand',
            'background_color' => '#112233',
            'button_color' => '#445566',
            'text_color' => '#778899',
        ]);

    $template = MailTemplate::factory()
        ->translation([
            'title' => 'Subject',
            'mail_content' => '<mj-text>Hi</mj-text>',
        ], 'de_DE')
        ->create([
            'mail_layout_id' => $layout->getKey(),
        ]);

    $data = app(MailTemplateRenderer::class)->viewData($template);

    expect($data['backgroundColor'])->toBe('#112233')
        ->and($data['buttonColor'])->toBe('#445566')
        ->and($data['textColor'])->toBe('#778899');
});

it('blocks deleting a layout that is still referenced by templates', function (): void {
    $layout = MailLayout::factory()->create();
    MailTemplate::factory()->create([
        'mail_layout_id' => $layout->getKey(),
    ]);

    expect($layout->isReferencedByTemplates())->toBeTrue()
        ->and($layout->delete())->toBeFalse()
        ->and($layout->forceDelete())->toBeFalse()
        ->and($layout->fresh())->not->toBeNull()
        ->and($layout->fresh()?->trashed())->toBeFalse();
});

it('soft-deletes a layout that is not referenced by templates', function (): void {
    $layout = MailLayout::factory()->create();

    expect($layout->delete())->not->toBeFalse()
        ->and(MailLayout::query()->find($layout->getKey()))->toBeNull()
        ->and(MailLayout::withTrashed()->find($layout->getKey())?->trashed())->toBeTrue();
});

it('keeps the assigned layout after it was soft-deleted', function (): void {
    $layout = MailLayout::factory()
        ->translation([
            'title' => 'Legacy',
            'footer' => '<mj-text>TRASHED-FOOTER</mj-text>',
        ], 'de_DE')
        ->create([
            'slug' => 'legacy',
            'logo' => 'mail-layouts/trashed.png',
        ]);

    $template = MailTemplate::factory()
        ->translation([
            'footer' => null,
        ], 'de_DE')
        ->create([
            'mail_layout_id' => $layout->getKey(),
            'logo' => null,
        ]);

    MailLayout::withoutEvents(fn () => $layout->delete());

    $template = $template->fresh();
    $options = MailTemplateResource::layoutOptions($template);

    expect($template?->mailLayout)->not->toBeNull()
        ->and($template?->mailLayout?->trashed())->toBeTrue()
        ->and($options)->toHaveKey((int) $layout->getKey())
        ->and($options[(int) $layout->getKey()])->toContain(__('mail-template::translations.layout_trashed_suffix'));

    $data = app(MailTemplateRenderer::class)->viewData($template);

    expect($data['footer'])->toBe('<mj-text>TRASHED-FOOTER</mj-text>')
        ->and($data['logoUrl'])->toContain('mail-layouts/trashed.png');
});

it('renders without layout logo footer and colors when the layout is missing', function (): void {
    $template = MailTemplate::factory()
        ->translation([
            'mail_content' => '<mj-text>Hi</mj-text>',
            'footer' => null,
        ], 'de_DE')
        ->create([
            'logo' => null,
        ]);

    $template->unsetRelation('mailLayout');
    $template->setRelation('mailLayout', null);

    $data = app(MailTemplateRenderer::class)->viewData($template);

    expect($data['logoUrl'])->toBeNull()
        ->and($data['footer'])->toBeNull()
        ->and($data['backgroundColor'])->toBe('#ECF2F6')
        ->and($data['buttonColor'])->toBe('#005CA3')
        ->and($data['textColor'])->toBe('#000000');
});

it('resolves logo urls from media snapshots and legacy paths', function (): void {
    expect(MailMedia::resolveUrl('mail-templates/legacy.png'))
        ->toContain('mail-templates/legacy.png')
        ->and(MailMedia::resolveUrl('https://cdn.example/logo.png'))
        ->toBe('https://cdn.example/logo.png')
        ->and(MailMedia::resolveUrl([
            'id' => 999999,
            'file_name' => 'mail-layouts/from-json.png',
        ]))->toContain('mail-layouts/from-json.png');

    expect(MailMedia::isAvailable())
        ->toBe(class_exists(MediaPicker::class));
});
