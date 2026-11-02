<?php

declare(strict_types=1);

namespace Moox\Media\Console;

use Illuminate\Console\Command;
use Moox\Media\Support\ImageDriverResolver;

class ImageDriverCommand extends Command
{
    protected $signature = 'media:image-driver';

    protected $description = 'Show the resolved Spatie image driver and optimizer binary availability';

    public function handle(): int
    {
        $status = ImageDriverResolver::status();

        $this->table(
            ['Key', 'Value'],
            [
                ['resolved driver', $status['driver']],
                ['media.image_driver', $status['preferred']],
                ['vips extension', $status['vips'] ? 'yes' : 'no'],
                ['imagick extension', $status['imagick'] ? 'yes' : 'no'],
                ['gd extension', $status['gd'] ? 'yes' : 'no'],
            ],
        );

        $optimizerRows = [];
        foreach ($status['optimizers'] as $name => $available) {
            $optimizerRows[] = [$name, $available ? 'found' : 'missing'];
        }

        $this->table(['Optimizer binary', 'Status'], $optimizerRows);

        return self::SUCCESS;
    }
}
