<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

/**
 * Resolved filesystem paths for a KoSIT validator + XRechnung install layout.
 */
final readonly class KositInstallPaths
{
    public function __construct(
        public string $validatorDir,
        public string $xrechnungDir,
    ) {}

    public static function fromBasePath(string $basePath): self
    {
        return new self(
            $basePath.'/'.config('kosit-validator.paths.validator_dir'),
            $basePath.'/'.config('kosit-validator.paths.xrechnung_dir'),
        );
    }

    /**
     * @return list<string>
     */
    public function directories(): array
    {
        return [$this->validatorDir, $this->xrechnungDir];
    }
}
