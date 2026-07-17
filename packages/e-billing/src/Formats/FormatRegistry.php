<?php

declare(strict_types=1);

namespace Moox\EBilling\Formats;

use Moox\EBilling\Formats\Exceptions\UnknownFormatException;

final class FormatRegistry
{
    /** @var array<string, FormatDefinition> */
    private array $formats = [];

    public function register(FormatDefinition $definition): void
    {
        $this->formats[$definition->id] = $definition;
    }

    public function has(string $formatId): bool
    {
        return isset($this->formats[$formatId]);
    }

    public function get(string $formatId): FormatDefinition
    {
        if (! $this->has($formatId)) {
            throw new UnknownFormatException("Unknown e-billing format [{$formatId}].");
        }

        return $this->formats[$formatId];
    }
}
