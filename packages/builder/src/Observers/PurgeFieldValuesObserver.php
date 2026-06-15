<?php

declare(strict_types=1);

namespace Moox\Builder\Observers;

use Moox\Builder\Models\Field;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Services\FieldValuePurger;

class PurgeFieldValuesObserver
{
    public function __construct(
        protected FieldValuePurger $purger,
        protected FieldGroupPersistence $fieldGroupPersistence,
    ) {}

    public function deleted(Field $field): void
    {
        $field->loadMissing('fieldGroup');

        if ($field->fieldGroup === null) {
            return;
        }

        $entities = $this->fieldGroupPersistence->entitiesFromLocationRules(
            $field->fieldGroup->location_rules ?? [],
        );

        $this->purger->purgeForFieldName($field->name, $entities);
    }
}
