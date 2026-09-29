<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\PersistBackend;
use Moox\MailTesting\Enums\RunStatus;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Support\LoadMailTemplateMigrations;
use Moox\MailTesting\Support\MailTestingRunService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    LoadMailTemplateMigrations::run();
    Storage::fake((string) config('mail-testing.disk', 'local'));

    MailTemplate::factory()
        ->translation([
            'title' => 'Login',
            'mail_content' => '<mj-text>{anrede}</mj-text><mj-text>{displayName}</mj-text>',
            'footer' => null,
        ])
        ->create(['slug' => 'login-link']);
});

it('renders personalized html and records three clocks plus total wall time', function (): void {
    $this->artisan('mail-testing:render', [
        '--template' => 'login-link',
        '--count' => 2,
        '--engine' => 'php',
        '--persist' => 'storage',
        '--validation' => 'soft',
    ])->assertSuccessful();

    $run = MailTestingRun::query()->first();

    expect($run)->not->toBeNull()
        ->and($run->status)->toBe(RunStatus::Completed)
        ->and($run->count)->toBe(2)
        ->and($run->processed)->toBe(2)
        ->and($run->generation_ms)->toBe($run->compose_ms + $run->convert_ms)
        ->and($run->total_ms)->toBeGreaterThanOrEqual($run->generation_ms)
        ->and(MailTestingMessage::query()->count())->toBe(2);

    $message = MailTestingMessage::query()->orderBy('position')->first();

    expect($message?->storage_path)->toBe(MailTestingRun::htmlRelativePath((int) $run->getKey(), 1));

    $html = $message?->resolvedHtml();

    expect($html)->toBeString()
        ->and($html)->toContain('Sehr geehrte')
        ->and($html)->not->toContain('<mjml');
});

it('fails when the template option is missing', function (): void {
    $this->artisan('mail-testing:render', [
        '--count' => 1,
        '--engine' => 'php',
    ])->assertFailed();

    expect(MailTestingRun::query()->count())->toBe(0);
});

it('interpolates demo variables into the rendered html', function (): void {
    $template = MailTemplate::query()->where('slug', 'login-link')->first();
    $template?->translations()->first()?->forceFill([
        'mail_content' => '<mj-text>Rechnung {invoiceNumber} für {firstName}</mj-text>',
    ])->save();

    $options = [
        'validation_level' => 'soft',
        'minify' => false,
        'beautify' => false,
        'keep_comments' => false,
        'ignore_includes' => false,
        'recipient_mode' => 'demo',
        'template_slug' => 'login-link',
        'variables' => [
            ['token' => 'invoiceNumber', 'mode' => 'demo', 'value' => 'RE-2026-001'],
        ],
    ];

    $run = MailTestingRun::query()->create([
        'status' => RunStatus::Pending,
        'engine' => Engine::Php,
        'persist_backend' => PersistBackend::Storage,
        'count' => 1,
        'processed' => 0,
        'options' => $options,
        'options_fingerprint' => MailTestingRun::fingerprint(1, PersistBackend::Storage, $options),
    ]);

    app(MailTestingRunService::class)->execute($run);

    $html = MailTestingMessage::query()->first()?->resolvedHtml();

    expect($html)->toBeString()
        ->and($html)->toContain('RE-2026-001')
        ->and($html)->toContain('Max')
        ->and($html)->not->toContain('{invoiceNumber}');
});

it('renders the selected template without changing the original', function (): void {
    $first = MailTemplate::query()->where('slug', 'login-link')->first();
    $first?->mailLayout?->translations()->first()?->forceFill([
        'footer' => '<mj-text>FOOTER-LOGIN</mj-text>',
    ])->save();
    $first?->translations()->first()?->forceFill([
        'mail_content' => '<mj-text>INHALT-LOGIN</mj-text>',
    ])->save();

    $invoiceLayout = MailLayout::factory()
        ->translation([
            'title' => 'Rechnung',
            'footer' => '<mj-text>FOOTER-RECHNUNG</mj-text>',
        ])
        ->create(['slug' => 'rechnung-layout']);

    MailTemplate::factory()
        ->translation([
            'title' => 'Rechnung',
            'mail_content' => '<mj-text>INHALT-RECHNUNG</mj-text>',
            'footer' => null,
        ])
        ->create([
            'slug' => 'rechnung',
            'mail_layout_id' => $invoiceLayout->getKey(),
        ]);

    $storedLayoutId = $first?->mail_layout_id;
    $storedContent = $first?->translations()->first()?->mail_content;

    $render = function (string $slug): string {
        $options = [
            'validation_level' => 'soft',
            'minify' => false,
            'beautify' => false,
            'keep_comments' => false,
            'ignore_includes' => false,
            'recipient_mode' => 'demo',
            'variables' => [],
            'template_slug' => $slug,
        ];

        $run = MailTestingRun::query()->create([
            'status' => RunStatus::Pending,
            'engine' => Engine::Php,
            'persist_backend' => PersistBackend::Database,
            'count' => 1,
            'processed' => 0,
            'options' => $options,
            'options_fingerprint' => MailTestingRun::fingerprint(1, PersistBackend::Database, $options),
        ]);

        app(MailTestingRunService::class)->execute($run);

        return (string) MailTestingMessage::query()
            ->where('mail_testing_run_id', $run->getKey())
            ->first()
            ?->resolvedHtml();
    };

    expect($render('login-link'))
        ->toContain('FOOTER-LOGIN')
        ->toContain('INHALT-LOGIN')
        ->not->toContain('FOOTER-RECHNUNG')
        ->not->toContain('INHALT-RECHNUNG');

    expect($render('rechnung'))
        ->toContain('FOOTER-RECHNUNG')
        ->toContain('INHALT-RECHNUNG')
        ->not->toContain('FOOTER-LOGIN')
        ->and(MailTemplate::query()->where('slug', 'login-link')->value('mail_layout_id'))->toBe($storedLayoutId)
        ->and(MailTemplate::query()->where('slug', 'login-link')->first()?->translations()->first()?->mail_content)
        ->toBe($storedContent);
});

it('stores html in the database when asked', function (): void {
    $this->artisan('mail-testing:render', [
        '--template' => 'login-link',
        '--count' => 1,
        '--engine' => 'php',
        '--persist' => 'database',
    ])->assertSuccessful();

    $message = MailTestingMessage::query()->first();

    expect($message)->not->toBeNull()
        ->and($message->html)->toBeString()
        ->and($message->html)->not->toBe('')
        ->and($message->storage_path)->toBeNull();
});

it('registers the filament plugin on the admin panel', function (): void {
    expect(Filament::getPanel('admin')->hasPlugin('mail-testing'))->toBeTrue()
        ->and(Route::has('filament.admin.pages.mail-testing'))->toBeTrue();
});
