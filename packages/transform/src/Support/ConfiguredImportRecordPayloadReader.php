<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Support\Facades\Config;
use Moox\Transform\Contracts\ImportRecordPayloadReader;

final class ConfiguredImportRecordPayloadReader implements ImportRecordPayloadReader
{
    public function read(int $importRecordId): array
    {
        if (app()->bound(ImportRecordPayloadReader::class)) {
            return app(ImportRecordPayloadReader::class)->read($importRecordId);
        }

        $reader = $this->resolveReader();

        return $reader->read($importRecordId);
    }

    private function resolveReader(): ImportRecordPayloadReader
    {
        $class = Config::get('transform.import_record_payload_reader');

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            throw new \RuntimeException(
                'Import record payload reading is not configured. Set transform.import_record_payload_reader in application config.'
            );
        }

        $reader = app($class);

        if (! $reader instanceof ImportRecordPayloadReader) {
            throw new \RuntimeException(
                "Configured import record payload reader [{$class}] must implement ".ImportRecordPayloadReader::class.'.'
            );
        }

        return $reader;
    }
}
