<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

/**
 * UI + default selection for selective redispatch (ADR 0008).
 */
final readonly class SelectiveRedispatchPlan
{
    /**
     * @param  list<string>  $channelKeys
     * @param  list<string>  $defaultSelectedKeys
     * @param  list<string>  $successfulKeys
     * @param  array<string, string>  $labels
     * @param  array<string, string>  $hints
     */
    public function __construct(
        public array $channelKeys,
        public array $defaultSelectedKeys,
        public array $successfulKeys,
        public array $labels,
        public array $hints,
    ) {
    }

    /**
     * @param  list<string>  $selected
     * @return list<string>
     */
    public function warningKeys(array $selected): array
    {
        return array_values(array_intersect($selected, $this->successfulKeys));
    }
}
