<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

/**
 * Resolves deterministic KoSIT validator artifact filenames from configuration.
 */
final class KositValidatorArtifact
{
    public static function expectedJarFilename(): string
    {
        return 'validator-'.config('kosit-validator.validator.version').'-standalone.jar';
    }
}
