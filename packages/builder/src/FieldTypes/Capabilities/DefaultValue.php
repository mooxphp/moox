<?php

declare(strict_types=1);

namespace Moox\Builder\FieldTypes\Capabilities;

use Carbon\CarbonInterface;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Services\ClonedFieldGroupResolver;
use Moox\Builder\Support\RichTextValue;

class DefaultValue extends Capability
{
    public function builderFields(): array
    {
        return [
            TextInput::make('config.default')
                ->label(__('builder::builder.capabilities.default_value')),
        ];
    }

    public function builderFieldsFor(string $fieldType): array
    {
        return match ($fieldType) {
            'toggle' => [
                Toggle::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->helperText(__('builder::builder.capabilities.default_value_toggle_helper'))
                    ->inline(false)
                    ->default(false),
            ],
            'color' => [
                $this->colorDefaultField(),
            ],
            'date' => $this->temporalDefaultFields(
                DatePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(false),
                'date',
            ),
            'datetime' => $this->temporalDefaultFields(
                DateTimePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(false),
                'datetime',
            ),
            'time' => $this->temporalDefaultFields(
                TimePicker::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->native(true),
                'time',
            ),
            'select', 'radio', 'button_group' => [
                Select::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->helperText(__('builder::builder.capabilities.default_value_option_helper'))
                    ->options(fn (Get $get): array => $this->optionChoices($get('options')))
                    ->searchable()
                    ->native(false)
                    ->live(),
            ],
            'multiselect', 'checkbox_list' => [
                Select::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value'))
                    ->helperText(__('builder::builder.capabilities.default_value_multi_option_helper'))
                    ->options(fn (Get $get): array => $this->optionChoices($get('options')))
                    ->multiple()
                    ->searchable()
                    ->native(false),
            ],
            'number' => [
                $this->numericDefaultField(),
            ],
            'range' => [
                $this->rangeDefaultField(),
            ],
            'text', 'password' => [
                $this->textDefaultField(),
            ],
            'textarea', 'rich_text' => [
                $this->textareaDefaultField(),
            ],
            'email' => [
                $this->emailDefaultField(),
            ],
            'url', 'oembed' => [
                $this->urlDefaultField(),
            ],
            default => [
                TextInput::make('config.default')
                    ->label(__('builder::builder.capabilities.default_value')),
            ],
        };
    }

    public function apply(Component $component, FieldDefinition $field): Component
    {
        $default = $this->resolveForField($field);

        if ($default === null) {
            return $component;
        }

        $component->default($default);

        return $component;
    }

    public function resolveForField(FieldDefinition $field): mixed
    {
        if (in_array($field->type, ['date', 'datetime', 'time'], true)) {
            return $this->resolveTemporalDefault($field);
        }

        if (! array_key_exists('default', $field->config)) {
            return null;
        }

        $default = $field->config['default'];

        if ($default === null || $default === '') {
            return null;
        }

        if ($field->type === 'toggle') {
            return $this->resolveBooleanDefault($default);
        }

        if ($field->type === 'number' && is_numeric($default)) {
            $numeric = $default + 0;

            return $this->numericDefaultWithinBounds($numeric, $field) ? $numeric : null;
        }

        if ($field->type === 'range' && is_numeric($default)) {
            $numeric = $default + 0;

            if (! $this->rangeDefaultIsValid($numeric, $field->config)) {
                return null;
            }

            return $this->normalizeRangeDefault($numeric);
        }

        if (in_array($field->type, ['multiselect', 'checkbox_list'], true)) {
            return $this->resolveArrayDefault($default);
        }

        if (in_array($field->type, ['select', 'radio', 'button_group'], true)) {
            return $this->resolveOptionDefault($field, (string) $default);
        }

        if ($field->type === 'color') {
            return $this->normalizeColorDefault($default);
        }

        if (in_array($field->type, ['url', 'oembed'], true)) {
            return $this->resolveUrlDefault($default);
        }

        if (in_array($field->type, ['text', 'textarea', 'rich_text', 'email', 'password'], true)
            && ! $this->defaultWithinMaxLength($default, $field)) {
            return null;
        }

        return $default;
    }

