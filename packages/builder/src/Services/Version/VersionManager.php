<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Version;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\ContextAwareService;
use RuntimeException;

class VersionManager extends ContextAwareService
{
    public function execute(): void
    {
        $this->ensureContextIsSet();
    }

    public function createVersion(array $data): int
    {
        $contextType = $this->context->getContextType();

        return match ($contextType) {
            'package' => $this->createPackageVersion($data),
            'app', 'preview' => $this->createEntityVersion($data),
            default => throw new RuntimeException("Unsupported context type: {$contextType}"),
        };
    }

    public function getLatestVersion(): ?object
    {
        $contextType = $this->context->getContextType();

        return match ($contextType) {
            'package' => $this->getLatestPackageVersion(),
            'app', 'preview' => $this->getLatestEntityVersion(),
            default => throw new RuntimeException("Unsupported context type: {$contextType}"),
        };
    }

    protected function createPackageVersion(array $data): int
    {
        return DB::table('builder_package_versions')->insertGetId([
            'package_id' => $this->getPackageId(),
            'version' => $this->generateVersionNumber(),
            'data' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createEntityVersion(array $data): int
    {
        return DB::table('builder_entity_builds')->insertGetId([
            'entity_id' => $this->getEntityId(),
            'build_context' => $this->context->getContextType(),
            'version' => $this->generateVersionNumber(),
            'data' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function getLatestPackageVersion(): ?object
    {
        return DB::table('builder_package_versions')
            ->where('package_id', $this->getPackageId())
            ->orderBy('created_at', 'desc')
            ->first();
    }

    protected function getLatestEntityVersion(): ?object
    {
        return DB::table('builder_entity_builds')
            ->where('entity_id', $this->getEntityId())
            ->where('build_context', $this->context->getContextType())
            ->orderBy('created_at', 'desc')
            ->first();
    }

    protected function getPackageId(): int
    {
        $name = $this->context->getEntityName();
        $package = DB::table('builder_packages')
            ->where('name', $name)
            ->first();

        return $package ? $package->id : 0;
    }

    protected function getEntityId(): int
    {
        $name = $this->context->getEntityName();
        $entity = DB::table('builder_entities')
            ->where('singular', $name)
            ->whereNull('deleted_at')
            ->first();

        return $entity ? $entity->id : 0;
    }

    protected function generateVersionNumber(): string
    {
        $latestVersion = $this->getLatestVersion();
        if (! $latestVersion) {
            return '1.0.0';
        }

        $parts = explode('.', $latestVersion->version);
        $parts[2] = (int) $parts[2] + 1;

        return implode('.', $parts);
    }
}
