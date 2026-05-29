<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Moox\Localization\Models\Localization;

trait ReportsMooxSeederProgress
{
    use SeedsMooxDemoRelations;

    protected function hasSeedOutput(): bool
    {
        return class_exists(SeedOutput::class) && SeedOutput::isBound();
    }

    protected function reportCreated(string $label): void
    {
        if ($this->hasSeedOutput()) {
            SeedOutput::created($label);

            return;
        }

        if ($this->command?->getOutput()->isVerbose()) {
            $this->command->line("  + {$label}");
        }
    }

    protected function reportDetail(string $line): void
    {
        if ($this->hasSeedOutput()) {
            SeedOutput::detail($line);

            return;
        }

        $this->command?->info($line);
    }

    /**
     * @param  list<string>  $locales
     */
    protected function assertRequiredLocalizations(array $locales): bool
    {
        if (! class_exists(Localization::class)) {
            return true;
        }

        $missing = collect($locales)
            ->filter(
                fn (string $locale): bool => ! Localization::query()
                    ->where('locale_variant', $locale)
                    ->exists()
            );

        if ($missing->isEmpty()) {
            return true;
        }

        $this->command?->error(
            'Missing `localizations` rows for: '.$missing->implode(', ').
            '. Run moox:demo or add those locale_variant values before running this seeder.'
        );

        return false;
    }
}
