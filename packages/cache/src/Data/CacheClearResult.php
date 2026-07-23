<?php

declare(strict_types=1);

namespace Moox\Cache\Data;

final readonly class CacheClearResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $output = null,
        public float $durationMs = 0,
    ) {
    }
}
