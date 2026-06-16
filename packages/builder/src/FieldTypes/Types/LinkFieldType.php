<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\FieldType;

class LinkFieldType extends FieldType
{
    public static function key(): string
    {
        return 'link';
    }

    public function castValue(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $raw = $decoded;
            }
        }

        if (! is_array($raw)) {
            return null;
        }

        $url = $raw['url'] ?? null;
        $label = $raw['label'] ?? null;

        if (blank($url) && blank($label)) {
            return null;
        }

        $opensInNewTab = false;

        if (array_key_exists('opens_in_new_tab', $raw)) {
            $opensInNewTab = (bool) $raw['opens_in_new_tab'];
        } elseif (array_key_exists('target', $raw)) {
            $opensInNewTab = ($raw['target'] ?? '_self') === '_blank';
        }

        return [
            'url' => $url,
            'label' => $label,
            'opens_in_new_tab' => $opensInNewTab,
        ];
    }

    public function capabilities(): array
    {
        return [
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $url = TextInput::make('url')
            ->label(__('builder::builder.link.url'))
            ->url()
            ->nullable();

        if (($field->validation['required'] ?? false) === true) {
            $url->required();
        } else {
            $url->nullable();
        }

        $fieldset = Fieldset::make($field->label)
            ->schema([
                $url,
                TextInput::make('label')
                    ->label(__('builder::builder.link.label')),
                Toggle::make('opens_in_new_tab')
                    ->label(__('builder::builder.link.opens_in_new_tab')),
            ])
            ->statePath($field->name)
            ->columnSpanFull();

        return $fieldset;
    }

    protected function additionalRules(FieldDefinition $field): array
    {
        // Validation rules in `$field->validation['rules']` target the field state,
        // which for `link` is an array. We validate the nested URL component instead.
        return [];
    }
}
