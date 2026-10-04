<?php

declare(strict_types=1);

namespace Moox\Definition\Resolution;

final class FieldOrigin
{
    public function __construct(
        private readonly string $source,
        private readonly ?string $feature,
    ) {
    }

    public static function domain(): self
    {
        return new self('domain', null);
    }

    public static function feature(string $feature): self
    {
        return new self('feature', $feature);
    }

    public function source(): string
    {
        return $this->source;
    }

    public function featureKey(): ?string
    {
        return $this->feature;
    }

    public function isDomain(): bool
    {
        return $this->source === 'domain';
    }
}
