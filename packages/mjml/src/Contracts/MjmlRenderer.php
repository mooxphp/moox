<?php

declare(strict_types=1);

namespace Moox\Mjml\Contracts;

interface MjmlRenderer
{
    public function toHtml(string $mjml): string;
}
