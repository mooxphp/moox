<?php

declare(strict_types=1);

namespace Moox\Builder\Observers;

use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Services\CompoundFieldValueMigrator;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Services\FieldValuePurger;

class PurgeFieldValuesObserver
{
    public function __construct(
        protected FieldValuePurger $purger,
        protected FieldGroupPersistence $fieldGroupPersistence,
        protected CompoundFieldValueMigrator $compoundFieldValueMigrator,
    ) {
    }

    public function deleting(FieldGroup|Field $model): void
    {
        if (! $model instanceof FieldGroup) {
            return;
        }

        $group = $model;
        $group->loadMissing('fields');

        $entities = $this->fieldGroupPersistence->entitiesFromLocationRules(
            $group->location_rules ?? [],
        );

        $this->purger->purgeForFieldNames(
            $group->fields->pluck('name')->all(),
            $entities,
        );
    }

    public function deleted(Field|FieldGroup $model): void
    {
        if (! $model instanceof Field) {
            return;
        }

        $field = $model;
        $field->loadMissing('fieldGroup');

        if ($field->fieldGroup === null) {
            return;
        }

        $entities = $this->fieldGroupPersistence->entitiesFromLocationRules(
            $field->fieldGroup->location_rules ?? [],
        );

        if ($field->parent_field_id !== null) {
            $this->compoundFieldValueMigrator->removeNestedSubfield($field, $entities);

            return;
        }

        $this->purger->purgeForFieldName($field->name, $entities);
    }
}
