<?php

declare(strict_types=1);

namespace Moox\Builder\Concerns;

use Astrotomic\Translatable\Contracts\Translatable;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Compiler\TableColumnCompiler;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Support\FieldGroupPlacement;
use Moox\Builder\Support\FieldVisibility;

/**
 * Add custom field group sections to a Filament resource form.
 *
 * Usage in your resource form schema:
 *
 *   ...static::customFieldComponents(),              // main area (default)
 *   ...static::customFieldComponents('sidebar'),      // sidebar slot
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
     * Compiled custom field sections for the given form placement. Defaults to
     * the main area; pass FieldGroupPlacement::SIDEBAR (or 'sidebar') to render
     * groups assigned to the sidebar in a dedicated slot.
     *
     * @return list<Section>
     */
    public static function customFieldComponents(string $placement = FieldGroupPlacement::MAIN): array
    {
        $groups = static::visibleCustomFieldGroups()
            ->filter(fn (FieldGroupDefinition $group): bool => $group->hasPlacement($placement))
            ->values();

        if ($groups->isEmpty()) {
            return [];
        }

        return app(SchemaCompiler::class)->compile($groups, static::class);
    }

    /**
     * @return list<Column>
     */
    public static function customFieldColumns(): array
    {
        $groups = static::visibleCustomFieldGroups();

        if ($groups->isEmpty()) {
            return [];
        }

        return app(TableColumnCompiler::class)->compile($groups, static::class);
    }

    /**
     * Eager-loads custom field values on list/table queries (via BaseResource *ModifyTableQuery convention).
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected static function customFieldsModifyTableQuery(Builder $query): Builder
    {
        if (static::customFieldColumns() === []) {
            return $query;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = static::getModel();

        if (! in_array(InteractsWithCustomFields::class, class_uses_recursive($modelClass), true)) {
            return $query;
        }

        return $query->withCustomFields();
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
        return app(SchemaCompiler::class)->rules(static::visibleCustomFieldGroups());
    }

    /**
     * Field groups assigned to this resource, filtered to those visible in the
     * admin (Filament) context. Admin-hidden groups/fields are excluded from the
     * form, table and validation rules alike.
     *
     * @return Collection<int, FieldGroupDefinition>
     */
    protected static function visibleCustomFieldGroups(): Collection
    {
        return FieldVisibility::filterGroups(
            app(DefinitionRegistry::class)->fieldGroupsFor(static::customFieldsLocationContext()),
            FieldVisibility::ADMIN,
        );
    }
}
