<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Traits;

trait GeneratorTrait
{
    protected function replaceTemplateVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{ $key }}", $value, $template);
        }

        return $template;
    }

    protected function loadStub(string $name): string
    {
        return file_get_contents(__DIR__."/../Templates/{$name}.php.stub");
    }

    protected function mergeUniqueArrays(array ...$arrays): array
    {
        return array_unique(array_merge(...$arrays));
    }

    protected function collectFromBlocks(array $blocks, string $method): array
    {
        return array_map(fn ($block) => $block->$method(), $blocks);
    }

    protected function collectFromFeatures(array $features, string $method): array
    {
        return array_map(fn ($feature) => $feature->$method(), $features);
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
    }

    protected function writeFile(string $path, string $content): void
    {
        $this->ensureDirectoryExists($path);
        file_put_contents($path, $content);
    }
}
