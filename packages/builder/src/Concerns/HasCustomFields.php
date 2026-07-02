<?php

declare(strict_types=1);

namespace Moox\Builder\Concerns;

use Astrotomic\Translatable\Contracts\Translatable;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Registry\DefinitionRegistry;

/**
 * Add custom field group sections to a Filament resource form.
 *
 * Usage in your resource form schema:
 *
 *   ...static::customFieldComponents(),
 *
 * Field groups are matched by entity key (model basename in kebab-case, e.g.
 * Item → item). Override with customFieldsEntity() when needed. Loading and
 * saving is handled automatically via Filament record events. Per-locale
 * values and the admin language selector apply only when the entity model
 * is translatable (or customFieldsAreTranslatable() returns true).
 */
trait HasCustomFields
{
    /**
     * Whether custom field values are stored per admin locale.
     * Defaults to whether the model implements Astrotomic TranslatableContract.
     */
    public static function customFieldsAreTranslatable(): bool
    {
        $model = static::getModel();

        return is_subclass_of($model, Translatable::class);
    }

    /**
     * Whether the builder should render its own admin locale switcher on this
     * resource's pages. Off by default: custom fields are used on translatable
     * entities that already ship a locale switcher (e.g. Draft), so the builder
     * would only add a duplicate. Opt in per resource when a standalone switcher
     * is actually needed.
     */
    public static function customFieldsRenderLocaleSwitcher(): bool
    {
        return false;
    }

    /**
     * @return list<Section>
     */
    public static function customFieldComponents(): array
    {
        $groups = app(DefinitionRegistry::class)->fieldGroupsFor(
            static::customFieldsLocationContext(),
        );

        if ($groups->isEmpty()) {
            return [];
        }

        return app(SchemaCompiler::class)->compile($groups, static::class);
    }

    public static function customFieldsLocationContext(): LocationContext
    {
        return new LocationContext(static::resolveCustomFieldsEntityIdentifier());
    }

    public static function resolveCustomFieldsEntityIdentifier(): string
    {
        $entity = static::customFieldsEntity();

        if ($entity !== null) {
            return $entity;
        }

        $model = static::getModel();

        return Str::kebab(class_basename($model));
    }

    /**
     * Override the storage / location-rule entity key when it should differ
     * from the model basename (e.g. "item" instead of "test-item").
     */
    protected static function customFieldsEntity(): ?string
    {
        return null;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function customFieldRules(): array
    {
        $groups = app(DefinitionRegistry::class)->fieldGroupsFor(
            static::customFieldsLocationContext(),
        );

        return app(SchemaCompiler::class)->rules($groups);
    }
}
