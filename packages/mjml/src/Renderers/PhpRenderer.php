<?php

declare(strict_types=1);

namespace Moox\Mjml\Renderers;

use MjmlPHP\Mjml as ShyimMjml;
use Moox\Mjml\Contracts\MjmlRenderer;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Throwable;

class PhpRenderer implements MjmlRenderer
{
    public function __construct(
        private string $mjmlClass = ShyimMjml::class,
    ) {}

    public function toHtml(string $mjml): string
    {
        if (! class_exists($this->mjmlClass)) {
            throw new CouldNotRenderMjml('shyim/mjml-php is required when mjml.use_php_renderer is true.');
        }

        try {
            $result = $this->mjmlClass::render($mjml);

            if (! is_object($result) || ! isset($result->html) || ! is_string($result->html)) {
                throw new CouldNotRenderMjml('shyim/mjml-php did not return HTML.');
            }

            return $result->html;
        } catch (CouldNotRenderMjml $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new CouldNotRenderMjml($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }
}
