<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\PersistBackend;
use Moox\MailTesting\Enums\RunStatus;
use Moox\MailTesting\Jobs\RenderMailTestingRunJob;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Pages\MailTestingPage;
use Moox\MailTesting\Support\HtmlLength;
use Moox\MailTesting\Support\MailTestingWorkerStatus;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(User::factory()->create([
        'email' => 'dev@heco.de',
    ]));

    app()->setLocale('de');
    Storage::fake((string) config('mail-testing.disk', 'local'));
    MailLayout::factory()->create(['slug' => 'invoice']);
});

it('shows grouped test settings instead of a mixed three-column form', function (): void {
    Livewire::test(MailTestingPage::class)
        ->assertSuccessful()
        ->assertSee(__('mail-testing::translations.fieldset_test'))
        ->assertSee(__('mail-testing::translations.fieldset_variables'))
        ->assertSee(__('mail-testing::translations.save_variables'))
        ->assertSee(__('mail-testing::translations.fieldset_mjml'))
        ->assertSee(__('mail-testing::translations.source_layout'))
        ->assertSee('php artisan queue:work --queue=mail-testing --tries=1 --timeout=0')
        ->assertSee('php artisan mail-testing:render')
        ->assertDontSee('keepComments')
        ->assertSee(__('mail-testing::translations.persist_storage_help'))
        ->assertSeeHtml('id="form"')
        ->assertSeeHtml('wire:submit="start"')
        ->assertSeeHtml('form="form"');
});

it('saves custom mjml from the template modal', function (): void {
    $mjml = <<<'MJML'
<mj-text>Hallo {displayName}</mj-text>
<mj-button href="https://www.heco.de">Zur Website</mj-button>
MJML;

    $layout = MailLayout::query()->where('slug', 'invoice')->first();

    Livewire::test(MailTestingPage::class)
        ->set('data.source_layout_id', $layout?->getKey())
        ->callAction('ensureTemplate', [
            'mail_content' => $mjml,
        ])
        ->assertNotified(__('mail-testing::translations.template_ready'));

    $template = MailTemplate::query()->where('slug', 'test')->first();

    expect($template)->not->toBeNull()
        ->and($template?->mailLayout?->slug)->toBe('invoice')
        ->and($template?->translations->first()?->mail_content)->toBe($mjml);
});

it('lists php and node run times in one table so the values line up', function (): void {
    MailTestingRun::query()->create([
        'status' => RunStatus::Completed,
        'engine' => Engine::Php,
        'persist_backend' => PersistBackend::Storage,
        'count' => 10,
        'processed' => 10,
        'options' => [],
        'options_fingerprint' => 'php-run',
        'compose_ms' => 19,
        'convert_ms' => 76,
        'persist_ms' => 73,
        'generation_ms' => 95,
        'total_ms' => 254,
    ]);
    MailTestingRun::query()->create([
        'status' => RunStatus::Completed,
        'engine' => Engine::Node,
        'persist_backend' => PersistBackend::Storage,
        'count' => 10,
        'processed' => 10,
        'options' => [],
        'options_fingerprint' => 'node-run',
        'compose_ms' => 12,
        'convert_ms' => 40,
        'persist_ms' => 70,
        'generation_ms' => 80,
        'total_ms' => 202,
    ]);

    Livewire::test(MailTestingPage::class)
        ->assertSuccessful()
        ->assertSee(__('mail-testing::translations.last_run_help'))
        ->assertSeeInOrder(['PHP', '19 ms', '76 ms', '73 ms', '95 ms', '254 ms'])
        ->assertSeeInOrder(['Node', '12 ms', '40 ms', '70 ms', '80 ms', '202 ms'])
        ->assertSee(MailTestingRun::query()->where('engine', Engine::Php)->first()?->htmlDirectoryPath())
        ->assertSee(MailTestingRun::query()->where('engine', Engine::Node)->first()?->htmlDirectoryPath())
        ->assertDontSeeHtml('wire:poll');
});

it('refreshes the runs every 10 seconds while one is still open', function (): void {
    MailTestingRun::query()->create([
        'status' => RunStatus::Running,
        'engine' => Engine::Php,
        'persist_backend' => PersistBackend::Storage,
        'count' => 50,
        'processed' => 10,
        'options' => [],
        'options_fingerprint' => 'running-run',
    ]);

    Livewire::test(MailTestingPage::class)
        ->assertSeeHtml('wire:poll.10s');
});

it('does not poll after a run has failed', function (): void {
    MailTestingRun::query()->create([
        'status' => RunStatus::Failed,
        'engine' => Engine::Node,
        'persist_backend' => PersistBackend::Storage,
        'count' => 50,
        'processed' => 12,
        'options' => [],
        'options_fingerprint' => 'failed-run',
        'error' => 'Konvertierung abgebrochen',
    ]);

    Livewire::test(MailTestingPage::class)
        ->assertDontSeeHtml('wire:poll');
});

