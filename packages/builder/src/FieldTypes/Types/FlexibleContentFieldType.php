<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Schemas\Components\Component;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldDefinition;
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
            ->collapsed();

        return $this->applyNestedValueValidation(
            $this->applyCapabilitiesAndValidation($component, $field),
            $field,
        );
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
