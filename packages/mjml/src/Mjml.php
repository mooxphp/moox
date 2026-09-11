<?php

declare(strict_types=1);

namespace Moox\Mjml;

use Moox\Mjml\Contracts\MjmlRenderer;
use Moox\Mjml\Renderers\NodeRenderer;
use Moox\Mjml\Renderers\PhpRenderer;

class Mjml
{
    public static function new(): self
    {
        return new self;
    }

    public function toHtml(string $mjml): string
    {
        return $this->renderer()->toHtml($mjml);
    }

    private function renderer(): MjmlRenderer
    {
        if (config('mjml.use_php_renderer', true)) {
            return new PhpRenderer;
        }

        return new NodeRenderer;
    }
}
