<?php

declare(strict_types=1);

namespace Moox\Cache\Support;

use Moox\Cache\Contracts\CacheTarget;
use Moox\Cache\Enums\CacheTargetStatus;

abstract class AbstractCacheTarget implements CacheTarget
{
    public function __construct(
        protected string $targetKey,
        protected string $targetLabel,
        protected ?string $targetDescription = null,
        protected string $targetCategory = 'laravel',
        protected ?string $targetIcon = null,
        protected ?string $targetColor = null,
    ) {
    }

    public function key(): string
    {
        return $this->targetKey;
    }

    public function label(): string
    {
        return $this->targetLabel;
    }

    public function description(): ?string
    {
        return $this->targetDescription;
    }

    public function category(): string
    {
        return $this->targetCategory;
    }

    public function icon(): ?string
    {
        return $this->targetIcon;
    }

    public function color(): ?string
    {
        return $this->targetColor;
    }

    public function status(): CacheTargetStatus
    {
        return CacheTargetStatus::Available;
    }
}
