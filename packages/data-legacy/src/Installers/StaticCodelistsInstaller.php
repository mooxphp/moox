<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Installers;

use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;
use Moox\DataLegacy\Services\ImportCodelistsService;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

class StaticCodelistsInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'static-codelists';
    }

    public function getLabel(): string
    {
        return 'Static codelists (UNCL / UNTDID / Incoterms)';
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        if (! Schema::hasTable('static_charge_reasons')) {
            return false;
        }

        return \DB::table('static_charge_reasons')->exists();
    }

    public function install(array $assets): bool
    {
        if (! Schema::hasTable('static_charge_reasons')) {
            note('ℹ️ Table static_charge_reasons not found. Run migrations first (data package).');

            return false;
        }

        try {
            note('📋 Importing static codelists from committed JSON …');

            $count = (new ImportCodelistsService)->import();

            note("✅ Static codelists import completed ({$count} rows upserted).");

            return true;
        } catch (\Throwable $e) {
            error('⚠️ Static codelists import failed: '.$e->getMessage());

            return false;
        }
    }
}
