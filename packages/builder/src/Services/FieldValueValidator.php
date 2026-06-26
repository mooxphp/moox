<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\MediaFieldValueSupport;
use Moox\Builder\Support\OptionValueRules;
use Moox\Builder\Support\RichTextValue;

class FieldValueValidator
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public function messagesFor(FieldDefinition $field, mixed $value, ?string $path = null): array
    {
        $path ??= $field->name;

        $fieldType = $this->fieldTypeRegistry->get($field->type);

        if ($field->type === 'tab') {
            return [];
        }

        if ($fieldType->isLayoutMarker()) {
            return [];
        }

        if ($fieldType->hasSubFields()) {
            return $this->messagesForCompound($field, $value, $path);
        }

        return $this->messagesForLeaf($field, $value, $path);
    }

    public function assertValid(FieldDefinition $field, mixed $value): void
    {
        $messages = $this->messagesFor($field, $value);

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForCompound(FieldDefinition $field, mixed $value, string $path): array
    {
        if ($field->type === 'group') {
            return $this->messagesForRow($field, $this->normalizeGroupRow($value), $path);
        }

        if ($field->type === 'flexible_content') {
            return $this->messagesForFlexibleContent($field, $value, $path);
        }

        if (! is_array($value)) {
            return [];
        }

        $messages = [];

        foreach (array_values($value) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemPath = "{$path}.{$index}";

            if ($this->isRowEmpty($field, $item)) {
                $messages[$itemPath] = [
                    __('builder::builder.validation.empty_repeater_item', [
                        'position' => $index + 1,
                        'field' => $field->label,
                    ]),
                ];

                continue;
            }

            $messages = array_merge($messages, $this->messagesForRow($field, $item, $itemPath));
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForFlexibleContent(FieldDefinition $field, mixed $value, string $path): array
    {
        if (! is_array($value)) {
            return [];
        }

        $messages = [];

        foreach (array_values($value) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $layoutKey = (string) ($item['type'] ?? $item['layout'] ?? '');
            $data = is_array($item['data'] ?? null) ? $item['data'] : $item;
            unset($data['type'], $data['layout'], $data['data']);

            $itemPath = "{$path}.{$index}";

            if ($layoutKey === '') {
                $messages[$itemPath] = [
                    __('builder::builder.validation.empty_flexible_layout'),
                ];

                continue;
            }

            $layout = $field->layouts()->firstWhere('name', $layoutKey);

            if ($layout === null) {
                $messages[$itemPath] = [
                    __('builder::builder.validation.unknown_flexible_layout', ['layout' => $layoutKey]),
                ];

                continue;
            }

            if ($this->isRowEmpty($layout, $data)) {
                $messages[$itemPath] = [
                    __('builder::builder.validation.empty_flexible_item', [
                        'position' => $index + 1,
                        'field' => $field->label,
                    ]),
                ];

                continue;
            }

            $messages = array_merge($messages, $this->messagesForRow($layout, $data, "{$itemPath}.data"));
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForRow(FieldDefinition $parent, array $row, string $path): array
    {
        $messages = [];

        foreach ($parent->children as $child) {
            $childPath = "{$path}.{$child->name}";
            $messages = array_merge(
                $messages,
                $this->messagesFor($child, $row[$child->name] ?? null, $childPath),
            );
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForLeaf(FieldDefinition $field, mixed $value, string $path): array
    {
        $fieldType = $this->fieldTypeRegistry->get($field->type);

        if (! $fieldType->storesValue()) {
            return [];
        }

        if ($field->type === 'link') {
            return $this->messagesForLink($field, $value, $path);
        }

        if ($field->type === 'image') {
            return $this->messagesForImage($field, $value, $path);
        }

        $messages = [];

        if (($field->validation['required'] ?? false) === true && $this->isEmptyValue($field->type, $value)) {
            $messages[$path] = [
                __('validation.required', ['attribute' => $field->label]),
            ];
        }

        if ($this->isEmptyValue($field->type, $value)) {
            return $messages;
        }

        try {
            OptionValueRules::assertValid($field, $value);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $errorMessages) {
                $messages[$key !== 'value' ? $key : $path] = $errorMessages;
            }
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForLink(FieldDefinition $field, mixed $value, string $path): array
    {
        $messages = [];
        $urlPath = "{$path}.url";

        if (($field->validation['required'] ?? false) === true && $this->isEmptyValue('link', $value, true)) {
            $messages[$urlPath] = [
                __('validation.required', ['attribute' => __('builder::builder.link.url')]),
            ];
        }

        if ($this->isEmptyValue('link', $value)) {
            return $messages;
        }

        if (! is_array($value)) {
            return $messages;
        }

        $url = $value['url'] ?? null;

        if (blank($url)) {
            return $messages;
        }

        $validator = Validator::make(
            ['url' => $url],
            ['url' => ['url']],
            ['url.url' => __('builder::builder.validation.invalid_link_url')],
        );

        if ($validator->fails()) {
            $messages[$urlPath] = $validator->errors()->get('url');
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForImage(FieldDefinition $field, mixed $value, string $path): array
    {
        $messages = [];

        if (($field->validation['required'] ?? false) === true && $this->isEmptyValue('image', $value)) {
            $messages[$path] = [
                __('validation.required', ['attribute' => $field->label]),
            ];
        }

        if ($this->isEmptyValue('image', $value)) {
            return $messages;
        }

        $ids = MediaFieldValueSupport::extractIds($value);

        if ($ids === []) {
            $messages[$path] = [
                __('builder::builder.validation.invalid_media'),
            ];

            return $messages;
        }

        foreach ($ids as $mediaId) {
            if (! MediaFieldValueSupport::mediaExists($mediaId)) {
                $messages[$path] = [
                    __('builder::builder.validation.missing_media'),
                ];

                break;
            }
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function isRowEmpty(FieldDefinition $parent, array $row): bool
    {
        foreach ($parent->children as $child) {
            $fieldType = $this->fieldTypeRegistry->get($child->type);

            if (! $fieldType->storesValue()) {
                continue;
            }

            if (! $this->isEmptyValue($child->type, $row[$child->name] ?? null)) {
                return false;
            }
        }

        return true;
    }

    protected function isEmptyValue(string $type, mixed $value, bool $required = false): bool
    {
        if ($type === 'rich_text') {
            return RichTextValue::isEmpty($value);
        }

        if ($type === 'toggle') {
            return $value === null || $value === false;
        }

        if ($type === 'link') {
            if (! is_array($value)) {
                return true;
            }

            if ($required) {
                return blank($value['url'] ?? null);
            }

            return blank($value['url'] ?? null) && blank($value['label'] ?? null);
        }

        if (is_array($value)) {
            return $value === [];
        }

        return blank($value);
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeGroupRow(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if (array_is_list($value) && isset($value[0]) && is_array($value[0])) {
            return $value[0];
        }

        return $value;
    }
}
