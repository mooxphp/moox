<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Filament\Concerns;

use Moox\BlockEditor\Support\TemplateContentSanitizer;

trait InteractsWithTemplateForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeTemplateFormData(array $data): array
    {
        $content = $data['content'] ?? null;

        $data['content'] = $this->decodeJsonFieldToArray($content);

        if (is_array($data['content'] ?? null)) {
            /** @var TemplateContentSanitizer $sanitizer */
            $sanitizer = app(TemplateContentSanitizer::class);
            $data['content'] = $sanitizer->sanitizeBlocks($data['content']);
        }

        return $data;
    }

    private function decodeJsonFieldToArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
