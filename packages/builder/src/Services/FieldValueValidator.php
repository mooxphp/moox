<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\MediaFieldValueSupport;
use Moox\Builder\Support\OptionValueRules;
use Moox\Builder\Support\RelationValueRules;
use Moox\Builder\Support\RichTextValue;

class FieldValueValidator
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public function messagesFor(FieldDefinition $field, mixed $value, ?string $path = null, ?Model $record = null): array
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
            return $this->messagesForCompound($field, $value, $path, $record);
        }

        return $this->messagesForLeaf($field, $value, $path, $record);
    }

    public function assertValid(FieldDefinition $field, mixed $value, ?Model $record = null): void
    {
        $messages = $this->messagesFor($field, $value, record: $record);

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForCompound(FieldDefinition $field, mixed $value, string $path, ?Model $record = null): array
    {
        if ($field->type === 'group') {
            return $this->messagesForRow($field, $this->normalizeGroupRow($value), $path, $record);
        }

        if ($field->type === 'flexible_content') {
            return $this->messagesForFlexibleContent($field, $value, $path, $record);
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

            $messages = array_merge($messages, $this->messagesForRow($field, $item, $itemPath, $record));
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForFlexibleContent(FieldDefinition $field, mixed $value, string $path, ?Model $record = null): array
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

            $messages = array_merge($messages, $this->messagesForRow($layout, $data, "{$itemPath}.data", $record));
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForRow(FieldDefinition $parent, array $row, string $path, ?Model $record = null): array
    {
        $messages = [];

        foreach ($parent->children as $child) {
            $childPath = "{$path}.{$child->name}";
            $messages = array_merge(
                $messages,
                $this->messagesFor($child, $row[$child->name] ?? null, $childPath, $record),
            );
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForLeaf(FieldDefinition $field, mixed $value, string $path, ?Model $record = null): array
    {
        $fieldType = $this->fieldTypeRegistry->get($field->type);

        if (! $fieldType->storesValue()) {
            return [];
        }

        if ($field->type === 'link') {
            return $this->messagesForLink($field, $value, $path);
        }

        if ($field->type === 'image') {
            return $this->messagesForImage($field, $value, $path, $record);
        }

        if ($field->type === 'gallery') {
            return $this->messagesForGallery($field, $value, $path, $record);
        }

        if ($field->type === 'file') {
            return $this->messagesForFile($field, $value, $path, $record);
        }

        if ($field->type === 'relation') {
            return RelationValueRules::messages($field, $value, $path);
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
    protected function messagesForImage(FieldDefinition $field, mixed $value, string $path, ?Model $record = null): array
    {
        return $this->messagesForMediaField($field, $value, $path, 'image', $record);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForFile(FieldDefinition $field, mixed $value, string $path, ?Model $record = null): array
    {
        return $this->messagesForMediaField($field, $value, $path, 'file', $record);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForGallery(FieldDefinition $field, mixed $value, string $path, ?Model $record = null): array
    {
        $messages = $this->messagesForMediaField($field, $value, $path, 'gallery', $record);

        if ($messages !== [] || $this->isEmptyValue('gallery', $value)) {
            return $messages;
        }

        $ids = MediaFieldValueSupport::extractIds($value);
        $count = count($ids);
        $min = $this->normalizeFileLimit($field->config['min_files'] ?? null);
        $max = $this->normalizeFileLimit($field->config['max_files'] ?? null);

        if ($min === null && ($field->validation['required'] ?? false) === true) {
            $min = 1;
        }

        if ($min !== null && $count < $min) {
            $messages[$path] = [
                __('builder::builder.validation.gallery_min_files', [
                    'min' => $min,
                    'attribute' => $field->label,
                ]),
            ];
        }

        if ($max !== null && $count > $max) {
            $messages[$path] = [
                __('builder::builder.validation.gallery_max_files', [
                    'max' => $max,
                    'attribute' => $field->label,
                ]),
            ];
        }

        return $messages;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function messagesForMediaField(FieldDefinition $field, mixed $value, string $path, string $type, ?Model $record = null): array
    {
        $messages = [];

        if (($field->validation['required'] ?? false) === true && $this->isEmptyValue($type, $value)) {
            $messages[$path] = [
                __('validation.required', ['attribute' => $field->label]),
            ];
        }

        if ($this->isEmptyValue($type, $value)) {
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
            $result = MediaFieldValueSupport::mediaValidationResult($mediaId, $type, $record);

            if ($result['valid']) {
                continue;
            }

            $messages[$path] = match ($result['reason']) {
                'invalid_type' => [
                    __('builder::builder.validation.invalid_media_type', ['attribute' => $field->label]),
                ],
                'invalid_file_type' => [
                    __('builder::builder.validation.invalid_file_media_type', ['attribute' => $field->label]),
                ],
                'scope_mismatch' => [
                    __('builder::builder.validation.media_scope_mismatch', ['attribute' => $field->label]),
                ],
                default => [
                    __('builder::builder.validation.missing_media'),
                ],
            };

            break;
        }

        return $messages;
    }

    protected function normalizeFileLimit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $limit = (int) $value;

        return $limit > 0 ? $limit : null;
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

        if (in_array($type, ['image', 'gallery', 'file'], true)) {
            return MediaFieldValueSupport::extractIds($value) === [];
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
