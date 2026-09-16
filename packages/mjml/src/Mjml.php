<?php

declare(strict_types=1);

namespace Moox\Mjml;

use Moox\Mjml\Contracts\MjmlRenderer;
use Moox\Mjml\Enums\ValidationLevel;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Moox\Mjml\Renderers\NodeRenderer;
use Moox\Mjml\Renderers\PhpRenderer;

class Mjml
{
    /**
     * Explicitly set options only. Unset keys keep each engine's own defaults.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    public static function new(): self
    {
        return new self;
    }

    public function keepComments(bool $keepComments = true): self
    {
        $this->options['keepComments'] = $keepComments;

        return $this;
    }

    public function hideComments(): self
    {
        return $this->keepComments(false);
    }

    public function ignoreIncludes(bool $ignoreIncludes = true): self
    {
        $this->options['ignoreIncludes'] = $ignoreIncludes;

        return $this;
    }

    public function beautify(bool $beautify = true): self
    {
        $this->options['beautify'] = $beautify;

        return $this;
    }

    public function minify(bool $minify = true): self
    {
        $this->options['minify'] = $minify;

        return $this;
    }

    public function sidecar(bool $sidecar = true): self
    {
        $this->options['sidecar'] = $sidecar;

        return $this;
    }

    public function validationLevel(ValidationLevel $validationLevel): self
    {
        $this->options['validationLevel'] = $validationLevel->value;

        return $this;
    }

    public function filePath(string $filePath): self
    {
        $this->options['filePath'] = $filePath;

        return $this;
    }

    public function workingDirectory(string $workingDirectory): self
    {
        $this->options['workingDirectory'] = $workingDirectory;

        return $this;
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
     */
    public function convert(string $mjml, array $options = []): MjmlResult
    {
        return $this->renderer()->convert($mjml, $this->mergedOptions($options));
    }

    public function canConvert(string $mjml): bool
    {
        try {
            $this->convert($mjml);
        } catch (CouldNotRenderMjml) {
            return false;
        }

        return true;
    }

    public function canConvertWithoutErrors(string $mjml): bool
    {
        try {
            $result = $this->convert($mjml);
        } catch (CouldNotRenderMjml) {
            return false;
        }

        return ! $result->hasErrors();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function mergedOptions(array $overrides): array
    {
        $merged = array_merge($this->options, $overrides);

        if (isset($merged['validationLevel']) && $merged['validationLevel'] instanceof ValidationLevel) {
            $merged['validationLevel'] = $merged['validationLevel']->value;
        }

        return $merged;
    }

    private function renderer(): MjmlRenderer
    {
        if (config('mjml.use_php_renderer', true)) {
            return new PhpRenderer;
        }

        return new NodeRenderer;
    }
}
