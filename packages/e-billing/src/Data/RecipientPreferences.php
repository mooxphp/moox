<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

/**
 * A FormatRegistry format id with an optional hybrid profile override, and the visual-copy choice.
 * Null means "no preference": the configured default applies. A profile without a format is ignored;
 * for xrechnung the profile must be null (the format resolver throws otherwise).
 */
final readonly class RecipientPreferences
{
    public function __construct(
        public ?string $format = null,
        public ?string $profile = null,
        public ?bool $sendVisualCopy = null,
    ) {
    }
}
