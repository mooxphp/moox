<?php

declare(strict_types=1);

namespace Moox\Audit\Observers;

use Illuminate\Database\Eloquent\Model;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\Audit\Support\AuditConfigResolver;
use Spatie\Activitylog\Enums\ActivityEvent;

final class ConfigDrivenModelObserver
{
    /** @var array<int, array<string, mixed>> */
    private array $oldAttributes = [];

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

        $attributes = $this->trackedAttributes($config);
        $old = [];

        foreach ($attributes as $attribute) {
            $old[$attribute] = $model->getOriginal($attribute);
        }

        $this->oldAttributes[spl_object_id($model)] = $old;
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
        $attributes = $this->trackedAttributes($config);
        $current = [];

        foreach ($attributes as $attribute) {
            $current[$attribute] = $model->getAttribute($attribute);
        }

        if ($event === ActivityEvent::Created->value) {
            return ['attributes' => $current];
        }

        if ($event === ActivityEvent::Deleted->value) {
            return ['old' => $current];
        }

        $objectId = spl_object_id($model);
        $old = $this->oldAttributes[$objectId] ?? [];
        unset($this->oldAttributes[$objectId]);

        $dirtyAttributes = [];
        $dirtyOld = [];

        foreach ($attributes as $attribute) {
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
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private function trackedAttributes(array $config): array
    {
        $attributes = $config['attributes'] ?? [];
        $hidden = $config['hidden_attributes'] ?? [];

        return array_values(array_diff($attributes, $hidden));
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
