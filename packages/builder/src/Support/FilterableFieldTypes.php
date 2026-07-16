<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Moox\Builder\Data\FieldDefinition;

final class FilterableFieldTypes
{
    public static function supports(FieldDefinition $field): bool
    {
        return match ($field->type) {
            'select', 'radio', 'button_group' => $field->options !== [],
            'toggle' => true,
            'relation' => ! RelationValueRules::isMultiple($field)
                && filled($field->config['related_entity'] ?? null),
            default => false,
        };
    }
}
