<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTesting\Support\EnsureTestTemplate;
use Moox\MailTesting\Support\LoadMailTemplateMigrations;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    LoadMailTemplateMigrations::run();
});

it('assigns the selected layout to the test template', function (): void {
    $source = MailLayout::factory()
        ->translation([
            'title' => 'Login',
            'footer' => '<mj-text>Footer</mj-text>',
        ])
        ->create([
            'slug' => 'login-link',
        ]);

    $template = app(EnsureTestTemplate::class)->ensure((int) $source->getKey());

    expect($template->slug)->toBe('test')
        ->and($template->mailLayout?->slug)->toBe('login-link')
        ->and($template->translate('de_DE')->mail_content)->toContain('{anrede}')
        ->and($template->translate('de_DE')->mail_content)->toContain('{displayName}')
        ->and($template->translate('de_DE')->mail_content)->toContain('MJML Rendering Test');

    $again = app(EnsureTestTemplate::class)->ensure(null, '<mj-text>Nur Inhalt</mj-text>');

    expect($again->getKey())->toBe($template->getKey())
        ->and($again->mailLayout?->slug)->toBe('login-link')
        ->and($again->translate('de_DE')->mail_content)->toBe('<mj-text>Nur Inhalt</mj-text>')
        ->and(MailTemplate::query()->where('slug', 'test')->count())->toBe(1)
        ->and(MailLayout::query()->where('slug', 'mail-testing')->count())->toBe(0);
});

it('saves custom mjml as the test template content', function (): void {
    $source = MailLayout::factory()
        ->translation([
            'title' => 'Login',
            'footer' => '<mj-text>Footer</mj-text>',
        ])
        ->create([
            'slug' => 'login-link',
        ]);

    $mjml = <<<'MJML'
<mj-text font-size="28px" font-weight="600">Hallo {displayName}</mj-text>
<mj-button href="https://www.heco.de">Zur Website</mj-button>
MJML;

    $template = app(EnsureTestTemplate::class)->ensure((int) $source->getKey(), $mjml);

    expect($template->translate('de_DE')->mail_content)->toBe($mjml)
        ->and(EnsureTestTemplate::currentOrDefaultContent())->toBe($mjml);
});
