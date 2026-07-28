<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Contracts\AuditDataSource;
use Moox\Audit\Support\Sources\CustomFieldAuditSource;
use Moox\Audit\Support\Sources\ModelAttributeAuditSource;

final class AuditSnapshotResolver
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function snapshot(Model $model, array $config, bool $useOriginal = false): array
    {
        $snapshot = [];

        foreach (self::resolveSources($model, $config) as $sourceDefinition) {
            $source = app($sourceDefinition['class']);

            if (! $source instanceof AuditDataSource) {
                continue;
            }

            $snapshot = array_replace(
                $snapshot,
                $source->snapshot($model, $config, $sourceDefinition['config'], $useOriginal),
            );
        }

        return self::filterHiddenAttributes($snapshot, $config);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{class: class-string, config: array<string, mixed>}>
     */
    private static function resolveSources(Model $model, array $config): array
    {
        $definitions = [[
            'class' => ModelAttributeAuditSource::class,
            'config' => [],
        ]];

        foreach ($config['sources'] ?? [] as $source) {
            if (is_string($source) && is_a($source, AuditDataSource::class, true)) {
                $definitions[] = [
                    'class' => $source,
                    'config' => [],
                ];

                continue;
            }

            if (! is_array($source)) {
                continue;
            }

            $class = $source['class'] ?? null;

            if (! is_string($class) || ! is_a($class, AuditDataSource::class, true)) {
                continue;
            }

            $definitions[] = [
                'class' => $class,
                'config' => $source,
            ];
        }

        if (method_exists($model, 'customFieldNames') && method_exists($model, 'customFields')) {
            $definitions[] = [
                'class' => CustomFieldAuditSource::class,
                'config' => [],
            ];
        }

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private static function filterHiddenAttributes(array $snapshot, array $config): array
    {
        $hidden = array_values(array_filter($config['hidden_attributes'] ?? [], is_string(...)));

        if ($hidden === []) {
            return $snapshot;
        }

        return array_diff_key($snapshot, array_fill_keys($hidden, true));
    }
}
