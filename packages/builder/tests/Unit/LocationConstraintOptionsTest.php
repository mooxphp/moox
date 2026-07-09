<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\LocationConstraintOptions;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('casts taxonomy term ids from select values to integers when persisting constraints', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    $rules = $persistence->mergeEntityRulesWithConstraints(
        ['draft'],
        [
            [
                'param' => 'taxonomy',
                'taxonomy' => 'category',
                'operator' => '==',
                'value' => '12',
            ],
        ],
    );

    expect($rules[0][1])->toMatchArray([
        'param' => 'taxonomy:category',
        'operator' => '==',
        'value' => 12,
    ]);
});

it('casts multi select taxonomy values to integer arrays', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    $rules = $persistence->mergeEntityRulesWithConstraints(
        ['draft'],
        [
            [
                'param' => 'taxonomy',
                'taxonomy' => 'tag',
                'operator' => 'in',
                'value' => ['12', '34'],
            ],
        ],
    );

    expect($rules[0][1]['value'])->toBe([12, 34]);
});

it('resolves translatable taxonomy term labels using locale fallback chain', function (): void {
    $term = new class extends Model
    {
        public function translate(string $locale, bool $withFallback = true): ?object
        {
            return $this->getRelation('translations')->firstWhere('locale', $locale);
        }
    };

    $term->setRawAttributes(['id' => 1]);
    $term->setRelation('translations', collect([
        (object) ['locale' => 'en_US', 'title' => 'Plops'],
    ]));

    $options = app(LocationConstraintOptions::class);
    $method = new ReflectionMethod(LocationConstraintOptions::class, 'termLabel');
    $method->setAccessible(true);

    expect($method->invoke($options, $term))->toBe('Plops');
});

it('falls back to any available translation when preferred locales are missing', function (): void {
    $term = new class extends Model
    {
        public function translate(string $locale, bool $withFallback = true): ?object
        {
            return $this->getRelation('translations')->firstWhere('locale', $locale);
        }
    };

    $term->setRawAttributes(['id' => 2]);
    $term->setRelation('translations', collect([
        (object) ['locale' => 'de_DE', 'title' => 'Kategorie'],
    ]));

    $resolver = app(BuilderLocaleResolver::class);
    $options = new LocationConstraintOptions(
        app(\Moox\Builder\Registry\EntityRegistry::class),
        app(\Moox\Core\Services\TaxonomyService::class),
        $resolver,
    );

    $method = new ReflectionMethod(LocationConstraintOptions::class, 'termLabel');
    $method->setAccessible(true);

    $label = $resolver->using('ja_JP', fn (): string => $method->invoke($options, $term));

    expect($label)->toBe('Kategorie');
});

