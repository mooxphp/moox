<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\PersistBackend;
use Moox\MailTesting\Enums\RunStatus;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Resources\MailTestingMessageResource;
use Moox\MailTesting\Resources\MailTestingMessageResource\Pages\ListMailTestingMessages;
use Moox\MailTesting\Resources\MailTestingMessageResource\Pages\ViewMailTestingMessage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(User::factory()->create([
        'email' => 'dev@heco.de',
    ]));

    app()->setLocale('de');
    Storage::fake((string) config('mail-testing.disk', 'local'));
});

it('deletes a stored html file when a message is deleted', function (): void {
    $message = makeStoredMailTestingMessage();
    $path = (string) $message->storage_path;
    $disk = Storage::disk((string) config('mail-testing.disk', 'local'));

    expect($disk->exists($path))->toBeTrue();

    $message->delete();

    expect(MailTestingMessage::query()->count())->toBe(0)
        ->and($disk->exists($path))->toBeFalse();
});

it('purges runs messages and html files', function (): void {
    $message = makeStoredMailTestingMessage();
    $path = (string) $message->storage_path;
    $disk = Storage::disk((string) config('mail-testing.disk', 'local'));

    MailTestingRun::purgeAll();

    expect(MailTestingMessage::query()->count())->toBe(0)
        ->and(MailTestingRun::query()->count())->toBe(0)
        ->and($disk->exists($path))->toBeFalse();
});

it('deletes a table row and its html file from the resource', function (): void {
    $message = makeStoredMailTestingMessage();
    $path = (string) $message->storage_path;

    Livewire::test(ListMailTestingMessages::class)
        ->assertActionExists('deleteAll')
        ->callAction(TestAction::make(DeleteAction::class)->table($message));

    expect(MailTestingMessage::query()->count())->toBe(0)
        ->and(Storage::disk((string) config('mail-testing.disk', 'local'))->exists($path))->toBeFalse();
});

it('bulk deletes selected messages', function (): void {
    $first = makeStoredMailTestingMessage(position: 1);
    $second = makeStoredMailTestingMessage(run: $first->run, position: 2);

    Livewire::test(ListMailTestingMessages::class)
        ->selectTableRecords([$first->getKey(), $second->getKey()])
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

    expect(MailTestingMessage::query()->count())->toBe(0);
});

it('wipes all test data from the list header', function (): void {
    makeStoredMailTestingMessage();

    Livewire::test(ListMailTestingMessages::class)
        ->callAction('deleteAll')
        ->assertNotified(__('mail-testing::translations.deleted_all'));

    expect(MailTestingMessage::query()->count())->toBe(0)
        ->and(MailTestingRun::query()->count())->toBe(0);
});

it('deletes a message from the view page', function (): void {
    $message = makeStoredMailTestingMessage();

    Livewire::test(ViewMailTestingMessage::class, [
        'record' => $message->getRouteKey(),
    ])
        ->callAction(DeleteAction::class);

    expect(MailTestingMessage::query()->find($message->getKey()))->toBeNull();
});

it('renders the list page with a preview action', function (): void {
    makeStoredMailTestingMessage();

    Livewire::test(ListMailTestingMessages::class)
        ->assertSuccessful()
        ->assertSee(__('mail-testing::translations.preview'))
        ->assertSee(__('mail-testing::translations.engine'))
        ->assertSee('1 ms')
        ->assertSee('17 B')
        ->assertSee(__('mail-testing::translations.compose_ms_help'))
        ->assertSee(__('mail-testing::translations.convert_ms_help'))
        ->assertSee(__('mail-testing::translations.persist_ms_help'));
});

it('shows the engine and filters the table by it', function (): void {
    $php = makeStoredMailTestingMessage(engine: Engine::Php);
    $node = makeStoredMailTestingMessage(position: 2, engine: Engine::Node);

    Livewire::test(ListMailTestingMessages::class)
        ->assertSuccessful()
        ->assertSee('PHP')
        ->assertSee('Node')
        ->assertCanSeeTableRecords([$php, $node])
        ->filterTable('engine', Engine::Php->value)
        ->assertCanSeeTableRecords([$php])
        ->assertCanNotSeeTableRecords([$node]);
});

