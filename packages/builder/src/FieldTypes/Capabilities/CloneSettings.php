<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Services\ClonedFieldGroupResolver;

class CloneSettings extends Capability
{
    /**
     * @return list<Component>
     */
    public function builderFieldsFor(string $fieldType): array
    {
        if ($fieldType !== 'clone') {
            return [];
        }

        return $this->builderFields();
    }

    /**
     * @return list<Component>
     */
    public function builderFields(): array
    {
        return [
            Select::make('config.field_group_slug')
                ->label(__('builder::builder.field.clone_field_group'))
                ->helperText(__('builder::builder.field.clone_field_group_helper'))
                ->options(fn (callable $get): array => app(ClonedFieldGroupResolver::class)->optionsForSelect(
                    static::excludeFieldGroupSlug($get),
                ))
                ->required()
                ->searchable()
                ->live()
                ->native(false)
                ->disabled(fn (callable $get): bool => app(ClonedFieldGroupResolver::class)->optionsForSelect(
                    static::excludeFieldGroupSlug($get),
                ) === [])
                ->rules([
                    fn (callable $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                        $currentSlug = static::excludeFieldGroupSlug($get);

                        if ($currentSlug !== '' && (string) $value === $currentSlug) {
                            $fail(__('builder::builder.validation.clone_field_group_self'));
                        }
                    },
                ]),
        ];
    }

    protected static function excludeFieldGroupSlug(callable $get): ?string
    {
        $slug = trim((string) ($get('../../slug') ?? $get('../../../slug') ?? ''));

        return $slug !== '' ? $slug : null;
    }

    /**
     * @return list<string|\Closure>
     */
    public function rules(FieldDefinition $field): array
    {
        return [];
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        return $component;
    }
}