    /**
     * @param  Collection<int, FieldDefinition>  $children
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mergeIntoData(Collection $children, array $data): array
    {
        return $this->mergeSubFieldDefaults($children, $data);
    }

    protected function normalizeColorDefault(mixed $default): ?string
    {
        if ($default === null) {
            return null;
        }

        if (! is_string($default)) {
            if (is_scalar($default)) {
                $default = (string) $default;
            } else {
                return null;
            }
        }

        $color = trim($default);

        if ($color === '' || $color === '#') {
            return null;
        }

        if (! str_starts_with($color, '#')) {
            $color = '#'.$color;
        }

        return $color;
    }

    public function normalizeColorValue(mixed $value): ?string
    {
        return $this->normalizeColorDefault($value);
    }

    public function normalizeTimeValue(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::today()->setTimeFromTimeString($value->format('H:i:s'));
        }

        if (! is_string($value)) {
            return null;
        }

        try {
            $parsed = Carbon::parse($value, config('app.timezone'));

            return Carbon::today()->setTime($parsed->hour, $parsed->minute, $parsed->second);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function colorDefaultField(): ColorPicker
    {
        return ColorPicker::make('config.default')
            ->label(__('builder::builder.capabilities.default_value'))
            ->live()
            ->afterStateHydrated(function (ColorPicker $component, mixed $state): void {
                $normalized = $this->normalizeColorValue($state);

                if ($normalized !== null && $normalized !== $state) {
                    $component->state($normalized);
                }
            })
            ->afterStateUpdated(function (ColorPicker $component, mixed $state): void {
                $normalized = $this->normalizeColorValue($state);

                if ($normalized !== null && $normalized !== $state) {
                    $component->state($normalized);
                }
            });
    }

    public function hasConfiguredDefault(FieldDefinition $field): bool
    {
        if (in_array($field->type, ['date', 'datetime', 'time'], true)) {
            return ($field->config['defaultNow'] ?? false) === true
                || array_key_exists('default', $field->config);
        }

        return array_key_exists('default', $field->config);
    }

    public function shouldApplyDefault(mixed $state, string $type): bool
    {
        if ($type === 'toggle') {
            return $state === null;
        }

        if ($type === 'color') {
            return $state === null || (is_string($state) && trim($state) === '');
        }

        if (in_array($type, ['date', 'datetime', 'time'], true)) {
            if ($state === null || $state === '') {
                return true;
            }

            if ($state instanceof CarbonInterface) {
                return false;
            }

            if (is_string($state)) {
                try {
                    Carbon::parse($state);

                    return false;
                } catch (\Throwable) {
                    return true;
                }
            }

            return true;
        }

        if (is_array($state)) {
            return $state === [];
        }

        if ($type === 'rich_text') {
            return RichTextValue::isEmpty($state);
        }

        return $state === null || $state === '';
    }

    public function shouldReplaceSliderFallbackState(FieldDefinition $field, mixed $state): bool
    {
        if ($field->type !== 'range' || ! is_numeric($state)) {
            return false;
        }

        $resolved = $this->resolveForField($field);

        if ($resolved === null) {
            return false;
        }

        $min = $field->config['min'] ?? null;

        if ($min === null || $min === '' || ! is_numeric($min)) {
            return false;
        }

        return ($state + 0) === ($min + 0) && ($resolved + 0) !== ($min + 0);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function rangeDefaultIsValid(float|int $value, array $config): bool
    {
        $field = new FieldDefinition(
            name: 'range',
            label: 'Range',
            type: 'range',
            config: $config,
        );

        return $this->numericDefaultWithinBounds($value, $field)
            && $this->numericDefaultAlignsWithStep($value, $field);
    }

    public function mergeCompoundDefaults(FieldDefinition $field, mixed $state): mixed
    {
        if (! is_array($state)) {
            return $state;
        }

        return match ($field->type) {
            'flexible_content' => $this->mergeFlexibleContentDefaults($field, $state),
            'repeater' => $this->mergeListItemDefaults($field, $state),
            'group', 'clone' => $this->mergeGroupDefaults($field, $state),
            default => $state,
        };
    }

    /**
     * @param  Collection<int, FieldDefinition>  $children
     * @return array<string, mixed>
     */
    public function defaultDataForChildren(Collection $children): array
    {
        return $this->mergeSubFieldDefaults($children, []);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $state
     * @return array<int, array<string, mixed>>
     */
    public function normalizeCompoundState(array $state): array
    {
        return array_is_list($state) ? array_values($state) : array_values($state);
    }

    /**
     * @param  array<int, array<string, mixed>>  $state
     * @return array<int, array<string, mixed>>
     */
    protected function mergeFlexibleContentDefaults(FieldDefinition $field, array $state): array
    {
        return array_values(array_map(function (mixed $item) use ($field): array {
            if (! is_array($item)) {
                return [];
            }

            $type = $item['type'] ?? null;
            $data = $item['data'] ?? [];

            if (! is_string($type) || ! is_array($data)) {
                return $item;
            }

            $layout = $field->layouts()->firstWhere('name', $type);

            if ($layout === null) {
                return $item;
            }

            $item['data'] = $this->mergeSubFieldDefaults($layout->children, $data);

            return $item;
        }, $state));
    }

    /**
     * @param  array<int, array<string, mixed>>  $state
     * @return array<int, array<string, mixed>>
     */
    protected function mergeGroupDefaults(FieldDefinition $field, array $state): array
    {
        if ($state === []) {
            return $state;
        }

        $children = app(ClonedFieldGroupResolver::class)->compoundChildren($field);

        if (! array_is_list($state)) {
            return [$this->mergeSubFieldDefaults($children, $state)];
        }

        return array_values(array_map(
            fn (mixed $item): array => is_array($item)
                ? $this->mergeSubFieldDefaults($children, $item)
                : [],
            $state,
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $state
     * @return array<int, array<string, mixed>>
     */
    protected function mergeListItemDefaults(FieldDefinition $field, array $state): array
    {
        return array_values(array_map(
            fn (mixed $item): array => is_array($item)
                ? $this->mergeSubFieldDefaults($field->children, $item)
                : [],
            $state,
        ));
    }

    /**
     * @param  Collection<int, FieldDefinition>  $children
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeSubFieldDefaults(Collection $children, array $data): array
    {
        foreach ($children as $child) {
            if ($child->type === 'tab') {
                foreach ($child->children as $tabChild) {
                    $data = $this->mergeFieldDefaultIntoData($tabChild, $data);
                }

                continue;
            }

            $data = $this->mergeFieldDefaultIntoData($child, $data);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeFieldDefaultIntoData(FieldDefinition $child, array $data): array
    {
        $fieldType = app(FieldTypeRegistry::class)->get($child->type);

        if ($fieldType->hasSubFields() && in_array($child->type, ['group', 'clone', 'repeater', 'flexible_content'], true)) {
            $key = $child->name;
            $nested = $data[$key] ?? [];

            if (! is_array($nested)) {
                $nested = [];
            }

            if (in_array($child->type, ['group', 'clone'], true) && ! array_is_list($nested)) {
                $data[$key] = $this->mergeSubFieldDefaults(
                    app(ClonedFieldGroupResolver::class)->compoundChildren($child),
                    $nested,
                );
            } else {
                $data[$key] = $this->mergeCompoundDefaults($child, $nested);
            }

            return $data;
        }

        if (! $fieldType->storesValue()) {
            return $data;
        }

        $current = $data[$child->name] ?? null;

        if (! $this->shouldApplyDefault($current, $child->type)) {
            return $data;
        }

        $default = $this->resolveForField($child);

        if ($default !== null) {
            $data[$child->name] = $default;
        }

        return $data;
    }

    /**
     * @param  list<array{label?: string, value?: string}>|null  $options
     * @return array<string, string>
     */
    protected function optionChoices(?array $options): array
    {
        if ($options === null) {
            return [];
        }

        $choices = [];

        foreach ($options as $option) {
            $value = $option['value'] ?? null;

            if (blank($value)) {
                continue;
            }

            $choices[(string) $value] = filled($option['label'] ?? null)
                ? (string) $option['label']
                : (string) $value;
        }

        return $choices;
    }

    protected function resolveOptionDefault(FieldDefinition $field, string $default): ?string
    {
        foreach ($field->options as $option) {
            $value = $option['value'] ?? null;

            if (blank($value)) {
                continue;
            }

            if ((string) $value === $default) {
                return (string) $value;
            }
        }

        return null;
    }

    protected function resolveBooleanDefault(mixed $default): bool
    {
        if (is_bool($default)) {
            return $default;
        }

        return filter_var($default, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return list<Component>
     */
    protected function temporalDefaultFields(Component $picker, string $type): array
    {
        if ($picker instanceof DateTimePicker && in_array($type, ['date', 'datetime', 'time'], true)) {
            $picker = $this->configureTemporalAdminPicker($picker, $type);
        }

        return [
            Toggle::make('config.defaultNow')
                ->label(__('builder::builder.capabilities.default_value_now'))
                ->helperText(match ($type) {
                    'date' => __('builder::builder.capabilities.default_value_now_date_helper'),
                    'datetime' => __('builder::builder.capabilities.default_value_now_datetime_helper'),
                    default => __('builder::builder.capabilities.default_value_now_time_helper'),
                })
                ->inline(false)
                ->default(false)
                ->live(),
            $picker->hidden(fn (Get $get): bool => $this->defaultNowEnabled($get)),
        ];
    }

    protected function defaultNowEnabled(Get $get): bool
    {
        return filter_var($get('config.defaultNow') ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    protected function configureTemporalAdminPicker(DateTimePicker $picker, string $type): DateTimePicker
    {
        if ($type === 'time') {
            return $picker
                ->native(true)
                ->seconds(
                    fn (Get $get): bool => str_contains($this->resolvedDisplayFormat($get, $type), ':s'),
                )
                ->afterStateHydrated(function (DateTimePicker $component, mixed $state): void {
                    $normalized = $this->normalizeTimeValue($state);

                    if ($normalized !== null && $normalized !== $state) {
                        $component->state($normalized);
                    }
                });
        }

        $picker = $picker
            ->displayFormat(fn (Get $get): string => $this->resolvedDisplayFormat($get, $type))
            ->format(fn (Get $get): string => $this->resolvedStorageFormat($get, $type))
            ->key(fn (Get $get): string => 'default-picker-'.$this->temporalPickerKeySuffix($get, $type));

        if ($type === 'datetime') {
            $picker = $picker->seconds(
                fn (Get $get): bool => str_contains($this->resolvedDisplayFormat($get, $type), 'H:i:s'),
            );
        }

        return $picker;
    }

    protected function temporalPickerKeySuffix(Get $get, string $type): string
    {
        return md5($type.':'.$this->resolvedDisplayFormat($get, $type));
    }

    protected function resolvedDisplayFormat(Get $get, string $type): string
    {
        return filled($get('config.displayFormat'))
            ? (string) $get('config.displayFormat')
            : DisplayFormat::defaultFor($type);
    }

    protected function resolvedStorageFormat(Get $get, string $type): string
    {
        if ($type === 'time') {
            return DisplayFormat::storageFormatForTime($this->resolvedDisplayFormat($get, $type));
        }

        if ($type === 'datetime') {
            return str_contains($this->resolvedDisplayFormat($get, $type), 'H:i:s')
                ? 'Y-m-d H:i:s'
                : 'Y-m-d H:i';
        }

        return 'Y-m-d';
    }

    protected function resolveTemporalDefault(FieldDefinition $field): CarbonInterface|string|null
    {
        if (($field->config['defaultNow'] ?? false) === true) {
            return match ($field->type) {
                'date' => now()->startOfDay(),
                'datetime' => now(),
                'time' => now(),
                default => null,
            };
        }

        if (! array_key_exists('default', $field->config)) {
            return null;
        }

        $default = $field->config['default'];

        if ($default === null || $default === '') {
            return null;
        }

        return $this->parseTemporalDefault($field->type, $default);
    }

    protected function parseTemporalDefault(string $type, mixed $default): CarbonInterface|string|null
    {
        if ($type === 'time') {
            return $this->normalizeTimeValue($default);
        }

        if ($default instanceof CarbonInterface) {
            return $default;
        }

        if (! is_string($default)) {
            return null;
        }

        try {
            return Carbon::parse($default);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveArrayDefault(mixed $default): array
    {
        if ($default === null || $default === '') {
            return [];
        }

        if (is_string($default)) {
            $decoded = json_decode($default, true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn (mixed $value): bool => $value !== null && $value !== ''));
            }

            return [$default];
        }

        if (! is_array($default)) {
            return [(string) $default];
        }

        return array_values(array_filter($default, fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    protected function textDefaultField(): TextInput
    {
        return TextInput::make('config.default')
            ->label(__('builder::builder.capabilities.default_value'))
            ->rules(fn (Get $get): array => $this->maxLengthDefaultRules($get))
            ->maxLength(fn (Get $get): ?int => $this->configuredMaxLength($get('config.maxLength')))
            ->validationAttribute(__('builder::builder.capabilities.default_value'))
            ->validationMessages([
                'max' => __('builder::builder.validation.default_max_length'),
            ])
            ->live(onBlur: true);
    }

    protected function textareaDefaultField(): Textarea
    {
        return Textarea::make('config.default')
            ->label(__('builder::builder.capabilities.default_value'))
            ->rows(3)
            ->rules(fn (Get $get): array => $this->maxLengthDefaultRules($get))
            ->maxLength(fn (Get $get): ?int => $this->configuredMaxLength($get('config.maxLength')))
            ->validationAttribute(__('builder::builder.capabilities.default_value'))
            ->validationMessages([
                'max' => __('builder::builder.validation.default_max_length'),
            ])
            ->live(onBlur: true);
    }

    protected function emailDefaultField(): TextInput
    {
        return TextInput::make('config.default')
            ->label(__('builder::builder.capabilities.default_value'))
            ->rules(fn (Get $get): array => array_merge(
                ['nullable', 'email'],
                $this->maxLengthConstraintRules($get),
            ))
            ->maxLength(fn (Get $get): ?int => $this->configuredMaxLength($get('config.maxLength')))
            ->validationAttribute(__('builder::builder.capabilities.default_value'))
            ->validationMessages([
                'email' => __('builder::builder.validation.invalid_email_default'),
                'max' => __('builder::builder.validation.default_max_length'),
            ])
            ->live(onBlur: true);
    }

    /**
     * @return list<string>
     */
    protected function maxLengthDefaultRules(Get $get): array
    {
        return array_merge(
            ['nullable', 'string'],
            $this->maxLengthConstraintRules($get),
        );
    }

    /**
     * @return list<string>
     */
    protected function maxLengthConstraintRules(Get $get): array
    {
        $maxLength = $this->configuredMaxLength($get('config.maxLength'));

        return $maxLength !== null ? ['max:'.$maxLength] : [];
    }

    protected function configuredMaxLength(mixed $maxLength): ?int
    {
        if ($maxLength === null || $maxLength === '' || ! is_numeric($maxLength)) {
            return null;
        }

        $maxLength = (int) $maxLength;

        return $maxLength > 0 ? $maxLength : null;
    }

    protected function defaultWithinMaxLength(mixed $default, FieldDefinition $field): bool
    {
        $maxLength = $this->configuredMaxLength($field->config['maxLength'] ?? null);

        if ($maxLength === null || ! is_string($default)) {
            return true;
        }

        return mb_strlen($default) <= $maxLength;
    }

    protected function urlDefaultField(): TextInput
    {
        return TextInput::make('config.default')
            ->label(__('builder::builder.capabilities.default_value'))
            ->rules(['nullable', 'url'])
            ->validationAttribute(__('builder::builder.capabilities.default_value'))
            ->validationMessages([
                'url' => __('builder::builder.validation.invalid_url_default'),
            ])
            ->live(onBlur: true);
    }

    protected function resolveUrlDefault(mixed $default): ?string
    {
        if (! is_string($default)) {
            return null;
        }

        $url = trim($default);

        if ($url === '') {
            return null;
        }

        return $this->isValidUrl($url) ? $url : null;
    }

    protected function isValidUrl(string $url): bool
    {
        return Validator::make(
            ['url' => $url],
            ['url' => ['url']],
        )->passes();
    }

    protected function numericDefaultField(): TextInput
    {
        return TextInput::make('config.default')
            ->label(__('builder::builder.capabilities.default_value'))
            ->helperText(__('builder::builder.capabilities.default_value_number_helper'))
            ->numeric()
            ->rules(fn (Get $get): array => $this->numericDefaultRules($get))
            ->validationAttribute(__('builder::builder.capabilities.default_value'))
            ->live(onBlur: true);
    }

    protected function rangeDefaultField(): TextInput
    {
        return TextInput::make('config.default')
            ->label(__('builder::builder.capabilities.default_value'))
            ->helperText(__('builder::builder.capabilities.default_value_range_helper'))
            ->numeric()
            ->rules(fn (Get $get): array => $this->rangeDefaultRules($get))
            ->validationAttribute(__('builder::builder.capabilities.default_value'))
            ->validationMessages([
                'min' => __('builder::builder.validation.range_default_bounds'),
                'max' => __('builder::builder.validation.range_default_bounds'),
            ])
            ->live(onBlur: true);
    }

    /**
     * @return list<string|\Closure>
     */
    protected function rangeDefaultRules(Get $get): array
    {
        $rules = $this->numericDefaultRules($get);

        $rules[] = function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_numeric($value)) {
                return;
            }

            if (! $this->rangeDefaultIsValid($value + 0, [
                'min' => $get('config.min'),
                'max' => $get('config.max'),
                'step' => $get('config.step'),
            ])) {
                $fail(__('builder::builder.validation.range_default_step'));
            }
        };

        return $rules;
    }

    /**
     * @return list<string>
     */
    protected function numericDefaultRules(Get $get): array
    {
        $rules = ['nullable', 'numeric'];

        $min = $get('config.min');

        if ($min !== null && $min !== '' && is_numeric($min)) {
            $rules[] = 'min:'.$min;
        }

        $max = $get('config.max');

        if ($max !== null && $max !== '' && is_numeric($max)) {
            $rules[] = 'max:'.$max;
        }

        return $rules;
    }

    protected function numericDefaultWithinBounds(float|int $value, FieldDefinition $field): bool
    {
        if (isset($field->config['min']) && $field->config['min'] !== '' && is_numeric($field->config['min'])) {
            if ($value < $field->config['min'] + 0) {
                return false;
            }
        }

        if (isset($field->config['max']) && $field->config['max'] !== '' && is_numeric($field->config['max'])) {
            if ($value > $field->config['max'] + 0) {
                return false;
            }
        }

        return true;
    }

    protected function numericDefaultAlignsWithStep(float|int $value, FieldDefinition $field): bool
    {
        $step = $field->config['step'] ?? null;

        if ($step === null || $step === '' || ! is_numeric($step) || $step + 0 <= 0) {
            return true;
        }

        $min = $field->config['min'] ?? null;
        $origin = ($min !== null && $min !== '' && is_numeric($min)) ? $min + 0 : 0;
        $diff = $value - $origin;

        if ($diff < -1e-9) {
            return false;
        }

        $steps = $diff / ($step + 0);

        return abs($steps - round($steps)) < 1e-6;
    }

    protected function normalizeRangeDefault(float|int $value): float|int
    {
        if (abs($value - round($value)) < 1e-9) {
            return (int) round($value);
        }

        return round($value, 6);
    }
}