it('starts the run inline when no mail-testing worker is listening', function (): void {
    config(['queue.default' => 'database']);
    Cache::flush();
    Queue::fake();

    Livewire::test(MailTestingPage::class)
        ->set('data.count', 2)
        ->call('start')
        ->assertNotified();

    Queue::assertNothingPushed();
    expect(MailTestingRun::query()->count())->toBe(1)
        ->and(MailTestingRun::query()->first()?->status)->not->toBe(RunStatus::Pending);
});

it('queues the run when the mail-testing worker heartbeat is fresh', function (): void {
    config(['queue.default' => 'database']);
    Cache::flush();
    Queue::fake();
    MailTestingWorkerStatus::rememberIfListening('mail-testing');

    Livewire::test(MailTestingPage::class)
        ->set('data.count', 2)
        ->call('start')
        ->assertSee(__('mail-testing::translations.start_queued'))
        ->assertNotified();

    Queue::assertPushed(RenderMailTestingRunJob::class);
    expect(MailTestingRun::query()->first()?->status)->toBe(RunStatus::Pending);
});

it('stores recipient mode and variable bindings on the queued run', function (): void {
    config(['queue.default' => 'database']);
    Cache::flush();
    Queue::fake();
    MailTestingWorkerStatus::rememberIfListening('mail-testing');

    Livewire::test(MailTestingPage::class)
        ->set('data.count', 1)
        ->set('data.recipient_mode', 'demo')
        ->set('data.variables', [
            ['token' => '{invoiceNumber}', 'mode' => 'demo', 'value' => 'RE-2026-001'],
        ])
        ->call('start')
        ->assertNotified();

    $run = MailTestingRun::query()->first();

    expect($run?->options['recipient_mode'])->toBe('demo')
        ->and($run?->options['layout_id'])->toBeNull()
        ->and($run?->options['variables'])->toBe([
            ['token' => 'invoiceNumber', 'mode' => 'demo', 'value' => 'RE-2026-001'],
        ]);
});

it('stores the selected layout on the queued run', function (): void {
    config(['queue.default' => 'database']);
    Cache::flush();
    Queue::fake();
    MailTestingWorkerStatus::rememberIfListening('mail-testing');

    $layout = MailLayout::query()->where('slug', 'invoice')->first();

    Livewire::test(MailTestingPage::class)
        ->set('data.source_layout_id', $layout?->getKey())
        ->set('data.count', 1)
        ->call('start')
        ->assertNotified();

    expect(MailTestingRun::query()->first()?->options['layout_id'])->toBe($layout?->getKey())
        ->and(MailTestingWorkerStatus::renderCommand([
            'count' => 1,
            'engine' => 'php',
            'persist_backend' => 'storage',
            'validation_level' => 'soft',
            'source_layout_id' => $layout?->getKey(),
        ]))->toContain('--layout=invoice');
});

it('saves variables and restores them on the next visit', function (): void {
    Livewire::test(MailTestingPage::class)
        ->set('data.recipient_mode', 'demo')
        ->set('data.variables', [
            ['token' => 'invoiceNumber', 'mode' => 'random', 'value' => 'RE-####'],
        ])
        ->call('saveVariables')
        ->assertNotified(__('mail-testing::translations.variables_saved'));

    $stored = json_decode(
        (string) Storage::disk((string) config('mail-testing.disk', 'local'))->get('mail-testing-variables.json'),
        true,
    );

    expect($stored['recipient_mode'])->toBe('demo')
        ->and($stored['variables'])->toBe([
            ['token' => 'invoiceNumber', 'mode' => 'random', 'value' => 'RE-####'],
        ]);

    $restored = Livewire::test(MailTestingPage::class)->get('data');

    expect($restored['recipient_mode'])->toBe('demo');

    $tokens = collect($restored['variables'] ?? [])
        ->map(fn (array $row): string => (string) ($row['token'] ?? ''))
        ->all();

    expect($tokens)->toContain('invoiceNumber');
});

