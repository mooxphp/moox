<?php

declare(strict_types=1);

namespace Moox\Cache\Data;

final class CacheKey
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $description = null,
    ) {
    }

    public static function make(string $key): self
    {
        return new self(
            key: $key,
            label: $key,
        );
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
