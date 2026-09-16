<?php

declare(strict_types=1);

namespace Moox\Mjml\Renderers;

use Moox\Mjml\Contracts\MjmlRenderer;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Moox\Mjml\MjmlResult;
use Spatie\Mjml\Mjml as SpatieMjml;
use Throwable;

class NodeRenderer implements MjmlRenderer
{
    public function __construct(
        private string $mjmlClass = SpatieMjml::class,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function convert(string $mjml, array $options = []): MjmlResult
    {
        if (! class_exists($this->mjmlClass)) {
            throw new CouldNotRenderMjml('spatie/mjml-php is required when mjml.use_php_renderer is false.');
        }

        try {
            $engine = $this->mjmlClass::new();

            if (array_key_exists('sidecar', $options)) {
                $engine->sidecar((bool) $options['sidecar']);
            }

            if (array_key_exists('workingDirectory', $options) && is_string($options['workingDirectory'])) {
                $engine->workingDirectory($options['workingDirectory']);
            }

            $result = $engine->convert($mjml, $this->cliOptions($options));

            if (! is_object($result) || ! method_exists($result, 'raw')) {
                throw new CouldNotRenderMjml('spatie/mjml-php did not return a conversion result.');
            }

            $raw = $result->raw();

            if (! is_array($raw)) {
                throw new CouldNotRenderMjml('spatie/mjml-php did not return a conversion result.');
            }

            return new MjmlResult($raw);
        } catch (CouldNotRenderMjml $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new CouldNotRenderMjml($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function toHtml(string $mjml, array $options = []): string
    {
        return $this->convert($mjml, $options)->html();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function cliOptions(array $options): array
    {
        unset($options['sidecar'], $options['workingDirectory']);

        if (isset($options['validationLevel']) && $options['validationLevel'] instanceof \BackedEnum) {
            $options['validationLevel'] = $options['validationLevel']->value;
        }

        return $options;
    }
}
