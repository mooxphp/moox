<?php

declare(strict_types=1);

namespace Moox\Audit\Observers;

use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\Audit\Support\AuditConfigResolver;
use Moox\Audit\Support\AuditSnapshotResolver;
use Spatie\Activitylog\Enums\ActivityEvent;

final class ConfigDrivenModelObserver
{
    /** @var array<int, array<string, mixed>> */
    private array $oldSnapshots = [];

    public function created(Model $model): void
    {
        $this->record($model, ActivityEvent::Created->value);
    }

    public function updating(Model $model): void
    {
        $config = AuditConfigResolver::resolveModel($model::class);

        if ($config === null) {
            return;
        }

        $this->oldSnapshots[spl_object_id($model)] = AuditSnapshotResolver::snapshot($model, $config, true);
    }

    public function updated(Model $model): void
    {
        $this->record($model, ActivityEvent::Updated->value);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, ActivityEvent::Deleted->value);
    }

    public function restored(Model $model): void
    {
        $this->record($model, ActivityEvent::Restored->value);
    }

    private function record(Model $model, string $event): void
    {
        $config = AuditConfigResolver::resolveModel($model::class);

        if ($config === null) {
            return;
        }

        if (! in_array($event, $config['events'] ?? [
            ActivityEvent::Created->value,
            ActivityEvent::Updated->value,
            ActivityEvent::Deleted->value,
            ActivityEvent::Restored->value,
        ], true)) {
            return;
        }

        $changes = $this->buildChanges($model, $event, $config);

        if ($event === ActivityEvent::Updated->value) {
            $changes = $this->filterSignificantUpdates($changes, $config);
        }

        if ($this->shouldSkip($changes, $event)) {
            return;
        }

        MooxActivityLogger::audit(
            $model,
            $event,
            $changes,
            $config,
            (string) ($config['log_name'] ?? 'default'),
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function buildChanges(Model $model, string $event, array $config): array
    {
        $current = AuditSnapshotResolver::snapshot($model, $config);

        if ($event === ActivityEvent::Created->value) {
            return ['attributes' => $current];
        }

        if ($event === ActivityEvent::Deleted->value) {
            return ['old' => $current];
        }

        $objectId = spl_object_id($model);
        $old = $this->oldSnapshots[$objectId] ?? [];
        unset($this->oldSnapshots[$objectId]);

        $dirtyAttributes = [];
        $dirtyOld = [];

        foreach (array_unique([
            ...array_keys($old),
            ...array_keys($current),
        ]) as $attribute) {
            if (! is_string($attribute)) {
                continue;
            }

            $oldValue = $old[$attribute] ?? null;
            $newValue = $current[$attribute] ?? null;

            if ($oldValue != $newValue) {
                $dirtyOld[$attribute] = $oldValue;
                $dirtyAttributes[$attribute] = $newValue;
            }
        }

        return [
            'attributes' => $dirtyAttributes,
            'old' => $dirtyOld,
        ];
    }

    /**
     * When `significant_updates` is configured, keep only matching attribute
     * changes. Keys may map to `true` (any change) or a list of new values.
     *
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function filterSignificantUpdates(array $changes, array $config): array
    {
        $significant = $config['significant_updates'] ?? null;

        if (! is_array($significant) || $significant === []) {
            return $changes;
        }

        $attributes = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $old = is_array($changes['old'] ?? null) ? $changes['old'] : [];

        $filteredAttributes = [];
        $filteredOld = [];

        foreach ($significant as $attribute => $allowedValues) {
            if (! is_string($attribute) || $attribute === '' || ! array_key_exists($attribute, $attributes)) {
                continue;
            }

            $newValue = $attributes[$attribute];

            if (! $this->isSignificantUpdateValue($newValue, $allowedValues)) {
                continue;
            }

            $filteredAttributes[$attribute] = $newValue;

            if (array_key_exists($attribute, $old)) {
                $filteredOld[$attribute] = $old[$attribute];
            }
        }

        return [
            'attributes' => $filteredAttributes,
            'old' => $filteredOld,
        ];
    }

    private function isSignificantUpdateValue(mixed $newValue, mixed $allowedValues): bool
    {
        if ($allowedValues === true || $allowedValues === '*') {
            return true;
        }

        if (! is_array($allowedValues)) {
            return false;
        }

        if ($allowedValues === []) {
            return true;
        }

        $normalized = $this->normalizeComparableValue($newValue);

        if ($normalized === null) {
            return false;
        }

        return in_array($normalized, array_map(
            $this->normalizeComparableValue(...),
            $allowedValues,
        ), true);
    }

    private function normalizeComparableValue(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function shouldSkip(array $changes, string $event): bool
    {
        if ($event === ActivityEvent::Created->value) {
            return empty($changes['attributes']);
        }

        if ($event === ActivityEvent::Deleted->value) {
            return false;
        }

        return empty($changes['attributes']) && empty($changes['old']);
    }
}
