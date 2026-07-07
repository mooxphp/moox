<?php

declare(strict_types=1);

namespace Moox\Transform\Tests\Support;

use Moox\Transform\Contracts\ImportRecordPayloadReader;

final class FakeImportRecordPayloadReader implements ImportRecordPayloadReader
{
    /** @var array<int, array<mixed>> */
    public static array $payloads = [];

    public function read(int $importRecordId): array
    {
        return self::$payloads[$importRecordId] ?? [];
    }
}
