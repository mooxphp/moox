<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

use Moox\Audit\Contracts\ActivityAttributeLabelResolver;

final class TestAttributeLabelResolver implements ActivityAttributeLabelResolver
{
    public function resolveFieldLabel(string $field): ?string
    {
        return match ($field) {
            'gateway_status' => 'Gateway',
            'review_status' => 'Review',
            default => null,
        };
    }

    public function resolveValueLabel(string $field, string $value): ?string
    {
        return match ([$field, $value]) {
            ['gateway_status', 'generation_failed'] => 'Generation failed',
            ['review_status', 'db_validated'] => 'Automatically pre-reviewed',
            ['review_status', 'human_confirmed'] => 'Manually confirmed',
            default => null,
        };
    }
}
