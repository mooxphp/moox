<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Services\MooxActivityLogger;

final class CustomFieldAuditMerger
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function mergeCreated(Model $record, string $resourceClass, array $data): void
    {
        $this->merge($record, $resourceClass, $data, 'created');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function mergeUpdated(Model $record, string $resourceClass, array $data): void
    {
        $this->merge($record, $resourceClass, $data, 'updated');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function merge(Model $record, string $resourceClass, array $data, string $event): void
    {
        $config = AuditConfigResolver::resolveModel($record::class);

        if ($config === null || ! method_exists($record, 'customFields')) {
            return;
        }

        $managerClass = 'Moox\\Builder\\Services\\CustomFieldsManager';

        if (! class_exists($managerClass)) {
            return;
        }

        /** @var object $manager */
        $manager = app($managerClass);

        if (! method_exists($manager, 'preparedFormValues')) {
            return;
        }

        /** @var array<string, mixed> $newValues */
        $newValues = $manager->preparedFormValues($resourceClass, $record, $data);

        if ($newValues === []) {
            return;
        }

        /** @var array<string, mixed> $oldValues */
        $oldValues = $event === 'created'
            ? []
            : $record->customFields(true);

        $changes = $this->buildChanges($oldValues, $newValues, $event);

        if (($changes['attributes'] ?? []) === [] && ($changes['old'] ?? []) === []) {
            return;
        }

        $activity = $this->latestActivityFor($record, $event);

        if ($activity === null) {
            MooxActivityLogger::audit(
                $record,
                $event,
                $changes,
                $config,
                (string) ($config['log_name'] ?? 'default'),
            );

            return;
        }

        $current = $activity->attribute_changes;
        $current = is_array($current) ? $current : (is_object($current) && method_exists($current, 'toArray') ? $current->toArray() : []);

        $mergedAttributes = array_replace(
            is_array($current['attributes'] ?? null) ? $current['attributes'] : [],
            $changes['attributes'] ?? [],
        );

        $merged = [
            'attributes' => $mergedAttributes,
        ];

        $mergedOld = array_replace(
            is_array($current['old'] ?? null) ? $current['old'] : [],
            $changes['old'] ?? [],
        );

        if ($mergedOld !== []) {
            $merged['old'] = $mergedOld;
        }

        $activity->attribute_changes = $merged;
        $activity->save();
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array{attributes: array<string, mixed>, old?: array<string, mixed>}
     */
    private function buildChanges(array $oldValues, array $newValues, string $event): array
    {
        if ($event === 'created') {
            return [
                'attributes' => $newValues,
            ];
        }

        $attributes = [];
        $old = [];

        foreach (array_unique([...array_keys($oldValues), ...array_keys($newValues)]) as $key) {
            if (! is_string($key)) {
                continue;
            }

            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            if ($oldValue != $newValue) {
                $old[$key] = $oldValue;
                $attributes[$key] = $newValue;
            }
        }

        return [
            'attributes' => $attributes,
            'old' => $old,
        ];
    }

    private function latestActivityFor(Model $record, string $event): ?Model
    {
        $activityModel = config('audit.activity_model');

        if (! is_string($activityModel) || ! is_subclass_of($activityModel, Model::class)) {
            return null;
        }

        return $activityModel::query()
            ->where('subject_type', $record::class)
            ->where('subject_id', $record->getKey())
            ->where('event', $event)
            ->latest('id')
            ->first();
    }
}
