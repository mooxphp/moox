<?php

declare(strict_types=1);

namespace Moox\Builder;

use Filament\Resources\Events\RecordSaved;
use Illuminate\Support\Facades\Event;
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\FieldTypes\Types\CheckboxListFieldType;
use Moox\Builder\FieldTypes\Types\ColorFieldType;
use Moox\Builder\FieldTypes\Types\DateFieldType;
use Moox\Builder\FieldTypes\Types\DatetimeFieldType;
use Moox\Builder\FieldTypes\Types\EmailFieldType;
use Moox\Builder\FieldTypes\Types\MultiselectFieldType;
use Moox\Builder\FieldTypes\Types\NumberFieldType;
use Moox\Builder\FieldTypes\Types\PasswordFieldType;
use Moox\Builder\FieldTypes\Types\RadioFieldType;
use Moox\Builder\FieldTypes\Types\SelectFieldType;
use Moox\Builder\FieldTypes\Types\TextareaFieldType;
use Moox\Builder\FieldTypes\Types\TextFieldType;
use Moox\Builder\FieldTypes\Types\TimeFieldType;
use Moox\Builder\FieldTypes\Types\ToggleFieldType;
use Moox\Builder\FieldTypes\Types\UrlFieldType;
use Moox\Builder\Listeners\PersistCustomFields;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldOption;
use Moox\Builder\Observers\InvalidateDefinitionCacheObserver;
use Moox\Builder\Observers\PurgeFieldValuesObserver;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Storage\ValueStoreResolver;
use Moox\Builder\Support\EntityModelDeletionRegistrar;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BuilderServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('builder')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_builder_field_groups_table',
                'create_builder_fields_table',
                'create_builder_field_options_table',
                'create_builder_field_values_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(FieldTypeRegistry::class, function (): FieldTypeRegistry {
            $registry = new FieldTypeRegistry;

            foreach ($this->defaultFieldTypes() as $type) {
                $registry->register($type);
            }

            return $registry;
        });

        $this->app->singleton(ValueStoreResolver::class);
        $this->app->singleton(EntityRegistry::class);
    }

    public function packageBooted(): void
    {
        FieldGroup::observe(InvalidateDefinitionCacheObserver::class);
        Field::observe(InvalidateDefinitionCacheObserver::class);
        Field::observe(PurgeFieldValuesObserver::class);
        FieldOption::observe(InvalidateDefinitionCacheObserver::class);

        Event::listen(RecordSaved::class, PersistCustomFields::class);

        $this->app->booted(function (): void {
            app(EntityModelDeletionRegistrar::class)->register();
        });
    }

    /**
     * @return list<FieldType>
     */
    protected function defaultFieldTypes(): array
    {
        return [
            new TextFieldType,
            new TextareaFieldType,
            new NumberFieldType,
            new EmailFieldType,
            new UrlFieldType,
            new PasswordFieldType,
            new SelectFieldType,
            new MultiselectFieldType,
            new CheckboxListFieldType,
            new RadioFieldType,
            new ToggleFieldType,
            new DateFieldType,
            new DatetimeFieldType,
            new TimeFieldType,
            new ColorFieldType,
        ];
    }
}
