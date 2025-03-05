<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Exception;
use Illuminate\Support\Facades\DB;
use Moox\Builder\Blocks\AbstractBlock;
use RuntimeException;

class BuildRecorder
{
    public function record(int $entityId, string $buildContext, array $blocks, array $files): void
    {
        if ($blocks === []) {
            throw new RuntimeException(
                'Blocks array empty in BuildRecorder. Debug trace: '.
                json_encode([
                    'entityId' => $entityId,
                    'context' => $buildContext,
                    'blockCount' => count($blocks),
                    'blockTypes' => array_map(fn ($block) => $block::class, $blocks),
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
                ])
            );
        }

        DB::beginTransaction();
        try {
            $this->validateFileStructure($files);
            $blockData = $this->serializeBlocks($blocks);

            if ($blockData === []) {
                throw new RuntimeException(
                    'Serialized block data empty. Original blocks: '.
                    json_encode(array_map(fn ($block) => $block::class, $blocks))
                );
            }

            $this->deactivateBuilds($entityId, $buildContext);
            $this->recordBuild($entityId, $buildContext, $blockData, $files);
            $this->persistBlocks($entityId, $blocks);

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw new RuntimeException('Build recording failed: '.$exception->getMessage().
            "\nBlock count: ".count($blocks).
            "\nBlock types: ".json_encode(array_map(fn ($block) => $block::class, $blocks)), $exception->getCode(), $exception);
        }
    }

    public function validateEntityExists(int $entityId): bool
    {
        return DB::table('builder_entities')->where('id', $entityId)->exists();
    }

    public function hasConflictingProductionBuild(int $entityId, string $newContext): bool
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->where('build_context', '!=', $newContext)
            ->where('build_context', '!=', 'preview')
            ->exists();
    }

    public function loadCurrentState(int $entityId, string $contextType): array
    {
        $build = DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $contextType)
            ->where('is_active', true)
            ->first();

        if (! $build) {
            return [];
        }

        return [
            'entity_id' => $build->entity_id,
            'build_context' => $build->build_context,
            'data' => json_decode((string) $build->data, true),
            'files' => json_decode((string) $build->files, true),
        ];
    }

    protected function deactivateBuilds(int $entityId, string $buildContext): void
    {
        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $buildContext)
            ->update(['is_active' => false]);
    }

    protected function recordBuild(int $entityId, string $buildContext, array $blockData, array $files): void
    {
        $encodedData = json_encode($blockData);
        $encodedFiles = json_encode($files);

        if ($encodedData === false || $encodedData === '[]' || $encodedData === 'null') {
            throw new RuntimeException('Failed to encode block data or data is empty');
        }

        DB::table('builder_entity_builds')->insert([
            'entity_id' => $entityId,
            'build_context' => $buildContext,
            'data' => $encodedData,
            'files' => $encodedFiles,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function persistBlocks(int $entityId, array $blocks): void
    {
        DB::table('builder_entity_blocks')
            ->where('entity_id', $entityId)
            ->delete();

        foreach ($blocks as $index => $block) {
            DB::table('builder_entity_blocks')->insert([
                'entity_id' => $entityId,
                'title' => $block->getTitle(),
                'description' => $block->getDescription(),
                'block_class' => $block::class,
                'options' => json_encode($block->getOptions()),
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function serializeBlocks(array $blocks): array
    {
        $serialized = array_map(function ($block): array {
            if (! $block instanceof AbstractBlock) {
                throw new RuntimeException('Invalid block: must be instance of AbstractBlock');
            }

            $data = [
                'type' => $block::class,
                'title' => $block->getTitle(),
                'description' => $block->getDescription(),
                'options' => $block->getOptions(),
                'migrations' => $block->getMigrations(),
            ];

            /** @var mixed $block */
            /** @phpstan-ignore-next-line */
            if (method_exists($block, 'getUseStatements')) {
                $data['useStatements'] = [
                    'model' => $block->getUseStatements('model'),
                    'resource' => $block->getUseStatements('resource'),
                    'pages' => $block->getUseStatements('pages'),
                ];
            }

            if (method_exists($block, 'getTraits')) {
                $data['traits'] = [
                    'model' => $block->getTraits('model'),
                    'resource' => $block->getTraits('resource'),
                    'pages' => $block->getTraits('pages'),
                ];
            }

            if (method_exists($block, 'getMethods')) {
                $data['methods'] = $block->getMethods('model');
            }

            if (method_exists($block, 'getFormFields')) {
                $data['formFields'] = $block->getFormFields();
            }

            if (method_exists($block, 'getTableColumns')) {
                $data['tableColumns'] = $block->getTableColumns();
            }

            return $data;
        }, $blocks);

        if ($serialized === []) {
            throw new RuntimeException('Failed to serialize blocks');
        }

        return $serialized;
    }

    protected function validateFileStructure(array $files): void
    {
        foreach ($files as $type => $typeFiles) {
            if (! is_array($typeFiles)) {
                throw new RuntimeException('Invalid file structure for type: '.$type);
            }

            foreach ($typeFiles as $path => $content) {
                if (! is_string($path)) {
                    throw new RuntimeException('Invalid path in type '.$type);
                }

                if (! is_string($content)) {
                    throw new RuntimeException(sprintf('Invalid content for path %s in type %s', $path, $type));
                }
            }
        }
    }

    public function getLatestBuild(int $entityId, ?string $buildContext = null): object
    {
        $query = DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('is_active', true);

        if ($buildContext) {
            $query->where('build_context', $buildContext);
        }

        $build = $query->orderBy('created_at', 'desc')->first();

        if (! $build) {
            throw new RuntimeException('No active build found for entity '.$entityId);
        }

        return $build;
    }

    public function getEntityIdFromName(string $entityName): int
    {
        $entity = DB::table('builder_entities')
            ->where('singular', $entityName)
            ->first();

        if (! $entity) {
            throw new RuntimeException('Entity not found with name: '.$entityName);
        }

        return $entity->id;
    }
}
