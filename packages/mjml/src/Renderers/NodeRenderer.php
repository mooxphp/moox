<?php

declare(strict_types=1);

namespace Moox\Mjml\Renderers;

use Moox\Mjml\Contracts\MjmlRenderer;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Spatie\Mjml\Mjml as SpatieMjml;
use Throwable;

class NodeRenderer implements MjmlRenderer
{
    public function __construct(
        private string $mjmlClass = SpatieMjml::class,
    ) {
    }

    public function toHtml(string $mjml): string
    {
        if (! class_exists($this->mjmlClass)) {
            throw new CouldNotRenderMjml('spatie/mjml-php is required when mjml.use_php_renderer is false.');
        }

        try {
            $html = $this->mjmlClass::new()->toHtml($mjml);

            if (! is_string($html)) {
                throw new CouldNotRenderMjml('spatie/mjml-php did not return HTML.');
            }

            return $html;
        } catch (CouldNotRenderMjml $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new CouldNotRenderMjml($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }
}
