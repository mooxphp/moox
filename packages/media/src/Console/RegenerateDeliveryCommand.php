<?php

declare(strict_types=1);

namespace Moox\Media\Console;

use Illuminate\Console\Command;
use Moox\Media\Support\MediaDeliveryConversions;

class RegenerateDeliveryCommand extends Command
{
    protected $signature = 'media:regenerate-delivery
                            {--ids=* : Only regenerate these media ids}
                            {--only-missing : Only missing delivery conversions (default)}
                            {--force : Regenerate even when conversions already exist}';

    protected $description = 'Regenerate AVIF/WebP/JPG delivery conversions for media';

    public function handle(): int
    {
        $conversionNames = MediaDeliveryConversions::allConversionNames();

        if ($conversionNames === []) {
            $this->warn('No delivery conversions configured.');

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
        }

        if ($onlyMissing) {
            $parameters['--only-missing'] = true;
        }

        $this->info('Regenerating delivery conversions: '.implode(', ', $conversionNames));

        return $this->call('media-library:regenerate', $parameters);
    }
}
