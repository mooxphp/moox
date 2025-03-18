<?php

declare(strict_types=1);

namespace Moox\Localization\View\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Component as FilamentComponent;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class TranslationTabs extends Component
{
    protected string $view = 'filament::forms.components.tabs';

    public function __construct(
        public array $fields = [],
        public ?string $title = 'Translations',
        public ?string $model = null,
        public ?string $table = null,
        public ?string $column = null,
    ) {}

    public function getTabs(): array
    {
        return collect($this->getActiveLocales())->map(fn (string $locale) => Tab::make($locale)
            ->label(strtoupper($locale))
            ->schema($this->getTabSchema($locale))
        )->toArray();
    }

    protected function getActiveLocales(): array
    {
        return \Moox\Localization\Models\Localization::query()
            ->where('is_active_admin', true)
            ->get()
            ->pluck('language.alpha2')
            ->toArray();
    }

    protected function getTabSchema(string $locale): array
    {
        $schema = [];

        foreach ($this->fields as $field) {
            $component = $this->createComponent($field, $locale);
            if ($component) {
                $schema[] = $component;
            }
        }

        return $schema;
    }

    protected function createComponent(array $field, string $locale): ?FilamentComponent
    {
        $fieldName = $field['name'];
        $fieldType = $field['type'] ?? 'text';
        $fieldLabel = $field['label'] ?? __('core::core.'.$fieldName);
        $fieldRequired = $field['required'] ?? true;
        $fieldUnique = $field['unique'] ?? false;
        $fieldComponent = $field['component'] ?? null;
        $fieldOptions = $field['options'] ?? [];

        // If a custom component is provided, use it
        if ($fieldComponent && class_exists($fieldComponent)) {
            $component = $fieldComponent::make("translations.{$locale}.{$fieldName}");
        } else {
            // Fallback to default components
            $component = match ($fieldType) {
                'markdown' => MarkdownEditor::make("translations.{$locale}.{$fieldName}"),
                'textarea' => \Filament\Forms\Components\Textarea::make("translations.{$locale}.{$fieldName}"),
                'select' => \Filament\Forms\Components\Select::make("translations.{$locale}.{$fieldName}"),
                'toggle' => \Filament\Forms\Components\Toggle::make("translations.{$locale}.{$fieldName}"),
                'checkbox' => \Filament\Forms\Components\Checkbox::make("translations.{$locale}.{$fieldName}"),
                'radio' => \Filament\Forms\Components\Radio::make("translations.{$locale}.{$fieldName}"),
                'date' => \Filament\Forms\Components\DatePicker::make("translations.{$locale}.{$fieldName}"),
                'datetime' => \Filament\Forms\Components\DateTimePicker::make("translations.{$locale}.{$fieldName}"),
                'time' => \Filament\Forms\Components\TimePicker::make("translations.{$locale}.{$fieldName}"),
                'color' => \Filament\Forms\Components\ColorPicker::make("translations.{$locale}.{$fieldName}"),
                'file' => \Filament\Forms\Components\FileUpload::make("translations.{$locale}.{$fieldName}"),
                'image' => \Filament\Forms\Components\FileUpload::make("translations.{$locale}.{$fieldName}")->image(),
                'rich' => \Filament\Forms\Components\RichEditor::make("translations.{$locale}.{$fieldName}"),
                default => TextInput::make("translations.{$locale}.{$fieldName}"),
            };
        }

        // Apply common options
        $component
            ->label($fieldLabel)
            ->required($fieldRequired)
            ->afterStateHydrated(function ($component, ?string $state) use ($locale, $fieldName) {
                $translation = $component->getRecord()?->translations
                    ->where('locale', $locale)
                    ->first();

                if ($translation) {
                    $component->state($translation->{$fieldName});
                }
            });

        // Apply unique validation if needed
        if ($fieldUnique && $this->model && $this->table && $this->column) {
            $component->unique(
                modifyRuleUsing: function (Unique $rule) use ($locale) {
                    return $rule
                        ->where('locale', $locale)
                        ->whereNull("{$this->table}.{$this->model}_id");
                },
                table: $this->table,
                column: $this->column,
                ignoreRecord: true,
                ignorable: fn ($record) => $record?->translations()
                    ->where('locale', $locale)
                    ->first()
            );
        }

        // Handle slug generation
        if ($fieldName === 'title' && isset($field['slug_field'])) {
            $component->afterStateUpdated(
                fn (Set $set, ?string $state) => $set("translations.{$locale}.{$field['slug_field']}", Str::slug($state))
            );
        }

        // Apply any additional options
        foreach ($fieldOptions as $method => $value) {
            if (method_exists($component, $method)) {
                $component->{$method}($value);
            }
        }

        return $component;
    }
}
