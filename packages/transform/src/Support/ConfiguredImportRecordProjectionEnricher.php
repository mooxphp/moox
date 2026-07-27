<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Support\Facades\Config;
use Moox\Transform\Contracts\ImportRecordProjectionEnricher;

final class ConfiguredImportRecordProjectionEnricher
{
    /**
     * @param  array<string, mixed>  $projection
     * @return array<string, mixed>
     */
    public function enrich(int $importRecordId, array $projection): array
    {
        $class = Config::get('transform.import_record_projection_enricher');

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return $projection;
        }

        $enricher = app($class);

        if (! $enricher instanceof ImportRecordProjectionEnricher) {
            throw new \RuntimeException(
                "Configured import record projection enricher [{$class}] must implement ".ImportRecordProjectionEnricher::class.'.'
            );
        }

        return $enricher->enrich($importRecordId, $projection);
    }
}
