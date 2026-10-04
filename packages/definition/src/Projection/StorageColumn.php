<?php

declare(strict_types=1);

namespace Moox\Definition\Projection;

final class StorageColumn
{
    public function __construct(
        private readonly StorageKind $kind,
        private readonly ?int $length = null,
        private readonly ?int $precision = null,
        private readonly ?int $scale = null,
    ) {
    }

    public function kind(): StorageKind
    {
        return $this->kind;
    }

    public function length(): ?int
    {
        return $this->length;
    }

    public function precision(): ?int
    {
        return $this->precision;
    }

    public function scale(): ?int
    {
        return $this->scale;
    }
}
