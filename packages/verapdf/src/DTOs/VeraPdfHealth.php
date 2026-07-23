<?php

declare(strict_types=1);

namespace Moox\VeraPdf\DTOs;

final readonly class VeraPdfHealth
{
    public function __construct(
        public bool $javaAvailable,
        public ?string $launcherPath,
        public ?string $launcherError,
        public bool $installed,
        public bool $cliBinariesPresent,
        public bool $guiArtefactsPresent,
        public string $outputPath,
        public bool $outputPathWritable,
    ) {
    }

    public function isHealthy(): bool
    {
        return $this->javaAvailable
            && $this->launcherPath !== null
            && $this->installed
            && $this->cliBinariesPresent;
    }
}
