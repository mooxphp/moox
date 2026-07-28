<?php

declare(strict_types=1);

namespace Moox\Audit\Support\Sources;

use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Contracts\AuditDataSource;

final class ModelAttributeAuditSource implements AuditDataSource
{
    /**
     * @param  array<string, mixed>  $modelConfig
     * @param  array<string, mixed>  $sourceConfig
     * @return array<string, mixed>
     */
    public function snapshot(Model $model, array $modelConfig, array $sourceConfig = [], bool $useOriginal = false): array
    {
        $attributes = $modelConfig['attributes'] ?? [];
        $hidden = $modelConfig['hidden_attributes'] ?? [];
        $trackedAttributes = array_values(array_diff($attributes, $hidden));

        $snapshot = [];

        foreach ($trackedAttributes as $attribute) {
            if (! is_string($attribute) || $attribute === '') {
                continue;
            }

            $snapshot[$attribute] = $useOriginal
                ? $model->getOriginal($attribute)
                : $model->getAttribute($attribute);
        }

        return $snapshot;
    }
}
