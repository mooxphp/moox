<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Types;

use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Component;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\Capability;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\MaxLength;
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\Support\HtmlSanitizer;
use Moox\Builder\Support\RichTextValue;

class RichTextFieldType extends FieldType
{
    public static function key(): string
    {
        return 'rich_text';
    }

    public function persistValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        // Rich text is authored HTML and therefore untrusted; strip executable
        // XSS vectors before storing so API/frontend consumers receive safe
        // markup. Structured (JSON document) values are left untouched.
        if (is_string($value)) {
            return HtmlSanitizer::clean($value);
        }

        return $value;
    }

    public function capabilities(): array
    {
        return [
            MaxLength::class,
            DefaultValue::class,
            HelperText::class,
        ];
    }

    public function formComponent(FieldDefinition $field): Component
    {
        $component = RichEditor::make($field->name)
            ->label($field->label);

        return $this->applyCapabilitiesAndValidation($component, $field);
    }

    protected function applyCapabilitiesAndValidation(Component $component, FieldDefinition $field): Component
    {
        $component = Capability::applyAll($this->capabilities(), $component, $field);

        $rules = [];

        foreach ($this->capabilities() as $capabilityClass) {
            $rules = array_merge($rules, app($capabilityClass)->rules($field));
        }

        $rules = array_merge($rules, $this->additionalRules($field));

        if ($rules !== []) {
            $component->rules($rules);
        }

        if (($field->validation['required'] ?? false) === true) {
            $component->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($field): void {
                    if (RichTextValue::isEmpty($value)) {
                        $fail(__('validation.required', ['attribute' => $field->label]));
                    }
                },
            ]);
        }

        return $component;
    }
}
