<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Schemas\Components\Component;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\RepeaterItems;
use Moox\Builder\FieldTypes\FieldType;

class FlexibleContentFieldType extends FieldType
{
    public static function key(): string
    {
        return 'flexible_content';
    }

    public function hasSubFields(): bool
    {
        return true;
    }

    public function hasLayouts(): bool
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

        $layoutDefaults = $field->layouts()
            ->mapWithKeys(fn (FieldDefinition $layout): array => [
                $layout->name => $defaultValue->defaultDataForChildren($layout->children),
            ])
            ->all();

        $blocks = $field->layouts()
            ->map(fn (FieldDefinition $layout): Block => Block::make($layout->name)
                ->label($layout->label)
                ->schema($compiler->compileSubFields($layout->children, null)))
            ->all();

        $component = Builder::make($field->name)
            ->label($field->label)
            ->blocks($blocks)
            ->addActionLabel(__('builder::builder.flexible_content.add_layout'))
            ->collapsible()
            ->collapsed()
            ->addAction(fn (Action $action): Action => $this->configureAddAction($action, $layoutDefaults))
            ->addBetweenAction(fn (Action $action): Action => $this->configureAddBetweenAction($action, $layoutDefaults));

        return $this->applyNestedValueValidation(
            $this->applyCapabilitiesAndValidation($component, $field),
            $field,
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $layoutDefaults
     */
    protected function configureAddAction(Action $action, array $layoutDefaults): Action
    {
        return $action->action(function (array $arguments, Builder $component, array $data = []) use ($layoutDefaults): void {
            $this->insertBuilderBlock($component, (string) $arguments['block'], $data, $layoutDefaults, afterItem: null);
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $layoutDefaults
     */
    protected function configureAddBetweenAction(Action $action, array $layoutDefaults): Action
    {
        return $action->action(function (array $arguments, Builder $component, array $data = []) use ($layoutDefaults): void {
            $this->insertBuilderBlock(
                $component,
                (string) $arguments['block'],
                $data,
                $layoutDefaults,
                afterItem: $arguments['afterItem'] ?? null,
            );
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $layoutDefaults
     */
    protected function insertBuilderBlock(
        Builder $component,
        string $block,
        array $data,
        array $layoutDefaults,
        ?string $afterItem,
    ): void {
        if ($data === []) {
            $data = $layoutDefaults[$block] ?? [];
        }

        $newKey = $component->generateUuid();
        $items = $component->getRawState() ?? [];

        if ($afterItem === null) {
            if ($newKey) {
                $items[$newKey] = ['type' => $block, 'data' => $data];
            } else {
                $items[] = ['type' => $block, 'data' => $data];
                $newKey = (string) array_key_last($items);
            }
        } else {
            $ordered = [];

            foreach ($items as $key => $item) {
                $ordered[$key] = $item;

                if ($key === $afterItem) {
                    if ($newKey) {
                        $ordered[$newKey] = ['type' => $block, 'data' => $data];
                    } else {
                        $ordered[] = ['type' => $block, 'data' => $data];
                        $newKey = (string) array_key_last($ordered);
                    }
                }
            }

            $items = $ordered;
        }

        $component->rawState($items);

        $schemaKey = $newKey ?? (string) array_key_last($items);
        $component->getChildSchema($schemaKey)->fill($data);

        $component->collapsed(false, shouldMakeComponentCollapsible: false);
        $component->callAfterStateUpdated();
    }

    public function castValue(mixed $raw): mixed
    {
        if (! is_array($raw)) {
            return [];
        }

        $items = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (isset($item['type'], $item['data']) && is_array($item['data'])) {
                $items[] = [
                    'type' => (string) $item['type'],
                    'data' => $item['data'],
                ];

                continue;
            }

            if (isset($item['layout']) && is_array($item)) {
                $layout = (string) $item['layout'];
                unset($item['layout']);
                $items[] = ['type' => $layout, 'data' => $item];
            }
        }

        return array_values($items);
    }

    public function normalizeForForm(mixed $stored): array
    {
        return $this->castValue($stored);
    }
}
