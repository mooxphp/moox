<?php

declare(strict_types=1);

namespace Moox\Cache\Contracts;

use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Data\CacheClearResult;
use Moox\Cache\Enums\CacheTargetStatus;

interface CacheTarget
{
    public function key(): string;

    public function label(): string;

    public function description(): ?string;

    public function category(): string;

    public function icon(): ?string;

    public function color(): ?string;

    public function status(): CacheTargetStatus;

    public function clear(CacheClearRequest $request): CacheClearResult;
}