it('collects record type options from selected entities', function (): void {
    Schema::create('typed_items', function (Blueprint $table): void {
        $table->id();
        $table->string('type')->nullable();
        $table->timestamps();
    });

    $modelClass = new class extends Model
    {
        protected $table = 'typed_items';

        protected $guarded = [];
    };

    $modelClass::query()->create(['type' => 'page']);
    $modelClass::query()->create(['type' => 'article']);
    $modelClass::query()->create(['type' => 'page']);

    $registry = new class($modelClass::class) extends EntityRegistry
    {
        public function __construct(protected string $modelClass) {}

        public function modelFor(string $entity): ?string
        {
            return $entity === 'typed-item' ? $this->modelClass : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(\Moox\Core\Services\TaxonomyService::class),
        app(BuilderLocaleResolver::class),
    );

    expect($options->recordTypeOptionsForEntities(['typed-item']))->toBe([
        'article' => 'Article',
        'page' => 'Page',
    ]);
});

it('falls back to resource type select options when no records exist yet', function (): void {
    $modelClass = new class extends Model
    {
        protected $table = 'resource_only_items';
    };

    $resourceClass = new class
    {
        public static function getTypeSelect(): \Filament\Forms\Components\Select
        {
            return \Filament\Forms\Components\Select::make('type')
                ->options([
                    'Post' => 'Post',
                    'Page' => 'Page',
                ]);
        }
    };

    $registry = new class($modelClass::class, $resourceClass::class) extends EntityRegistry
    {
        public function __construct(
            protected string $modelClass,
            protected string $resourceClass,
        ) {}

        public function modelFor(string $entity): ?string
        {
            return $entity === 'resource-only-item' ? $this->modelClass : null;
        }

        public function resourceFor(string $entity): ?string
        {
            return $entity === 'resource-only-item' ? $this->resourceClass : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(\Moox\Core\Services\TaxonomyService::class),
        app(BuilderLocaleResolver::class),
    );

    expect($options->recordTypeOptionsForEntities(['resource-only-item']))->toBe([
        'Page' => 'Page',
        'Post' => 'Post',
    ]);
});

it('hides user role param when auth user model has no roles support', function (): void {
    config()->set('permission.table_names.roles', 'roles');
    config()->set('auth.providers.users.model', NoRolesUser::class);

    $options = app(LocationConstraintOptions::class);

    expect($options->supportsUserRoles())->toBeFalse()
        ->and($options->availableParamOptions())->toHaveKey('user_role')
        ->and($options->roleOptions())->toBe([])
        ->and($options->userRoleUnavailableReason())->toBe(__('builder::builder.field_group.location_value_role_unavailable_permissions'));
});

it('shows user role param only when roles exist and user model has hasroles', function (): void {
    config()->set('permission.table_names.roles', 'roles');
    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });

    config()->set('auth.providers.users.model', RolesUser::class);

    \Illuminate\Support\Facades\DB::table('roles')->insert([
        'name' => 'editor',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $options = app(LocationConstraintOptions::class);

    expect($options->supportsUserRoles())->toBeTrue()
        ->and($options->availableParamOptions())->toHaveKey('user_role')
        ->and($options->roleOptions())->toBe(['editor' => 'editor'])
        ->and($options->userRoleUnavailableReason())->toBeNull();
});

it('explains when no roles exist yet', function (): void {
    config()->set('permission.table_names.roles', 'roles');
    config()->set('auth.providers.users.model', RolesUser::class);

    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });

    $options = app(LocationConstraintOptions::class);

    expect($options->supportsUserRoles())->toBeFalse()
        ->and($options->userRoleUnavailableReason())->toBe(__('builder::builder.field_group.location_value_role_unavailable_empty'));
});

it('returns entity-aware condition params', function (): void {
    $modelClass = new class extends Model
    {
        protected $table = 'resource_only_items';
    };

    $resourceClass = new class
    {
        public static function getTypeSelect(): \Filament\Forms\Components\Select
        {
            return \Filament\Forms\Components\Select::make('type')
                ->options([
                    'Post' => 'Post',
                ]);
        }
    };

    $registry = new class($modelClass::class, $resourceClass::class) extends EntityRegistry
    {
        public function __construct(
            protected string $modelClass,
            protected string $resourceClass,
        ) {}

        public function modelFor(string $entity): ?string
        {
            return $entity === 'resource-only-item' ? $this->modelClass : null;
        }

        public function resourceFor(string $entity): ?string
        {
            return $entity === 'resource-only-item' ? $this->resourceClass : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(\Moox\Core\Services\TaxonomyService::class),
        app(BuilderLocaleResolver::class),
    );

    expect($options->availableParamOptionsForEntities(['resource-only-item']))
        ->toHaveKey('record_type')
        ->toHaveKey('user_role')
        ->not->toHaveKey('taxonomy');
});

it('memoizes record type options per model', function (): void {
    Schema::create('typed_items', function (Blueprint $table): void {
        $table->id();
        $table->string('type')->nullable();
        $table->timestamps();
    });

    DB::table('typed_items')->insert([
        ['type' => 'post', 'created_at' => now(), 'updated_at' => now()],
        ['type' => 'page', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $modelClass = new class extends Model
    {
        protected $table = 'typed_items';
    };

    $registry = new class($modelClass::class) extends EntityRegistry
    {
        public function __construct(protected string $modelClass) {}

        public function modelFor(string $entity): ?string
        {
            return $entity === 'typed-item' ? $this->modelClass : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(\Moox\Core\Services\TaxonomyService::class),
        app(BuilderLocaleResolver::class),
    );

    DB::flushQueryLog();
    DB::enableQueryLog();

    $first = $options->recordTypeOptionsForEntities(['typed-item']);
    $queriesAfterFirst = count(DB::getQueryLog());

    $second = $options->recordTypeOptionsForEntities(['typed-item']);

    expect($first)->toHaveKeys(['post', 'page'])
        ->and($second)->toBe($first)
        ->and(count(DB::getQueryLog()))->toBe($queriesAfterFirst);
});

class NoRolesUser extends Model {}

class RolesUser extends Model
{
    use HasRoles;
}
