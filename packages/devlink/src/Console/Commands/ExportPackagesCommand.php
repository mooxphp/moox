<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;

class ExportPackagesCommand extends Command
{
    protected $signature = 'moox:devlink-export {--path= : Override the export path from config}';
    protected $description = 'Write the active devlink package folders to a file (for CI/deploy)';

    public function handle(): int
    {
        $path = $this->option('path')
            ?? config('devlink.export_path', '.github/moox-packages.txt');

        // In config/devlink.php the array KEY is the folder name (e.g. "address").
        // Export only active packages that live in the monorepo: type "public".
        // This excludes bundles (meta packages, no folder), "private" (from Satis)
        // and "local" (your own committed packages).
        $folders = collect(config('devlink.packages', []))
            ->filter(fn ($cfg) => ($cfg['active'] ?? false) === true
                               && ($cfg['type'] ?? null) === 'public')
            ->keys()
            ->sort()
            ->values();

        $target = base_path($path);
        @mkdir(dirname($target), 0755, true);
        file_put_contents($target, $folders->implode(' ') . PHP_EOL);

        $this->info($folders->count() . " active packages → {$path}");
        $this->line($folders->implode(' '));

        return self::SUCCESS;
    }
}
