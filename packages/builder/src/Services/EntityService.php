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
    }

    public function recordBuild(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        // Format resource and page files with Pint before recording
        foreach ($files as $path => $content) {
            if (str_contains($path, 'Resource.php') || str_contains($path, '/Pages/')) {
                $files[$path] = $this->formatWithPint($content);
            }
        }

        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->update(['is_active' => false]);

        $blockData = array_map(function ($block) {
            $data = [
                'type' => get_class($block),
                'name' => $block->getName(),
                'label' => $block->getLabel(),
                'description' => $block->getDescription(),
                'nullable' => $block->isNullable(),
                'fillable' => $block->isFillable(),
                'requiredBlocks' => $block->getRequiredBlocks(),
                'containsBlocks' => $block->getContainsBlocks(),
                'incompatibleBlocks' => $block->getIncompatibleBlocks(),
            ];

            // Only store non-empty arrays
            if ($casts = $block->getCasts('model')) {
                $data['casts'] = $casts;
            }
            if ($migrations = $block->getMigrations()) {
                $data['migrations'] = $migrations;
            }
            if ($useStatements = $block->getUseStatements('model')) {
                $data['useStatements'] = $useStatements;
            }
            if ($traits = $block->getTraits('model')) {
                $data['traits'] = $traits;
            }
            if ($methods = $block->getMethods('model')) {
                $data['methods'] = $methods;
            }
            if ($formFields = $block->getFormFields()) {
                $data['formFields'] = $formFields;
            }
            if ($tableColumns = $block->getTableColumns()) {
                $data['tableColumns'] = $tableColumns;
            }
            if ($factories = $block->getFactories()) {
                $data['factories'] = $factories;
            }
            if ($tests = $block->getTests('unit', 'model')) {
                $data['tests'] = $tests;
            }
            if ($filters = $block->getFilters()) {
                $data['filters'] = $filters;
            }
            if ($actions = $block->getActions('resource')) {
                $data['actions'] = $actions;
            }

            return $data;
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
            // Default columns
            $table->id();
            $table->timestamps();

            // Add columns from blocks
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
        // Parse migration string to extract column type and name
        // Example: "string('title')->nullable()"
        if (preg_match('/^(\w+)\([\'"](\w+)[\'"]\)(.*)$/', $migration, $matches)) {
            $type = $matches[1];    // string
            $name = $matches[2];    // title
            $modifiers = $matches[3]; // ->nullable()

            // Create column
            $column = $table->{$type}($name);

            // Apply modifiers
            if (str_contains($modifiers, '->nullable()')) {
                $column->nullable();
            }
            if (str_contains($modifiers, '->unique()')) {
                $column->unique();
            }
            // Add other modifiers as needed
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
            $block = new $blockClass(
                $data['name'],
                $data['label'],
                $data['description']
            );

            $block->setFeatureFlags($data);
            $block->setArrayData($data);

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
