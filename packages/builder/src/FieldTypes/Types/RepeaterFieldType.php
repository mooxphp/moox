<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\RepeaterItems;
use Moox\Builder\FieldTypes\FieldType;

class RepeaterFieldType extends FieldType
{
    public static function key(): string
    {
        return 'repeater';
    }

    public function hasSubFields(): bool
    {
        return true;
    }

    public function capabilities(): array
    {
        return [
            RepeaterItems::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $compiler = app(SchemaCompiler::class);
        $defaultValue = app(DefaultValue::class);

        $component = Repeater::make($field->name)
            ->label($field->label)
            ->schema($compiler->compileSubFields($field->children, null))
            ->collapsible()
            ->defaultItems(0)
            ->reorderable()
            ->addAction(fn (Action $action): Action => $action->action(
                function (Repeater $component) use ($defaultValue, $field): void {
                    $data = $defaultValue->mergeIntoData($field->children, []);

                    $newUuid = $component->generateUuid();
                    $items = $component->getRawState() ?? [];

                    if ($newUuid) {
                        $items[$newUuid] = $data;
                    } else {
                        $items[] = $data;
                        $newUuid = array_key_last($items);
                    }

                    $component->rawState($items);
                    $component->getChildSchema($newUuid)->fill($data);
                    $component->hydrateItems();
                    $component->collapsed(false, shouldMakeComponentCollapsible: false);
                    $component->callAfterStateUpdated();
                },
            ))
            ->addBetweenAction(fn (Action $action): Action => $action->action(
                function (array $arguments, Repeater $component) use ($defaultValue, $field): void {
                    $data = $defaultValue->mergeIntoData($field->children, []);
                    $newKey = $component->generateUuid();
                    $items = [];

                    foreach ($component->getRawState() ?? [] as $key => $item) {
                        $items[$key] = $item;

                        if ($key === $arguments['afterItem']) {
                            if ($newKey) {
                                $items[$newKey] = $data;
                            } else {
                                $items[] = $data;
                                $newKey = array_key_last($items);
                            }
                        }
                    }

                    $component->rawState($items);
                    $component->getChildSchema($newKey)->fill($data);
                    $component->hydrateItems();
                    $component->collapsed(false, shouldMakeComponentCollapsible: false);
                    $component->callAfterStateUpdated();
                },
            ));

        return $this->applyNestedValueValidation(
            $this->applyCapabilitiesAndValidation($component, $field),
            $field,
        );
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values($raw);
    }
}
