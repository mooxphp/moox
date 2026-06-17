<?php

declare(strict_types=1);

namespace Moox\Builder\Concerns;

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
 * saving is handled automatically via Filament record events.
 */
trait HasCustomFields
{
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
