<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Contexts;

interface BuildContext
{
    // Core properties
    public function getEntityName(): string;

    // Context type checks
    public function isPreview(): bool;

    public function isPackage(): bool;

    // Base paths and namespaces
    public function getBasePath(): string;

    public function getBaseNamespace(): string;

    // Entity paths
    public function getModelPath(): string;

    public function getResourcePath(): string;

    public function getMigrationPath(): string;

    public function getPluginPath(): string;

    // Entity namespaces
    public function getModelNamespace(): string;

    public function getResourceNamespace(): string;

    public function getPluginNamespace(): string;

    // Entity properties
    public function getTableName(): string;

    public function getPluralModelName(): string;

    // Migration handling
    public function shouldPublishMigrations(): bool;

    public function getMigrationFileName(): string;

    public function validate(): void;
}
