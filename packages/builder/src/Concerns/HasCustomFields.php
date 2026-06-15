<?php

declare(strict_types=1);

namespace Moox\Builder\Concerns;

use Filament\Schemas\Components\Section;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\EntityRegistry;

/**
 * Add custom field group sections to a Filament resource form.
 *
 * Usage in your resource form schema:
 *
 *   ...static::customFieldComponents(),
 *
 * The resource must be registered in config('builder.entities'). Loading and
 * saving is handled automatically via Filament record events registered by
 * the Builder service provider — no page-level hooks required.
 */
trait HasCustomFields
{
    /**
     * @return list<Section>
     */
    public static function customFieldComponents(): array
    {
        if (! static::isRegisteredForCustomFields()) {
            return [];
        }

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
        $entity = app(EntityRegistry::class)->resolveForResource(static::class);

        if ($entity === null) {
            throw new \LogicException(sprintf(
                'Resource [%s] uses HasCustomFields but is not registered in config(builder.entities).',
                static::class,
            ));
        }

        return $entity;
    }

    public static function isRegisteredForCustomFields(): bool
    {
        return app(EntityRegistry::class)->isRegisteredResource(static::class);
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
