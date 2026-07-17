<?php

declare(strict_types=1);

namespace Moox\EBilling\Formats;

use Moox\EBilling\Formats\Contracts\GeneratorStrategyInterface;

readonly class FormatDefinition
{
    public function __construct(
        public string $id,
        public string $label,
        public ArtifactKind $artifactKind,
        public string $profile,
        public GeneratorStrategyInterface $strategy,
    ) {}
}
