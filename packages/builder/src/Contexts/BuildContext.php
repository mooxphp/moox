<?php

declare(strict_types=1);

namespace Moox\Builder\Contexts;

use Illuminate\Console\Command;

interface BuildContext
{
    public function getEntityName(): string;

    public function getBasePath(): string;

    public function getBaseNamespace(): string;

    public function getPath(string $type): string;

    public function getNamespace(string $type): string;

    public function getTableName(): string;

    public function getPluralModelName(): string;

    public function isPreview(): bool;

    public function isPackage(): bool;

    public function shouldPublishMigrations(): bool;

    public function validate(): void;

    public function getPresetName(): string;

    public function setPresetName(string $name): void;

    public function getCommand(): ?Command;

    public function setCommand(Command $command): void;
}
