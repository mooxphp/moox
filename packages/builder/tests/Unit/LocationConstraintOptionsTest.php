<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\LocationConstraintOptions;
use Moox\Builder\Tests\TestCase;
use Moox\Core\Services\TaxonomyService;
use Spatie\Permission\Traits\HasRoles;

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
        app(EntityRegistry::class),
        app(TaxonomyService::class),
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
        public function __construct(protected string $modelClass)
        {
        }

        public function modelFor(string $entity): ?string
        {
            return $entity === 'typed-item' ? $this->modelClass : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(TaxonomyService::class),
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
        public static function getTypeSelect(): Select
        {
            return Select::make('type')
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
        ) {
        }

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
        app(TaxonomyService::class),
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

    DB::table('roles')->insert([
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
        public static function getTypeSelect(): Select
        {
            return Select::make('type')
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
        ) {
        }

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
        app(TaxonomyService::class),
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
        public function __construct(protected string $modelClass)
        {
        }

        public function modelFor(string $entity): ?string
        {
            return $entity === 'typed-item' ? $this->modelClass : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(TaxonomyService::class),
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

it('memoizes record status options per model', function (): void {
    Schema::create('status_items', function (Blueprint $table): void {
        $table->id();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    DB::table('status_items')->insert([
        ['status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['status' => 'published', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $modelClass = new class extends Model
    {
        protected $table = 'status_items';
    };

    $registry = new class($modelClass::class) extends EntityRegistry
    {
        public function __construct(protected string $modelClass)
        {
        }

        public function modelFor(string $entity): ?string
        {
            return $entity === 'status-item' ? $this->modelClass : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(TaxonomyService::class),
        app(BuilderLocaleResolver::class),
    );

    expect($options->availableParamOptionsForEntities(['status-item']))
        ->toHaveKey('record_status')
        ->and($options->recordStatusOptionsForEntities(['status-item']))
        ->toHaveKeys(['draft', 'published']);
});

it('exposes draft workflow statuses from resource helpers', function (): void {
    $resourceClass = new class
    {
        public static function getEditableTranslationStatusOptions(): array
        {
            return [
                'draft' => 'Draft',
                'waiting' => 'Waiting',
                'private' => 'Private',
                'scheduled' => 'Scheduled',
                'published' => 'Published',
            ];
        }
    };

    $registry = new class($resourceClass::class) extends EntityRegistry
    {
        public function __construct(protected string $resourceClass)
        {
        }

        public function resourceFor(string $entity): ?string
        {
            return $entity === 'draft-page' ? $this->resourceClass : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(TaxonomyService::class),
        app(BuilderLocaleResolver::class),
    );

    expect($options->recordStatusOptionsForEntities(['draft-page']))->toHaveKeys([
        'draft',
        'waiting',
        'private',
        'scheduled',
        'published',
    ]);
});

it('returns initial taxonomy term options when search is empty', function (): void {
    Schema::create('taxonomy_terms', function (Blueprint $table): void {
        $table->id();
        $table->string('title')->nullable();
        $table->timestamps();
    });

    DB::table('taxonomy_terms')->insert([
        ['title' => 'News', 'created_at' => now(), 'updated_at' => now()],
        ['title' => 'Sports', 'created_at' => now(), 'updated_at' => now()],
    ]);

    config([
        'page.taxonomies' => [
            'category' => [
                'label' => 'Category',
                'model' => TaxonomyTermTestModel::class,
            ],
        ],
    ]);

    $registry = new class extends EntityRegistry
    {
        public function modelFor(string $entity): ?string
        {
            return $entity === 'page' ? PageTaxonomyTestModel::class : null;
        }
    };

    $options = new LocationConstraintOptions(
        $registry,
        app(TaxonomyService::class),
        app(BuilderLocaleResolver::class),
    );

    expect($options->searchTermOptionsForTaxonomy('category', ['page'], ''))
        ->toMatchArray([
            '1' => 'News',
            '2' => 'Sports',
        ])
        ->and($options->searchTermOptionsForTaxonomy('category', ['page'], 'new'))
        ->toBe(['1' => 'News']);
});

class PageTaxonomyTestModel extends Model
{
    public static function getResourceName(): string
    {
        return 'page';
    }
}

class TaxonomyTermTestModel extends Model
{
    protected $table = 'taxonomy_terms';
}

class NoRolesUser extends Model
{
}

class RolesUser extends Model
{
    use HasRoles;
}
