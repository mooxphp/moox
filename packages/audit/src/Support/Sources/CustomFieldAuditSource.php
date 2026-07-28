<?php

declare(strict_types=1);

namespace Moox\Audit\Support\Sources;

use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Contracts\AuditDataSource;

final class CustomFieldAuditSource implements AuditDataSource
{
    /**
     * @param  array<string, mixed>  $modelConfig
     * @param  array<string, mixed>  $sourceConfig
     * @return array<string, mixed>
     */
    public function snapshot(Model $model, array $modelConfig, array $sourceConfig = [], bool $useOriginal = false): array
    {
        if (! method_exists($model, 'customFields')) {
            return [];
        }

        $values = $model->customFields($useOriginal);

        return is_array($values) ? $values : [];
    }
}
