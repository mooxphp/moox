<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Facades\DB;
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

        $buildContext = $preview ? 'preview' : ($package ? 'package' : 'app');

        $entityId = DB::table('builder_entities')->insertGetId([
            'singular' => $name,
            'plural' => Str::plural($name),
            'preset' => $presetName,
            'build_context' => $buildContext,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        DB::table('builder_entity_builds')->insert([
            'entity_id' => $entityId,
            'build_context' => $buildContext,
            'data' => json_encode($preset->getBlocks()),
            'files' => json_encode($generator->getGeneratedFiles()),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Entity '.$name.' created successfully in '.$buildContext);
    }
}
