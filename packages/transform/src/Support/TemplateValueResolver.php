<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Support\Arr;

final class TemplateValueResolver
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function resolve(mixed $value, array $context): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        if (preg_match('/^\{\{\s*(?<path>[^}]+)\s*\}\}$/', $trimmed, $matches) !== 1) {
            return $value;
        }

        $resolved = Arr::get($context, trim($matches['path']));

        return $resolved ?? $value;
    }
}
