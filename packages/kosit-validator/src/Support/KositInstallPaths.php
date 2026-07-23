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
    ) {
    }

    public static function fromConfig(): self
    {
        return self::fromBasePath((string) config('kosit-validator.base_path'));
    }

    public static function fromBasePath(string $basePath): self
    {
        $validatorDir = InstallerPathSegmentGuard::assertValid(
            (string) config('kosit-validator.paths.validator_dir'),
            'paths.validator_dir',
        );
        $xrechnungDir = InstallerPathSegmentGuard::assertValid(
            (string) config('kosit-validator.paths.xrechnung_dir'),
            'paths.xrechnung_dir',
        );

        return new self(
            $basePath.'/'.$validatorDir,
            $basePath.'/'.$xrechnungDir,
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
