<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Services\MooxActivityLogger;

final class AuditHooks
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $hookConfig
     */
    public static function registerHook(string $modelClass, string $event, array $hookConfig): void
    {
        $modelClass::$event(function (Model $model) use ($hookConfig): void {
            $handler = $hookConfig['handler'] ?? null;

            if ($handler === 'categorizables_detached') {
                self::logCategorizablesDetached($model, $hookConfig);

                return;
            }

            MooxActivityLogger::log(
                (string) ($hookConfig['log_name'] ?? 'default'),
                (string) ($hookConfig['description'] ?? $hookConfig['event'] ?? 'event'),
                [
                    'entry_type' => $hookConfig['entry_type'] ?? 'log',
                    'event' => $hookConfig['event'] ?? null,
                    'subject' => $model,
                    'properties' => is_array($hookConfig['properties'] ?? null) ? $hookConfig['properties'] : [],
                ],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $hookConfig
     */
    private static function logCategorizablesDetached(Model $model, array $hookConfig): void
    {
        $scope = $model->getAttribute('scope');

        MooxActivityLogger::log(
            (string) ($hookConfig['log_name'] ?? 'category'),
            (string) ($hookConfig['description'] ?? 'categorizables_detached'),
            [
                'entry_type' => $hookConfig['entry_type'] ?? 'log',
                'event' => $hookConfig['event'] ?? 'categorizables_detached',
                'subject' => $model,
                'scope' => is_string($scope) && $scope !== '' ? $scope : null,
                'properties' => [
                    'category_id' => $model->getKey(),
                ],
            ],
        );
    }
}
