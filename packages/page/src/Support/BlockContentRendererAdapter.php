<?php

declare(strict_types=1);

namespace Moox\Page\Support;

use Moox\BlockEditor\Rendering\BlockContentRenderer;
use Moox\Page\Contracts\PageContentRenderer;

class BlockContentRendererAdapter implements PageContentRenderer
{
    public function __construct(
        private readonly BlockContentRenderer $renderer,
    ) {}

    public function render(array|string|null $content, ?string $locale = null): string
    {
        return $this->renderer->render($content, $locale);
    }
}
