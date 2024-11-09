<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\EntityGenerator;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create {name} {--package=} {--preview} {--app} {--preset=}';

    protected $description = 'Create a new entity with model, resource and plugin';

    public function handle(): void
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');
        $buildContext = $preview ? 'preview' : ($package ? 'package' : 'app');

        $presetName = $this->option('preset');
        if (! $presetName) {
            $presetName = $this->choice(
                'Which preset would you like to use?',
                [
                    'simple-item',
                    'publishable-item',
                    'full-item',
                    'simple-taxonomy',
                    'nested-taxonomy',
                ],
                'simple-item'
            );
        }

        $existingEntity = DB::table('builder_entities')
            ->where('singular', $name)
            ->where('build_context', $buildContext)
            ->first();

        if ($existingEntity) {
            if (! $this->confirm("Entity '{$name}' already exists in {$buildContext} context. Do you want to rebuild it?")) {
                return;
            }

            $latestBuild = DB::table('builder_entity_builds')
                ->where('entity_id', $existingEntity->id)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestBuild) {
                if ($buildContext === 'preview') {
                    $files = json_decode($latestBuild->files, true);
                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }
                    $tableName = Str::plural(Str::snake($name));
                    if (Schema::hasTable($tableName)) {
                        Schema::dropIfExists($tableName);
                    }
                } else {
                    $this->warn('Warning: This entity might have production data.');
                    if (! $this->confirm('Are you sure you want to regenerate files? This might require manual migration handling.')) {
                        return;
                    }
                }
            }

            $entityId = $existingEntity->id;
        } else {
            $entityId = DB::table('builder_entities')->insertGetId([
                'singular' => $name,
                'plural' => Str::plural($name),
                'preset' => $presetName,
                'build_context' => $buildContext,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('builder_entity_blocks')
            ->where('entity_id', $entityId)
            ->delete();

        $preset = PresetRegistry::getPreset($presetName);
        foreach ($preset->getBlocks() as $index => $block) {
            DB::table('builder_entity_blocks')->insert([
                'entity_id' => $entityId,
                'title' => $block->getTitle(),
                'description' => $block->getDescription(),
                'block_class' => get_class($block),
                'options' => json_encode($block->getOptions()),
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $context = $this->createContext($name, $package, $preview);
        $context->setPresetName($presetName);

        $generator = new EntityGenerator($context, $preset->getBlocks());
        $generator->execute();

        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->update(['is_active' => false]);

        DB::table('builder_entity_builds')->insert([
            'entity_id' => $entityId,
            'build_context' => $buildContext,
            'data' => json_encode($preset->getBlocks()),
            'files' => json_encode($generator->getGeneratedFiles()),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Entity '.$name.' '.($existingEntity ? 're' : '').'built successfully in '.$buildContext);
    }
}