it('deletes one engine run with its mails and html files', function (): void {
    Storage::fake((string) config('mail-testing.disk', 'local'));

    $php = MailTestingRun::query()->create([
        'status' => RunStatus::Completed,
        'engine' => Engine::Php,
        'persist_backend' => PersistBackend::Storage,
        'count' => 1,
        'processed' => 1,
        'options' => [],
        'options_fingerprint' => 'php-delete',
        'compose_ms' => 1,
        'convert_ms' => 2,
        'persist_ms' => 3,
        'generation_ms' => 3,
        'total_ms' => 6,
    ]);
    $node = MailTestingRun::query()->create([
        'status' => RunStatus::Completed,
        'engine' => Engine::Node,
        'persist_backend' => PersistBackend::Storage,
        'count' => 1,
        'processed' => 1,
        'options' => [],
        'options_fingerprint' => 'node-keep',
        'compose_ms' => 4,
        'convert_ms' => 5,
        'persist_ms' => 6,
        'generation_ms' => 9,
        'total_ms' => 15,
    ]);

    $phpPath = MailTestingRun::htmlRelativePath($php->getKey(), 1);
    $nodePath = MailTestingRun::htmlRelativePath($node->getKey(), 1);
    $disk = Storage::disk((string) config('mail-testing.disk', 'local'));
    $disk->put($phpPath, '<html>php</html>');
    $disk->put($nodePath, '<html>node</html>');

    MailTestingMessage::query()->create([
        'mail_testing_run_id' => $php->getKey(),
        'position' => 1,
        'html_hash' => str_repeat('a', 40),
        'byte_length' => 10,
        'compose_ms' => 1,
        'convert_ms' => 2,
        'persist_ms' => 3,
        'storage_path' => $phpPath,
    ]);
    MailTestingMessage::query()->create([
        'mail_testing_run_id' => $node->getKey(),
        'position' => 1,
        'html_hash' => str_repeat('b', 40),
        'byte_length' => 12,
        'compose_ms' => 4,
        'convert_ms' => 5,
        'persist_ms' => 6,
        'storage_path' => $nodePath,
    ]);

    Livewire::test(MailTestingPage::class)
        ->assertSeeHtml('wire:click="deleteRun('.$php->getKey().')"')
        ->call('deleteRun', (int) $php->getKey())
        ->assertNotified(__('mail-testing::translations.deleted_run', ['engine' => 'PHP']));

    expect(MailTestingRun::query()->whereKey($php->getKey())->exists())->toBeFalse()
        ->and(MailTestingRun::query()->whereKey($node->getKey())->exists())->toBeTrue()
        ->and(MailTestingMessage::query()->where('mail_testing_run_id', $php->getKey())->count())->toBe(0)
        ->and(MailTestingMessage::query()->where('mail_testing_run_id', $node->getKey())->count())->toBe(1)
        ->and($disk->exists($phpPath))->toBeFalse()
        ->and($disk->exists($nodePath))->toBeTrue();
});

it('shows php vs node html samples with original line breaks', function (): void {
    $php = MailTestingRun::query()->create([
        'status' => RunStatus::Completed,
        'engine' => Engine::Php,
        'persist_backend' => PersistBackend::Storage,
        'count' => 1,
        'processed' => 1,
        'options' => [],
        'options_fingerprint' => 'compare-html',
        'compose_ms' => 1,
        'convert_ms' => 2,
        'persist_ms' => 3,
        'generation_ms' => 3,
        'total_ms' => 824,
    ]);
    $node = MailTestingRun::query()->create([
        'status' => RunStatus::Completed,
        'engine' => Engine::Node,
        'persist_backend' => PersistBackend::Storage,
        'count' => 1,
        'processed' => 1,
        'options' => [],
        'options_fingerprint' => 'compare-html',
        'compose_ms' => 4,
        'convert_ms' => 5,
        'persist_ms' => 6,
        'generation_ms' => 9,
        'total_ms' => 105_000,
    ]);

    $phpHtml = "<html>\n  <body>PHP-SAMPLE</body>\n</html>";
    $nodeHtml = "<html>\n  <body>NODE-SAMPLE extra</body>\n</html>";
    $phpPath = MailTestingRun::htmlRelativePath($php->getKey(), 1);
    $nodePath = MailTestingRun::htmlRelativePath($node->getKey(), 1);
    $disk = Storage::disk((string) config('mail-testing.disk', 'local'));
    $disk->put($phpPath, $phpHtml);
    $disk->put($nodePath, $nodeHtml);

    MailTestingMessage::query()->create([
        'mail_testing_run_id' => $php->getKey(),
        'position' => 1,
        'html_hash' => str_repeat('a', 40),
        'byte_length' => strlen($phpHtml),
        'compose_ms' => 1,
        'convert_ms' => 2,
        'persist_ms' => 3,
        'storage_path' => $phpPath,
    ]);
    MailTestingMessage::query()->create([
        'mail_testing_run_id' => $node->getKey(),
        'position' => 1,
        'html_hash' => str_repeat('b', 40),
        'byte_length' => strlen($nodeHtml),
        'compose_ms' => 4,
        'convert_ms' => 5,
        'persist_ms' => 6,
        'storage_path' => $nodePath,
    ]);

    Livewire::test(MailTestingPage::class)
        ->assertSuccessful()
        ->assertSee(__('mail-testing::translations.comparison'))
        ->assertSee('PHP-SAMPLE')
        ->assertSee('NODE-SAMPLE')
        ->assertSeeHtml("&lt;html&gt;\n  &lt;body&gt;PHP-SAMPLE&lt;/body&gt;")
        ->assertDontSee('&lt;html&gt; &lt;body&gt;PHP-SAMPLE&lt;/body&gt; &lt;/html&gt;', false)
        ->assertSeeHtml('white-space: pre-wrap')
        ->assertSeeHtml('max-height: 24rem')
        ->assertSeeHtml('overflow: auto')
        ->assertSee(HtmlLength::formatBytes(strlen($phpHtml)))
        ->assertSee(HtmlLength::formatCharacters(mb_strlen($phpHtml, 'UTF-8')))
        ->assertSee(HtmlLength::formatBytes(strlen($nodeHtml)))
        ->assertSee(HtmlLength::formatCharacters(mb_strlen($nodeHtml, 'UTF-8')))
        ->assertSeeHtml('color: rgb(180 83 9);');
});
