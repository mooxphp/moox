<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Rendering;

final class RenderContext
{
    public function __construct(
        public readonly string $locale,
    ) {}
}
