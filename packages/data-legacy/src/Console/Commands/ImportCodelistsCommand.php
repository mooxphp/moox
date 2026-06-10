<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Console\Commands;

use Illuminate\Console\Command;
use Moox\DataLegacy\Services\ImportCodelistsService;

class ImportCodelistsCommand extends Command
{
    protected $signature = 'moox:data-legacy:import-codelists {scheme? : Import a single scheme (e.g. uncl7161)}';

    protected $description = 'Import committed codelist JSON files into static_* tables';

    public function handle(ImportCodelistsService $importer): int
    {
        $scheme = $this->argument('scheme');
        $scheme = is_string($scheme) && $scheme !== '' ? $scheme : null;

        $this->info('Starting codelist import...');

        try {
            $count = $importer->import($scheme);
        } catch (\Throwable $e) {
            $this->error('Codelist import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Codelist import completed ({$count} rows upserted).");

        return self::SUCCESS;
    }
}
