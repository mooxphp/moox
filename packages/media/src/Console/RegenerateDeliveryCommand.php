<?php

declare(strict_types=1);

namespace Moox\Media\Console;

use Illuminate\Console\Command;
use Moox\Media\Support\MediaDeliveryConversions;

class RegenerateDeliveryCommand extends Command
{
    protected $signature = 'media:regenerate-delivery
                            {--ids=* : Only regenerate these media ids}
                            {--profile=* : Only these delivery profiles (e.g. web,customer_api)}
                            {--only-missing : Only missing delivery conversions (default)}
                            {--force : Regenerate even when conversions already exist}';

    protected $description = 'Regenerate delivery conversions (prefer --ids / --profile; full library only when intentional)';

    public function handle(): int
    {
        $profiles = $this->resolvedProfiles();
        $includeAdminSizes = $profiles === null;
        $conversionNames = $includeAdminSizes
            ? MediaDeliveryConversions::allConversionNames()
            : MediaDeliveryConversions::allConversionNames($profiles);

        if ($conversionNames === []) {
            $this->warn('No delivery conversions configured for the selected profiles.');

            return self::SUCCESS;
        }

        $onlyMissing = ! $this->option('force');
        if ($this->option('only-missing')) {
            $onlyMissing = true;
        }

        $parameters = [
            '--only' => $conversionNames,
        ];

        /** @var list<string|null> $ids */
        $ids = $this->option('ids');
        $filteredIds = array_values(array_filter($ids, static fn (?string $id): bool => filled($id)));
        if ($filteredIds !== []) {
            $parameters['--ids'] = $filteredIds;
        } else {
            $this->warn('No --ids given: scanning the full media library (only-missing unless --force).');
        }

        if ($onlyMissing) {
            $parameters['--only-missing'] = true;
        }

        $scope = $profiles === null ? 'all enabled profiles' : 'profiles: '.implode(', ', $profiles);
        $this->info('Regenerating ('.$scope.'): '.implode(', ', $conversionNames));

        return $this->call('media-library:regenerate', $parameters);
    }

    /**
     * @return list<string>|null Null means all enabled profiles (+ admin sizes via allConversionNames()).
     */
    private function resolvedProfiles(): ?array
    {
        /** @var list<string|null> $raw */
        $raw = $this->option('profile');
        $parts = [];

        foreach ($raw as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            foreach (explode(',', $value) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $parts[] = $part;
                }
            }
        }

        if ($parts === []) {
            return null;
        }

        $valid = [];
        foreach (array_unique($parts) as $profile) {
            if (! MediaDeliveryConversions::profileExists($profile)) {
                $this->warn("Unknown profile [{$profile}] — skipped.");

                continue;
            }

            if (! MediaDeliveryConversions::profileEnabled($profile)) {
                $this->warn("Profile [{$profile}] is disabled — skipped.");

                continue;
            }

            $valid[] = $profile;
        }

        return $valid;
    }
}
