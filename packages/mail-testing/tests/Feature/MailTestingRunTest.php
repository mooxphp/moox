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
use Moox\MailTesting\Support\EnsureTestTemplate;
use Moox\MailTesting\Support\LoadMailTemplateMigrations;
use Moox\MailTesting\Support\MailTestingRunService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    LoadMailTemplateMigrations::run();
    Storage::fake((string) config('mail-testing.disk', 'local'));

    $source = MailLayout::factory()->create(['slug' => 'login-link']);
    app(EnsureTestTemplate::class)->ensure((int) $source->getKey());
});

it('renders personalized html and records three clocks plus total wall time', function (): void {
    $this->artisan('mail-testing:render', [
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

it('interpolates demo variables into the rendered html', function (): void {
    $layout = MailLayout::query()->where('slug', 'login-link')->first();

    expect($layout)->not->toBeNull();

    app(EnsureTestTemplate::class)->ensure(
        (int) $layout->getKey(),
        '<mj-text>Rechnung {invoiceNumber} für {firstName}</mj-text>',
    );

    $options = [
        'validation_level' => 'soft',
        'minify' => false,
        'beautify' => false,
        'keep_comments' => false,
        'ignore_includes' => false,
        'recipient_mode' => 'demo',
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

it('uses the template layout unless the run selects another one', function (): void {
    $templateLayout = MailLayout::query()->where('slug', 'login-link')->first();
    $templateFooter = $templateLayout?->translations()->first();
    $templateFooter?->forceFill([
        'footer' => '<mj-text>FOOTER-TEMPLATE</mj-text>',
    ])->save();

    $selected = MailLayout::factory()
        ->translation([
            'title' => 'Rechnung',
            'footer' => '<mj-text>FOOTER-AUSWAHL</mj-text>',
        ])
        ->create(['slug' => 'rechnung']);

    app(EnsureTestTemplate::class)->ensure((int) $templateLayout?->getKey(), '<mj-text>Inhalt</mj-text>');

    $render = function (?int $layoutId): string {
        $options = [
            'validation_level' => 'soft',
            'minify' => false,
            'beautify' => false,
            'keep_comments' => false,
            'ignore_includes' => false,
            'recipient_mode' => 'demo',
            'variables' => [],
            'layout_id' => $layoutId,
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

    $storedLayoutId = MailTemplate::query()->where('slug', 'test')->value('mail_layout_id');

    expect($render(null))->toContain('FOOTER-TEMPLATE')->not->toContain('FOOTER-AUSWAHL');

    expect($render((int) $selected->getKey()))->toContain('FOOTER-AUSWAHL')->not->toContain('FOOTER-TEMPLATE')
        ->and(MailTemplate::query()->where('slug', 'test')->value('mail_layout_id'))->toBe($storedLayoutId);
});

it('stores html in the database when asked', function (): void {
    $this->artisan('mail-testing:render', [
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
