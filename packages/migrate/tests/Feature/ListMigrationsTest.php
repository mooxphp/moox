<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Moox\Migrate\Jobs\RunMigrationsJob;
use Moox\Migrate\Models\Migration;
use Moox\Migrate\Resources\MigrationResource;
use Moox\Migrate\Resources\MigrationResource\Pages\ListMigrations;
use Moox\Migrate\Support\MigrateRunStatus;
use Moox\Migrate\Support\MigrationInventory;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    MigrateRunStatus::clear();

    $this->actingAs(User::factory()->create([
        'email' => 'dev@heco.de',
    ]));
});

function migrateUiTempMigration(string $name, string $upBody = ''): string
{
    $path = database_path('migrations/'.$name.'.php');

    $up = $upBody !== '' ? $upBody : <<<'PHP'
        Schema::create('migrate_ui_probe', function (Blueprint $table): void {
            $table->id();
        });
PHP;

    file_put_contents($path, <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
{$up}
    }

    public function down(): void
    {
        Schema::dropIfExists('migrate_ui_probe');
    }
};
PHP);

    return $path;
}

it('renders the migrations list', function (): void {
    Livewire::test(ListMigrations::class)
        ->assertSuccessful()
        ->assertCanRenderTableColumn('migration')
        ->assertCanRenderTableColumn('batch');
});

it('lists and searches migrations by name', function (): void {
    $alpha = Migration::query()->create([
        'migration' => '2099_01_01_000000_migrate_ui_alpha',
        'batch' => 91001,
    ]);
    Migration::query()->create([
        'migration' => '2099_01_02_000000_migrate_ui_beta',
        'batch' => 91002,
    ]);

    Livewire::test(ListMigrations::class)
        ->loadTable()
        ->searchTable('migrate_ui_alpha')
        ->assertCanSeeTableRecords([$alpha])
        ->assertCountTableRecords(1);
});

it('filters migrations by batch', function (): void {
    $alpha = Migration::query()->create([
        'migration' => '2099_01_01_000000_migrate_ui_alpha',
        'batch' => 91001,
    ]);
    Migration::query()->create([
        'migration' => '2099_01_02_000000_migrate_ui_beta',
        'batch' => 91002,
    ]);

    Livewire::test(ListMigrations::class)
        ->loadTable()
        ->filterTable('batch', '91001')
        ->assertCanSeeTableRecords([$alpha])
        ->assertCountTableRecords(1);
});

it('does not expose create edit view or delete', function (): void {
    $record = Migration::query()->first()
        ?? Migration::query()->create([
            'migration' => '2099_01_01_000000_migrate_ui_auth',
            'batch' => 91000,
        ]);

    expect(array_keys(MigrationResource::getPages()))->toBe(['index'])
        ->and(MigrationResource::enableCreate())->toBeFalse()
        ->and(MigrationResource::enableEdit())->toBeFalse()
        ->and(MigrationResource::enableView())->toBeFalse()
        ->and(MigrationResource::enableDelete())->toBeFalse()
        ->and(MigrationResource::canCreate())->toBeFalse()
        ->and(MigrationResource::canEdit($record))->toBeFalse()
        ->and(MigrationResource::canView($record))->toBeFalse()
        ->and(MigrationResource::canDelete($record))->toBeFalse()
        ->and(MigrationResource::canDeleteAny())->toBeFalse();

    Livewire::test(ListMigrations::class)
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('view')
        ->assertTableActionDoesNotExist('delete');
});

it('requires confirmation before migrating', function (): void {
    Livewire::test(ListMigrations::class)
        ->assertActionExists('migrate', fn (Action $action): bool => $action->isConfirmationRequired());
});

it('disables migrate when nothing is pending', function (): void {
    Livewire::test(ListMigrations::class)
        ->assertActionDisabled('migrate');
});

it('dispatches a migrate job from the header action', function (): void {
    Queue::fake();

    $path = migrateUiTempMigration('2099_01_01_000000_migrate_ui_queue_dispatch', '        // pending only');

    try {
        Livewire::test(ListMigrations::class)
            ->callAction('migrate')
            ->assertNotified(__('migrate::migrate.migrate_queued'));

        Queue::assertPushed(RunMigrationsJob::class);
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('shows pending migrations on the page, in the modal, and as a badge', function (): void {
    $name = '2099_01_01_000000_migrate_ui_pending_visible';
    $before = (int) MigrationResource::getNavigationBadge();
    $path = migrateUiTempMigration($name, '        // pending only');

    try {
        $pendingNames = array_map(
            fn ($item): string => $item->name,
            app(MigrationInventory::class)->pending(),
        );

        expect($pendingNames)->toContain($name)
            ->and((int) MigrationResource::getNavigationBadge())->toBe($before + 1)
            ->and(MigrationResource::getNavigationBadgeColor())->toBe('warning');

        Livewire::test(ListMigrations::class)
            ->assertSee($name)
            ->mountAction('migrate')
            ->assertMountedActionModalSee($name);
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('labels host and package origins', function (): void {
    $inventory = app(MigrationInventory::class);

    expect($inventory->origin(database_path('migrations/2099_01_01_000000_example.php')))
        ->toBe(__('migrate::migrate.origin_app'))
        ->and($inventory->origin(base_path('packages/heco/database/migrations/2099_01_01_000000_example.php')))
        ->toBe('heco')
        ->and($inventory->origin(base_path('vendor/moox/core/database/migrations/2099_01_01_000000_example.php')))
        ->toBe('moox/core');
});

it('runs artisan migrate from the header action and refreshes the table', function (): void {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andReturn(0);
    Artisan::shouldReceive('output')->andReturn("INFO  Running migrations.\n");

    $path = migrateUiTempMigration('2099_01_01_000000_migrate_ui_sync_notify', '        // pending only');

    try {
        Livewire::test(ListMigrations::class)
            ->callAction('migrate')
            ->assertNotified(__('migrate::migrate.migrate_done'));
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('applies a pending migration when the header action runs', function (): void {
    $name = '2099_12_31_235959_create_migrate_ui_probe_table';
    $path = migrateUiTempMigration($name);

    try {
        expect(Schema::hasTable('migrate_ui_probe'))->toBeFalse();

        Livewire::test(ListMigrations::class)
            ->callAction('migrate')
            ->assertNotified(__('migrate::migrate.migrate_done'));

        expect(Schema::hasTable('migrate_ui_probe'))->toBeTrue()
            ->and(Migration::query()->where('migration', $name)->exists())->toBeTrue()
            ->and(array_map(
                fn ($item): string => $item->name,
                app(MigrationInventory::class)->pending(),
            ))->not->toContain($name);
    } finally {
        Schema::dropIfExists('migrate_ui_probe');

        if (is_file($path)) {
            unlink($path);
        }
    }
});
