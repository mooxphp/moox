<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Rendering\Contracts;

use Moox\BlockEditor\Rendering\RenderContext;

interface BlockRenderer
{
    public function supports(string $type): bool;

    /**
     * @param  array<string, mixed>  $block
     */
    public function render(array $block, RenderContext $context): string;
}