it('embeds the html preview iframe on the view page', function (): void {
    $message = makeStoredMailTestingMessage();

    Livewire::test(ViewMailTestingMessage::class, [
        'record' => $message->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSeeHtml('<iframe')
        ->assertSee('PHP')
        ->assertSee(MailTestingMessageResource::previewUrl($message), false)
        ->assertSee(__('mail-testing::translations.fieldset_mail'))
        ->assertSee(__('mail-testing::translations.fieldset_timings'))
        ->assertSee('2 ms')
        ->assertSee(__('mail-testing::translations.compose_ms_help'))
        ->assertSee('17 Bytes')
        ->assertSee('17 Zeichen')
        ->assertDontSeeHtml('lg:grid-cols-2');
});

it('shows php and node previews side by side', function (): void {
    $php = makeStoredMailTestingMessage(html: '<html>PHP-MAIL</html>', engine: Engine::Php);
    $node = makeStoredMailTestingMessage(
        position: $php->position,
        html: '<html>NODE-MAIL</html>',
        engine: Engine::Node,
    );

    Livewire::test(ViewMailTestingMessage::class, [
        'record' => $php->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSee(__('mail-testing::translations.comparison'))
        ->assertSeeHtml('lg:grid-cols-2')
        ->assertSeeHtml('title="PHP"')
        ->assertSeeHtml('title="Node"')
        ->assertSee('21 Bytes')
        ->assertSee('21 Zeichen')
        ->assertSee('22 Bytes')
        ->assertSee('22 Zeichen')
        ->assertSeeHtml('fi-color-warning')
        ->assertSee(MailTestingMessageResource::previewUrl($php), false)
        ->assertSee(MailTestingMessageResource::previewUrl($node), false);
});

it('opens an authenticated html preview of a stored test mail', function (): void {
    $message = makeStoredMailTestingMessage();

    $this->get(filament()->getDefaultPanel()->route('mail-testing-messages.preview', [
        'mailTestingMessage' => $message,
    ]))
        ->assertOk()
        ->assertSee('<html>test</html>', false);
});

it('uses the current request host for mail assets in the html preview', function (): void {
    $appUrl = rtrim((string) config('app.url'), '/');

    if ($appUrl === 'https://web.test') {
        test()->markTestSkipped('APP_URL is already the preview host.');
    }

    $html = '<html><body><img src="'.$appUrl.'/mail-templates/logo.png">PREVIEW-MAIL</body></html>';
    $message = makeStoredMailTestingMessage(html: $html);

    $path = parse_url(filament()->getDefaultPanel()->route('mail-testing-messages.preview', [
        'mailTestingMessage' => $message,
    ]), PHP_URL_PATH);

    $this->get('https://web.test'.$path)
        ->assertOk()
        ->assertSee('https://web.test/mail-templates/logo.png', false)
        ->assertSee('PREVIEW-MAIL', false);
});

it('redirects guests away from the test mail preview', function (): void {
    $message = makeStoredMailTestingMessage();

    auth()->logout();

    $this->get(filament()->getDefaultPanel()->route('mail-testing-messages.preview', [
        'mailTestingMessage' => $message,
    ]))->assertRedirect();
});

function makeStoredMailTestingMessage(
    ?MailTestingRun $run = null,
    int $position = 1,
    string $html = '<html>test</html>',
    Engine $engine = Engine::Php,
): MailTestingMessage
{
    $run ??= MailTestingRun::query()->create([
        'status' => RunStatus::Completed,
        'engine' => $engine,
        'persist_backend' => PersistBackend::Storage,
        'count' => 2,
        'processed' => 2,
        'options' => [],
        'options_fingerprint' => 'resource-delete',
        'compose_ms' => 1,
        'convert_ms' => 2,
        'persist_ms' => 3,
        'generation_ms' => 3,
        'total_ms' => 6,
    ]);

    $path = MailTestingRun::htmlRelativePath($run->getKey(), $position);
    Storage::disk((string) config('mail-testing.disk', 'local'))->put($path, $html);

    return MailTestingMessage::query()->create([
        'mail_testing_run_id' => $run->getKey(),
        'position' => $position,
        'html_hash' => str_repeat('a', 40),
        'byte_length' => strlen($html),
        'compose_ms' => 1,
        'convert_ms' => 2,
        'persist_ms' => 3,
        'storage_path' => $path,
    ]);
}
