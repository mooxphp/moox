<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Builder\PresetRegistry;

class EntityService
{
    public function create(string $name, string $buildContext, string $presetName): array
    {
        $existingEntity = $this->findEntity($name, $buildContext);

        if ($existingEntity) {
            $this->rebuild($existingEntity->id, $presetName);

            return ['entity' => $existingEntity, 'status' => 'exists'];
        }

        $entityId = DB::table('builder_entities')->insertGetId([
            'singular' => $name,
            'plural' => Str::plural($name),
            'preset' => $presetName,
            'build_context' => $buildContext,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->rebuild($entityId, $presetName);

        return [
            'entity' => DB::table('builder_entities')->find($entityId),
            'status' => 'created',
        ];
    }

    public function rebuild(int $entityId, string $presetName): void
    {
        DB::table('builder_entity_blocks')
            ->where('entity_id', $entityId)
            ->delete();

        $preset = PresetRegistry::getPreset($presetName);
        $blocks = $preset->getBlocks();

        $entity = DB::table('builder_entities')->find($entityId);

        $this->recordBuild(
            $entityId,
            $entity->build_context,
            $blocks,
            []
        );
    }

    public function recordBuild(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        foreach ($files as $path => $content) {
            if (str_contains($path, 'Resource.php') || str_contains($path, '/Pages/')) {
                $files[$path] = $this->formatWithPint($content);
            }
        }

        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->update(['is_active' => false]);

        $blockData = array_map(function ($block) {
            return [
                'type' => get_class($block),
                'name' => $block->getName(),
                'label' => $block->getLabel(),
                'description' => $block->getDescription(),
                'nullable' => $block->isNullable(),
                'fillable' => $block->isFillable(),
                'requiredBlocks' => $block->getRequiredBlocks(),
                'containsBlocks' => $block->getContainsBlocks(),
                'incompatibleBlocks' => $block->getIncompatibleBlocks(),
                'casts' => $block->getCasts('model'),
                'migrations' => $block->getMigrations(),
                'useStatements' => $block->getUseStatements('model'),
                'traits' => $block->getTraits('model'),
                'methods' => $block->getMethods('model'),
                'formFields' => $block->getFormFields(),
                'tableColumns' => $block->getTableColumns(),
                'factories' => $block->getFactories(),
                'tests' => $block->getTests('unit', 'model'),
                'filters' => $block->getFilters(),
                'actions' => $block->getActions('resource'),
            ];
        }, $blocks);

        DB::table('builder_entity_builds')->insert([
            'entity_id' => $entityId,
            'build_context' => $buildContext,
            'data' => json_encode($blockData),
            'files' => json_encode($files),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function delete(string $name, string $buildContext, bool $force = false): array
    {
        $entity = $this->findEntity($name, $buildContext);

        if (! $entity) {
            return ['status' => 'not_found'];
        }

        $latestBuild = $this->getLatestBuild($entity->id);

        if ($latestBuild) {
            DB::table('builder_entity_builds')
                ->where('id', $latestBuild->id)
                ->update(['is_active' => false]);
        }

        DB::table('builder_entities')
            ->where('id', $entity->id)
            ->update(['deleted_at' => now()]);

        return [
            'entity' => $entity,
            'build' => $latestBuild,
            'status' => 'deleted',
        ];
    }

    public function findEntity(string $name, string $buildContext): ?object
    {
        return DB::table('builder_entities')
            ->where('singular', $name)
            ->where('build_context', $buildContext)
            ->whereNull('deleted_at')
            ->first();
    }

    public function getLatestBuild(int $entityId): ?object
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function cleanupPreviewFiles(object $build): void
    {
        $files = json_decode($build->files, true);
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function createPreviewTable(string $entityName, array $blocks): void
    {
        $tableName = Str::plural(Str::snake($entityName));

        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }

        Schema::create($tableName, function (Blueprint $table) use ($blocks) {
            $table->id();
            $table->timestamps();

            foreach ($blocks as $block) {
                $migrations = $block->getMigrations();
                foreach ($migrations as $migration) {
                    $this->addColumn($table, $migration);
                }
            }
        });
    }

    protected function addColumn(Blueprint $table, string $migration): void
    {
        if (preg_match('/^(\w+)\([\'"](\w+)[\'"]\)(.*)$/', $migration, $matches)) {
            $type = $matches[1];
            $name = $matches[2];
            $modifiers = $matches[3];

            $column = $table->{$type}($name);

            if (str_contains($modifiers, '->nullable()')) {
                $column->nullable();
            }
            if (str_contains($modifiers, '->unique()')) {
                $column->unique();
            }
        }
    }

    public function dropPreviewTable(string $entityName): void
    {
        $tableName = Str::plural(Str::snake($entityName));

        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }
    }

    public function reconstructBlocksFromBuild(object $build): array
    {
        $blockData = json_decode($build->data, true);
        $blocks = [];

        foreach ($blockData as $data) {
            $blockClass = $data['type'];

            $blockOptions = DB::table('builder_entity_blocks')
                ->where('entity_id', $build->entity_id)
                ->where('block_class', $blockClass)
                ->first();

            if (! $blockOptions) {
                continue;
            }

            $options = json_decode($blockOptions->options, true);

            $block = new $blockClass(...array_values($options));

            $block->setFeatureFlags($data);
            $block->initialize();
            $blocks[] = $block;
        }

        return $blocks;
    }

    protected function formatWithPint(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'moox_');
        file_put_contents($tempFile, $content);

        shell_exec("./vendor/bin/pint {$tempFile} --quiet");

        $formattedContent = file_get_contents($tempFile);
        unlink($tempFile);

        return $formattedContent;
    }
}
