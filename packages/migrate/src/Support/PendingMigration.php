<?php

declare(strict_types=1);

namespace Moox\Migrate\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class PendingMigration
{
    public function __construct(
        public string $name,
        public string $path,
        public string $origin,
    ) {
    }

    public function label(): string
    {
        $stem = $this->name;

        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $this->name, $matches) === 1) {
            $stem = $matches[1];
        }

        $stem = (string) preg_replace('/_table$/', '', $stem);

        return Str::of($stem)->replace('_', ' ')->trim()->headline()->toString();
    }

    public function datedAt(): ?CarbonImmutable
    {
        if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})/', $this->name, $matches) !== 1) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y_m_d_His', $matches[1]) ?: null;
    }
}
