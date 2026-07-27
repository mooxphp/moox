<?php

declare(strict_types=1);

namespace Moox\Transform\Contracts;

interface ImportRecordProjectionEnricher
{
    /**
     * @param  array<string, mixed>  $projection
     * @return array<string, mixed>
     */
    public function enrich(int $importRecordId, array $projection): array;
}
