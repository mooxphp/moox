<?php

namespace Moox\Page\Resources\Concerns;

trait InteractsWithBlockEditorContent
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeBlockEditorFormData(array $data): array
    {
        if (array_key_exists('content', $data)) {
            $data['content'] = $this->decodeBlockEditorContent($data['content']);
        }

        return $data;
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeBlockEditorContent(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
