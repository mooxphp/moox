<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Http\Requests\Concerns;

use Moox\BlockEditor\Support\TemplateContentSanitizer;

trait SanitizesTemplatePayload
{
    protected function passedValidation(): void
    {
        /** @var TemplateContentSanitizer $sanitizer */
        $sanitizer = app(TemplateContentSanitizer::class);
        $payload = [];

        if ($this->exists('content')) {
            $content = $this->input('content');
            $payload['content'] = is_array($content) ? $sanitizer->sanitizeBlocks($content) : null;
        }

        if ($this->exists('name') && is_string($this->input('name'))) {
            $payload['name'] = trim(strip_tags($this->input('name')));
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * Ensure sanitized merge values are returned (Laravel validated() ignores post-validation merge).
     *
     * @param  array-key|null  $key
     * @param  mixed  $default
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function validated($key = null, $default = null): mixed
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated();

        if (array_key_exists('content', $validated) && $this->exists('content')) {
            $content = $this->input('content');
            $validated['content'] = is_array($content) ? $content : null;
        }

        if (array_key_exists('name', $validated) && is_string($this->input('name'))) {
            $validated['name'] = $this->input('name');
        }

        if ($key !== null) {
            return data_get($validated, $key, $default);
        }

        return $validated;
    }
}
