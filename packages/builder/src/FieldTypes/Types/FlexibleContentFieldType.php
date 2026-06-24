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
            ->addAction(fn (Action $action): Action => $this->configureAddAction($action, $field))
            ->addBetweenAction(fn (Action $action): Action => $this->configureAddBetweenAction($action, $field));

        return $this->applyNestedValueValidation(
            $this->applyCapabilitiesAndValidation($component, $field),
            $field,
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $layoutDefaults
     */
    protected function configureAddAction(Action $action, FieldDefinition $field): Action
    {
        return $action->action(function (array $arguments, Builder $component, array $data = []) use ($field): void {
            $this->insertBuilderBlock($component, $field, (string) $arguments['block'], $data, afterItem: null);
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $layoutDefaults
     */
    protected function configureAddBetweenAction(Action $action, FieldDefinition $field): Action
    {
        return $action->action(function (array $arguments, Builder $component, array $data = []) use ($field): void {
            $this->insertBuilderBlock(
                $component,
                $field,
                (string) $arguments['block'],
                $data,
                afterItem: $arguments['afterItem'] ?? null,
            );
        });
    }

    protected function insertBuilderBlock(
        Builder $component,
        FieldDefinition $field,
        string $block,
        array $data,
        ?string $afterItem,
    ): void {
        $layout = $field->layouts()->firstWhere('name', $block);

        if ($layout !== null) {
            $data = app(DefaultValue::class)->mergeIntoData($layout->children, $data);
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
        $component->getChildSchema($schemaKey)->fill(filled($data) ? $data : null);
        $component->hydrateItems();

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
