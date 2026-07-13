<?php

declare(strict_types=1);

namespace Moox\Page\Contracts;

interface PageContentRenderer
{
    public function render(array|string|null $content, ?string $locale = null): string;
}
