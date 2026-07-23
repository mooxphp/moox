<?php

declare(strict_types=1);

namespace Moox\Demo\Demo;

final class DemoContext
{
    /**
     * @param  list<string>  $locales
     */
    public function __construct(
        public readonly int $languageCount,
        public readonly array $locales,
        public readonly string $dataset,
        public readonly int $datasetCount,
        public readonly bool $fresh,
        public readonly bool $skipSeeders,
        public readonly bool $skipFactories,
        public readonly bool $skipMedia,
    ) {
    }
}
