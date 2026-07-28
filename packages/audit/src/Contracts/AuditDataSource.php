<?php

declare(strict_types=1);

namespace Moox\Audit\Contracts;

use Illuminate\Database\Eloquent\Model;

interface AuditDataSource
{
    /**
     * @param  array<string, mixed>  $modelConfig
     * @param  array<string, mixed>  $sourceConfig
     * @return array<string, mixed>
     */
    public function snapshot(Model $model, array $modelConfig, array $sourceConfig = [], bool $useOriginal = false): array;
}
