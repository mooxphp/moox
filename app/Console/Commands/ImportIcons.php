<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Icon;
use App\Models\IconSet;
use BladeUI\Icons\Factory;
use BladeUI\Icons\IconsManifest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

final class ImportIcons extends Command
{
    protected $signature = 'icons:import';

    protected $description = 'Import all icons from every icon set into the database.';

    private array $sets;

    private array $icons;

    public function handle(Factory $factory, IconsManifest $manifest): int
    {
        echo $this->getLaravel()->bootstrapPath('cache/tallui-web-icons.php');

        if (File::missing($this->getLaravel()->bootstrapPath('cache/tallui-web-icons.php'))) {
            $this->error('Please cache the icons first by running `php artisan icon:cache`.');

            return 1;
        }

        $this->sets = $factory->all();
        $this->icons = $manifest->getManifest($this->sets);

        unset($this->sets['default']);

        $this->info('Starting to import icon sets...');

        DB::transaction(function () {
            Icon::withoutSyncingToSearch(function () {
                Icon::query()->delete();
            });

            IconSet::each(function (IconSet $iconSet) {
                $this->parseIcons($iconSet);
            });
        });

        $this->info('Successfully imported '.IconSet::count().' icon sets!');

        return 0;
    }

    private function parseIcons(IconSet $iconSet): void
    {
        Icon::withoutSyncingToSearch(function () use ($iconSet) {
            $set = $this->sets[$iconSet->name];

            foreach ($this->icons[$iconSet->name] ?? [] as $icons) {
                foreach ($icons as $icon) {
                    Icon::create([
                        'icon_set_id' => $iconSet->id,
                        'name' => $set['prefix'].'-'.$icon,
                        'outlined' => $this->isOutlined($icon, $iconSet->outline_rule),
                        'keywords' => $this->keywords($icon, $iconSet->ignore_rule),
                    ]);
                }
            }
        });
    }

    private function keywords(string $string, ?string $rule): array
    {
        return Str::of($string)->when($rule !== null, function (Stringable $string) use ($rule) {
            return $string->replaceMatches($rule, '');
        })->explode('-')->filter()->toArray();
    }

    private function isOutlined(string $string, ?string $rule): bool
    {
        if ($rule === null) {
            return false;
        }

        return Str::of($string)->match($rule)->isNotEmpty();
    }
}
