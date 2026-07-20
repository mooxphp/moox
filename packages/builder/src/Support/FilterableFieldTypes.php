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
            'text', 'textarea', 'email', 'url', 'rich_text' => true,
            'number', 'range', 'date', 'datetime' => true,
            default => false,
        };
    }

    /**
     * Type-only check (no field instance required), e.g. to decide whether
     * the admin "Show in table filter" toggle should be offered at all,
     * before options or relation config are known.
     */
    public static function supportsType(string $type): bool
    {
        return match ($type) {
            'select', 'radio', 'button_group', 'toggle', 'relation' => true,
            'text', 'textarea', 'email', 'url', 'rich_text' => true,
            'number', 'range', 'date', 'datetime' => true,
            default => false,
        };
    }
}
