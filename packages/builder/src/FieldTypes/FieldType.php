<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes;

use Carbon\CarbonInterface;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Carbon;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\Capability;
use Moox\Builder\Services\FieldValueValidator;

abstract class FieldType
{
    abstract public static function key(): string;

    abstract public function formComponent(FieldDefinition $field): Component;

    /**
     * @return list<class-string<Capability>>
     */
    public function capabilities(): array
    {
        return [];
    }

    public function castValue(mixed $raw, ?FieldDefinition $field = null): mixed
    {
        return $raw;
    }

    public function persistValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        return $this->castValue($value, $field);
    }

    public function presentValue(mixed $value, ?FieldDefinition $field = null): mixed
    {
        return $value;
    }

    protected function presentIsoDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return $value;
            }
        }

        return null;
    }

    protected function presentIsoDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value)->toIso8601String();
            } catch (\Throwable) {
                return $value;
            }
        }

        return null;
    }

    public function hasOptions(): bool
    {
        return false;
    }

    public function storesValue(): bool
    {
        return true;
    }

    public function isLayoutMarker(): bool
    {
        return false;
    }

    public function hasSubFields(): bool
    {
        return false;
    }

    public function hasLayouts(): bool
    {
        return false;
    }

    public function isInternal(): bool
    {
        return false;
    }

    public function label(): string
    {
        $key = 'builder::builder.field_types.'.str_replace('-', '_', static::key());

        return __($key) !== $key ? __($key) : ucfirst(str_replace('_', ' ', static::key()));
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
            $component->required();
        }

        return $component;
    }

    protected function applyNestedValueValidation(Component $component, FieldDefinition $field): Component
    {
        if (! $this->hasSubFields()) {
            return $component;
        }

        return $component->rules([
            fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($field): void {
                foreach (app(FieldValueValidator::class)->messagesFor($field, $value, $attribute) as $messages) {
                    foreach ($messages as $message) {
                        $fail($message);
                    }
                }
            },
        ]);
    }

    /**
     * @return list<string>
     */
    protected function additionalRules(FieldDefinition $field): array
    {
        return $field->validation['rules'] ?? [];
    }
}
