<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EntityCreator
{
    public function __construct(
        private readonly EntityRebuilder $entityRebuilder
    ) {}

    public function create(string $name, string $buildContext, string $presetName): array
    {
        $existingEntity = $this->findEntity($name, $buildContext);

        if ($existingEntity) {
            $blocks = $this->entityRebuilder->rebuild($existingEntity->id, $presetName);

            return ['entity' => $existingEntity, 'status' => 'exists', 'blocks' => $blocks];
        }

        $entityId = DB::table('builder_entities')->insertGetId([
            'singular' => $name,
            'plural' => Str::plural($name),
            'preset' => $presetName,
            'build_context' => $buildContext,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $blocks = $this->entityRebuilder->rebuild($entityId, $presetName);

        return [
            'entity' => DB::table('builder_entities')->find($entityId),
            'status' => 'created',
            'blocks' => $blocks,
        ];
    }

    protected function findEntity(string $name, string $buildContext): ?object
    {
        return DB::table('builder_entities')
            ->where('singular', $name)
            ->where('build_context', $buildContext)
            ->whereNull('deleted_at')
            ->first();
    }
}
