<?php

declare(strict_types=1);

namespace Moox\Data\Console\Commands;

use Illuminate\Console\Command;
use Moox\Data\Models\StaticLocale;

class UpdateLocaleFlagCountryCodes extends Command
{
    protected $signature = 'moox:data:update-locale-flags';

    protected $description = 'Update flag_country_code for all static locales';

    public function handle(): void
    {
        $this->info('Updating flag country codes for locales...');

        $locales = StaticLocale::all();
        $count = 0;

        foreach ($locales as $locale) {
            if ($locale->country?->alpha2) {
                $locale->flag_country_code = $locale->country->alpha2;
                $locale->save();
                $count++;
            }
        }

        $this->info("Updated {$count} locales with flag country codes.");
    }
}
