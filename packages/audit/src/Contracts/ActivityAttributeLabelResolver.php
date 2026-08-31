<?php

declare(strict_types=1);

namespace Moox\Audit\Contracts;

interface ActivityAttributeLabelResolver
{
    /**
     * Human-readable field name, or null to fall back to the default headline.
     */
    public function resolveFieldLabel(string $field): ?string;

    /**
     * Human-readable attribute value, or null to fall back to formatValue().
     */
    public function resolveValueLabel(string $field, string $value): ?string;
}
