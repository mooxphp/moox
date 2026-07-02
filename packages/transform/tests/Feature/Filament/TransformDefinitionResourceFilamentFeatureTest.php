<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages\CreateTransformDefinition;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages\EditTransformDefinition;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages\ListTransformDefinitions;
use Moox\Transform\Jobs\RunTransformRecordJob;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it creates a transform definition via filament create page', function (): void {
    createTestTables();
    $this->actingAs(createFilamentTestUser());

    $component = Livewire::test(CreateTransformDefinition::class)
        ->fillForm([
            'name' => 'Filament Create Definition',
            'destination_model' => User::class,
            'source_references' => [
                [
                    'source_type' => 'db_table',
                    'connection' => (string) config('database.default'),
                    'table' => 'transform_dummy_models',
                    'key_column' => 'id',
                    'row_key' => '1',
                    'columns' => ['title'],
                ],
            ],
        ]);

    $component
        ->fillForm([
            'field_map' => [
                [
                    'destination_field' => 'name',
                    'source_path' => 'title',
                ],
            ],
            'validation_rules' => [],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TransformDefinition::query()->where('name', 'Filament Create Definition')->exists())->toBeTrue();
});

test('it validates required fields on filament create page', function (): void {
    createTestTables();
    $this->actingAs(createFilamentTestUser());

    Livewire::test(CreateTransformDefinition::class)
        ->fillForm([
            'name' => '',
            'destination_model' => null,
            'field_map' => [],
            'source_references' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name',
            'destination_model',
            'field_map',
            'source_references',
        ]);
});

test('it rejects unknown destination model in filament form validation', function (): void {
    createTestTables();
    $this->actingAs(createFilamentTestUser());

    $component = Livewire::test(CreateTransformDefinition::class)
        ->fillForm([
            'name' => 'Invalid Destination Definition',
            'destination_model' => 'App\\Models\\DefinitelyMissingModel',
            'source_references' => [
                [
                    'source_type' => 'db_table',
                    'connection' => (string) config('database.default'),
                    'table' => 'transform_dummy_models',
                    'key_column' => 'id',
                    'row_key' => '1',
                ],
            ],
        ]);

    $component
        ->fillForm([
            'field_map' => [
                [
                    'destination_field' => 'name',
                    'source_path' => 'title',
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'destination_model',
        ]);
});

test('it validates db_table source reference fields in filament form', function (): void {
    createTestTables();
    $this->actingAs(createFilamentTestUser());

    $component = Livewire::test(CreateTransformDefinition::class)
        ->fillForm([
            'name' => 'Invalid DB Source Definition',
            'destination_model' => User::class,
            'source_references' => [
                [
                    'source_type' => 'db_table',
                    'connection' => (string) config('database.default'),
                    'table' => 'otherdb.transform_dummy_models',
                    'key_column' => '',
                    'row_key' => '',
                ],
            ],
        ]);

    $component
        ->fillForm([
            'field_map' => [
                [
                    'destination_field' => 'name',
                    'source_path' => 'title',
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'source_references.0.key_column',
            'source_references.0.table',
        ]);
});

test('it rejects invalid field map shape from filament create form', function (): void {
    createTestTables();
    $this->actingAs(createFilamentTestUser());

    Livewire::test(CreateTransformDefinition::class)
        ->fillForm([
            'name' => 'Invalid Field Map Definition',
            'destination_model' => User::class,
            'field_map' => [],
            'source_references' => [
                [
                    'source_type' => 'db_table',
                    'connection' => (string) config('database.default'),
                    'table' => 'transform_dummy_models',
                    'key_column' => 'id',
                    'row_key' => '1',
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'field_map',
        ]);
});

test('it persists validation_rules and field_map from filament create form', function (): void {
    createTestTables();
    $this->actingAs(createFilamentTestUser());

    $component = Livewire::test(CreateTransformDefinition::class)
        ->fillForm([
            'name' => 'Rule Persistence Definition',
            'destination_model' => User::class,
            'source_references' => [
                [
                    'source_type' => 'db_table',
                    'connection' => (string) config('database.default'),
                    'table' => 'transform_dummy_models',
                    'key_column' => 'id',
                    'row_key' => '1',
                ],
            ],
        ]);

    $component
        ->fillForm([
            'field_map' => [
                [
                    'destination_field' => 'name',
                    'source_path' => 'title',
                ],
                [
                    'destination_field' => 'email',
                    'source_path' => 'price_label',
                ],
            ],
            'validation_rules' => [
                [
                    'validation_field' => 'email',
                    'validation_rule' => 'required|email',
                ],
                [
                    'validation_field' => 'name',
                    'validation_rule' => 'required|string|min:2',
                ],
            ],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $definition = TransformDefinition::query()->where('name', 'Rule Persistence Definition')->first();
    expect($definition)->not()->toBeNull();
    expect($definition?->field_map)->toBe([
        'name' => 'title',
        'email' => 'price_label',
    ]);
    expect($definition?->validation_rules)->toBe([
        'email' => ['required', 'email'],
        'name' => ['required', 'string', 'min:2'],
    ]);
});

test('it updates and deletes a transform definition via filament edit page', function (): void {
    createTestTables();
    $this->actingAs(createFilamentTestUser());

    $definition = TransformDefinition::query()->create([
        'name' => 'Filament Editable Definition',
        'destination_model' => User::class,
        'destination_match' => [
            'name' => 'title',
        ],
        'field_map' => [
            'name' => 'title',
        ],
        'validation_rules' => [],
        'is_active' => true,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'connection' => (string) config('database.default'),
                'table' => 'transform_dummy_models',
                'key_column' => 'id',
                'row_key' => 1,
                'columns' => ['title'],
            ],
        ],
    ]);

    Livewire::test(EditTransformDefinition::class, ['record' => $definition->getRouteKey()])
        ->fillForm([
            'name' => 'Filament Updated Definition',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($definition->fresh()?->name)->toBe('Filament Updated Definition');

    $definition->delete();

    expect($definition->fresh()?->deleted_at)->not()->toBeNull();
});

test('it dispatches run table action and creates a transform record', function (): void {
    createTestTables();
    $this->actingAs(createFilamentTestUser());
    Queue::fake();

    $definition = createDefinition([
        'name' => 'Filament Run Definition',
        'destination_model' => User::class,
        'source_references' => [
            [
                'source_type' => 'db_table',
                'connection' => (string) config('database.default'),
                'table' => 'transform_dummy_models',
                'key_column' => 'id',
                'row_key' => 1,
                'columns' => ['title'],
            ],
        ],
    ]);

    Livewire::test(ListTransformDefinitions::class)
        ->callTableAction('run', $definition);

    expect(TransformRecord::query()->where('transform_definition_id', $definition->id)->exists())->toBeTrue();
    Queue::assertPushed(RunTransformRecordJob::class);
});
