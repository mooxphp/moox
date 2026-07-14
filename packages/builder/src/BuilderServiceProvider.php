<?php

declare(strict_types=1);

namespace Moox\Builder;

use Filament\Resources\Events\RecordSaved;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\FieldTypes\Types\ButtonGroupFieldType;
use Moox\Builder\FieldTypes\Types\CheckboxListFieldType;
use Moox\Builder\FieldTypes\Types\ColorFieldType;
use Moox\Builder\FieldTypes\Types\DateFieldType;
use Moox\Builder\FieldTypes\Types\DatetimeFieldType;
use Moox\Builder\FieldTypes\Types\EmailFieldType;
use Moox\Builder\FieldTypes\Types\FlexibleContentFieldType;
use Moox\Builder\FieldTypes\Types\FlexibleLayoutFieldType;
use Moox\Builder\FieldTypes\Types\GroupFieldType;
use Moox\Builder\FieldTypes\Types\LinkFieldType;
use Moox\Builder\FieldTypes\Types\MessageFieldType;
use Moox\Builder\FieldTypes\Types\MultiselectFieldType;
use Moox\Builder\FieldTypes\Types\NumberFieldType;
use Moox\Builder\FieldTypes\Types\OembedFieldType;
use Moox\Builder\FieldTypes\Types\PasswordFieldType;
use Moox\Builder\FieldTypes\Types\RadioFieldType;
use Moox\Builder\FieldTypes\Types\RangeFieldType;
use Moox\Builder\FieldTypes\Types\RelationFieldType;
use Moox\Builder\FieldTypes\Types\RepeaterFieldType;
use Moox\Builder\FieldTypes\Types\RichTextFieldType;
use Moox\Builder\FieldTypes\Types\SectionFieldType;
use Moox\Builder\FieldTypes\Types\SelectFieldType;
use Moox\Builder\FieldTypes\Types\TabFieldType;
use Moox\Builder\FieldTypes\Types\TextareaFieldType;
use Moox\Builder\FieldTypes\Types\TextFieldType;
use Moox\Builder\FieldTypes\Types\TimeFieldType;
use Moox\Builder\FieldTypes\Types\ToggleFieldType;
use Moox\Builder\FieldTypes\Types\UrlFieldType;
use Moox\Builder\Http\Livewire\BuilderMediaPickerModal;
use Moox\Builder\Http\Middleware\ResolveBuilderAdminLocale;
use Moox\Builder\Listeners\PersistCustomFields;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldGroupTranslation;
use Moox\Builder\Models\FieldOption;
use Moox\Builder\Models\FieldOptionTranslation;
use Moox\Builder\Models\FieldTranslation;
use Moox\Builder\Observers\InvalidateDefinitionCacheObserver;
use Moox\Builder\Observers\PurgeFieldValuesObserver;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\BuilderFieldValueMediaMetadataSync;
use Moox\Builder\Support\BuilderAdminLocalizationCatalog;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\CustomFieldsFilamentHooks;
use Moox\Builder\Support\DefinitionTranslator;
use Moox\Builder\Support\EntityModelDeletionRegistrar;
use Moox\Builder\Support\LocationConstraintOptions;
use Moox\Builder\Support\MediaIntegration;
use Moox\Builder\Support\RelationTargetResolver;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BuilderServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('builder')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_builder_field_groups_table',
                'create_builder_fields_table',
                'create_builder_field_options_table',
                'create_builder_field_values_table',
                'create_builder_field_group_translations_table',
                'create_builder_field_translations_table',
                'create_builder_field_option_translations_table',
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

        $this->app->singleton(EntityRegistry::class);

        // Request-scoped: memoizes default locale / fallback chain (thousands of
        // translate() calls during field group editor hydration otherwise each hit DB).
        $this->app->scoped(BuilderLocaleResolver::class);
        $this->app->scoped(DefinitionTranslator::class);
        $this->app->scoped(BuilderAdminLocalizationCatalog::class);

        // Request-scoped so its relation-label memo is reused across table rows
        // and re-renders within a request, while staying Octane-safe (reset per
        // request, so target title changes are never served stale).
        $this->app->scoped(RelationTargetResolver::class);
        $this->app->scoped(LocationConstraintOptions::class);
    }

    public function packageBooted(): void
    {
        FieldGroup::observe(InvalidateDefinitionCacheObserver::class);
        FieldGroup::observe(PurgeFieldValuesObserver::class);
        Field::observe(InvalidateDefinitionCacheObserver::class);
        Field::observe(PurgeFieldValuesObserver::class);
        FieldOption::observe(InvalidateDefinitionCacheObserver::class);
        FieldGroupTranslation::observe(InvalidateDefinitionCacheObserver::class);
        FieldTranslation::observe(InvalidateDefinitionCacheObserver::class);
        FieldOptionTranslation::observe(InvalidateDefinitionCacheObserver::class);

        Event::listen(RecordSaved::class, PersistCustomFields::class);

        FilamentAsset::register([
            Css::make('moox-builder', __DIR__.'/../resources/css/builder.css'),
        ], 'moox/builder');

        CustomFieldsFilamentHooks::register();

        $this->app['router']->pushMiddlewareToGroup('web', ResolveBuilderAdminLocale::class);

        $this->registerMediaMetadataSyncListeners();

        $this->app->booted(function (): void {
            app(EntityModelDeletionRegistrar::class)->register();
            $this->registerMediaPickerModal();
        });
    }

    /**
     * @return list<FieldType>
     */
    protected function defaultFieldTypes(): array
    {
        $types = [
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
            new RangeFieldType,
            new ButtonGroupFieldType,
            new LinkFieldType,
            new RelationFieldType,
        ];

        if (MediaIntegration::isAvailable()) {
            $types[] = new FieldTypes\Types\ImageFieldType;
            $types[] = new FieldTypes\Types\GalleryFieldType;
            $types[] = new FieldTypes\Types\FileFieldType;
        }

        return array_merge($types, [
            new RichTextFieldType,
            new MessageFieldType,
            new OembedFieldType,
            new TabFieldType,
            new SectionFieldType,
            new GroupFieldType,
            new RepeaterFieldType,
            new FlexibleContentFieldType,
            new FlexibleLayoutFieldType,
        ]);
    }

    protected function registerMediaPickerModal(): void
    {
        if (! MediaIntegration::isAvailable() || ! $this->app->bound('livewire.finder')) {
            return;
        }

        Livewire::component('builder-media-picker-modal', BuilderMediaPickerModal::class);
    }

    protected function registerMediaMetadataSyncListeners(): void
    {
        if (! MediaIntegration::isAvailable()) {
            return;
        }

        $translationClass = 'Moox\Media\Models\MediaTranslation';
        $mediaClass = 'Moox\Media\Models\Media';

        if (! class_exists($translationClass) || ! class_exists($mediaClass)) {
            return;
        }

        $sync = function (object $translation) use ($mediaClass): void {
            $media = $mediaClass::query()->find($translation->media_id ?? null);

            if ($media !== null) {
                app(BuilderFieldValueMediaMetadataSync::class)->syncForMedia($media);
            }
        };

        $translationClass::saved($sync);
        $translationClass::updated($sync);
    }
}
