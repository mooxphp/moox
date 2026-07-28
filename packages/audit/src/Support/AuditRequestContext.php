<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks audit entries created during the current HTTP request so custom-field
 * merges attach to the same save — not to the latest historical activity row.
 */
final class AuditRequestContext
{
    /** @var array<string, int> */
    private static array $mergeTargetIds = [];

    public function rememberMergeTarget(Model $subject, string $event, int $activityId): void
    {
        self::$mergeTargetIds[$this->key($subject, $event)] = $activityId;
    }

    public function mergeTargetId(Model $subject, string $event): ?int
    {
        $id = self::$mergeTargetIds[$this->key($subject, $event)] ?? null;

        return is_int($id) ? $id : null;
    }

    public function forgetMergeTarget(Model $subject, string $event): void
    {
        unset(self::$mergeTargetIds[$this->key($subject, $event)]);
    }

    public function clear(): void
    {
        self::$mergeTargetIds = [];
    }

    private function key(Model $subject, string $event): string
    {
        return $subject::class.'|'.(string) $subject->getKey().'|'.$event;
    }
}
