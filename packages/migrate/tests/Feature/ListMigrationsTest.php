<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Moox\Migrate\Models\Migration;
use Moox\Migrate\Resources\MigrationResource;
use Moox\Migrate\Resources\MigrationResource\Pages\ListMigrations;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(User::factory()->create([
        'email' => 'dev@heco.de',
    ]));
});

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
    expect(array_keys(MigrationResource::getPages()))->toBe(['index'])
        ->and(MigrationResource::enableCreate())->toBeFalse()
        ->and(MigrationResource::enableEdit())->toBeFalse()
        ->and(MigrationResource::enableView())->toBeFalse()
        ->and(MigrationResource::enableDelete())->toBeFalse();

    Livewire::test(ListMigrations::class)
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('view')
        ->assertTableActionDoesNotExist('delete');
});

it('runs artisan migrate from the header action and refreshes the table', function (): void {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andReturn(0);
    Artisan::shouldReceive('output')->andReturn("INFO  Running migrations.\n");

    Livewire::test(ListMigrations::class)
        ->callAction('migrate')
        ->assertNotified(__('migrate::migrate.migrate_done'));
});

it('applies a pending migration when the header action runs', function (): void {
    $name = '2099_12_31_235959_create_migrate_ui_probe_table';
    $path = database_path('migrations/'.$name.'.php');

    file_put_contents($path, <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migrate_ui_probe', function (Blueprint $table): void {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migrate_ui_probe');
    }
};
PHP);

    try {
        expect(Schema::hasTable('migrate_ui_probe'))->toBeFalse();

        Livewire::test(ListMigrations::class)
            ->callAction('migrate')
            ->assertNotified(__('migrate::migrate.migrate_done'));

        expect(Schema::hasTable('migrate_ui_probe'))->toBeTrue()
            ->and(Migration::query()->where('migration', $name)->exists())->toBeTrue();
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});
