<?php

declare(strict_types=1);

namespace Moox\Transform\Contracts;

interface ImportRecordPayloadReader
{
    /**
     * @return array<mixed>
     */
    public function read(int $importRecordId): array;
}
