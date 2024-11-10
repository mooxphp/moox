<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeleteEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:delete-entity {name} {--force} {--package=} {--app}';

    protected $description = 'Delete an entity and its files';

    public function handle(): void
    {
        $name = $this->argument('name');
        $force = $this->option('force');
        $package = $this->option('package');
        $buildContext = $package ? 'package' : ($this->option('app') ? 'app' : 'preview');

        $entity = DB::table('builder_entities')
            ->where('singular', $name)
            ->where('build_context', $buildContext)
            ->whereNull('deleted_at')
            ->first();

        if (! $entity) {
            $this->error("No entity named '{$name}' found in {$buildContext} context.");

            return;
        }

        $latestBuild = DB::table('builder_entity_builds')
            ->where('entity_id', $entity->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $force && $buildContext !== 'preview') {
            if (! $this->confirm("Are you sure you want to delete the {$buildContext} entity '{$name}'?")) {
                return;
            }
        }

        if ($latestBuild) {
            $files = json_decode($latestBuild->files, true);
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                    $this->info("Deleted file: {$file}");
                }
            }

            $tableName = Str::plural(Str::snake($name));
            if (Schema::hasTable($tableName)) {
                if ($buildContext === 'preview') {
                    Schema::dropIfExists($tableName);
                    $this->info("Dropped preview table: {$tableName}");
                } else {
                    $this->warn("Table {$tableName} was not dropped as it might contain production data.");
                }
            }

            DB::table('builder_entity_builds')
                ->where('id', $latestBuild->id)
                ->update(['is_active' => false]);
        }

        DB::table('builder_entities')
            ->where('id', $entity->id)
            ->update([
                'deleted_at' => now(),
            ]);

        $this->info("Entity '{$name}' deleted successfully from {$buildContext} context!");
    }
}
