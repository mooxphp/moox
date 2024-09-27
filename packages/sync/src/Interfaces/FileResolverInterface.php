<?php

namespace Moox\Sync\Interfaces;

interface FileResolverInterface
{
    public function resolve(): array;

    public function getFileFields(): array;

    public function getFileData(string $field): ?array;
}
