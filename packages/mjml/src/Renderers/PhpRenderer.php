<?php

declare(strict_types=1);

namespace Moox\Mjml\Renderers;

use MjmlPHP\Mjml as ShyimMjml;
use MjmlPHP\MjmlOptions as ShyimMjmlOptions;
use MjmlPHP\Validation\ValidationLevel as ShyimValidationLevel;
use Moox\Mjml\Contracts\MjmlRenderer;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Moox\Mjml\MjmlResult;
use Throwable;

class PhpRenderer implements MjmlRenderer
{
    public function __construct(
        private string $mjmlClass = ShyimMjml::class,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function convert(string $mjml, array $options = []): MjmlResult
    {
        if (array_key_exists('sidecar', $options)) {
            throw new CouldNotRenderMjml('sidecar() is only available when mjml.use_php_renderer is false.');
        }

        if (array_key_exists('workingDirectory', $options)) {
            throw new CouldNotRenderMjml('workingDirectory() is only available when mjml.use_php_renderer is false.');
        }

        if (! class_exists($this->mjmlClass)) {
            throw new CouldNotRenderMjml('shyim/mjml-php is required when mjml.use_php_renderer is true.');
        }

        try {
            $result = $this->mjmlClass::render($mjml, $this->shyimOptions($options));

            if (! is_object($result) || ! isset($result->html) || ! is_string($result->html)) {
                throw new CouldNotRenderMjml('shyim/mjml-php did not return HTML.');
            }

            return new MjmlResult([
                'html' => $result->html,
                'json' => [],
                'errors' => $this->phpErrors($result),
            ]);
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
     */
    private function shyimOptions(array $options): ?ShyimMjmlOptions
    {
        if ($options === []) {
            return null;
        }

        if (! class_exists(ShyimMjmlOptions::class) || ! enum_exists(ShyimValidationLevel::class)) {
            throw new CouldNotRenderMjml('shyim/mjml-php is required when mjml.use_php_renderer is true.');
        }

        $validationLevel = ShyimValidationLevel::Strict;

        if (isset($options['validationLevel'])) {
            $value = $options['validationLevel'] instanceof \BackedEnum
                ? $options['validationLevel']->value
                : $options['validationLevel'];

            $validationLevel = ShyimValidationLevel::from((string) $value);
        }

        $filePath = $options['filePath'] ?? null;

        return new ShyimMjmlOptions(
            validationLevel: $validationLevel,
            minify: (bool) ($options['minify'] ?? false),
            beautify: (bool) ($options['beautify'] ?? false),
            keepComments: (bool) ($options['keepComments'] ?? true),
            filePath: is_string($filePath) ? $filePath : null,
            ignoreIncludes: (bool) ($options['ignoreIncludes'] ?? true),
        );
    }

    /**
     * @return list<array{line: int, message: string, tagName: string}>
     */
    private function phpErrors(object $result): array
    {
        if (! isset($result->errors) || ! is_array($result->errors)) {
            return [];
        }

        $mapped = [];

        foreach ($result->errors as $error) {
            if (is_object($error)) {
                $mapped[] = [
                    'line' => (int) ($error->line ?? 0),
                    'message' => (string) ($error->message ?? $error),
                    'tagName' => (string) ($error->tagName ?? ''),
                ];

                continue;
            }

            if (is_array($error)) {
                $mapped[] = [
                    'line' => (int) ($error['line'] ?? 0),
                    'message' => (string) ($error['message'] ?? ''),
                    'tagName' => (string) ($error['tagName'] ?? ''),
                ];
            }
        }

        return $mapped;
    }
}
