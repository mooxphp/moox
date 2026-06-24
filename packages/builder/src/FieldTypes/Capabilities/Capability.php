<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;

abstract class Capability
{
    /**
     * @return list<Component>
     */
    abstract public function builderFields(): array;

    /**
     * @return list<Component>
     */
    public function builderFieldsFor(string $fieldType): array
    {
        return $this->builderFields();
    }

    /**
     * @return list<string>
     */
    public function rules(FieldDefinition $field): array
    {
        return [];
    }

    abstract public function apply(Component $component, FieldDefinition $field): Component;

    /**
     * @param  list<class-string<Capability>>  $capabilities
     */
    public static function applyAll(array $capabilities, Component $component, FieldDefinition $field): Component
    {
        foreach ($capabilities as $class) {
            $component = app($class)->apply($component, $field);
        }

        return $component;
    }
}
