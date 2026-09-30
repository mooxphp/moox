<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Moox\Audit\Models\Activity;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\Audit\Support\AuditConfigResolver;

/**
 * The only place e-billing reads moox/audit's stored history. The value the parser produced for
 * an invoice, line or allowance/charge field is the one in that row's `created` activity:
 * the row is inserted from the parsed data and re-inserted on every re-parse.
 */
final class ParsedValueHistory
{
    public static function isAvailable(): bool
    {
        return class_exists(MooxActivityLogger::class)
            && (bool) config('audit.enabled', true)
            && (bool) config('e-billing.audit.enabled', true);
    }

    /**
     * Whether moox/audit tracks the attribute (the part before the first dot) on this model.
     */
    public static function isAudited(Model $target, string $attribute): bool
    {
        $config = AuditConfigResolver::resolveModel($target::class);
        if ($config === null) {
            return false;
        }

        $tracked = array_diff($config['attributes'] ?? [], $config['hidden_attributes'] ?? []);

        return in_array(explode('.', $attribute, 2)[0], $tracked, true);
    }

    public static function parsedValue(Model $target, string $attribute): mixed
    {
        /** @var class-string<Activity> $activityModel */
        $activityModel = config('audit.activity_model', Activity::class);

        $created = $activityModel::query()
            ->where('subject_type', $target->getMorphClass())
            ->where('subject_id', $target->getKey())
            ->where('event', 'created')
            ->oldest('id')
            ->first();

        $attributes = $created?->attribute_changes?->get('attributes');
        $topLevel = explode('.', $attribute, 2)[0];

        if (! is_array($attributes) || ! array_key_exists($topLevel, $attributes)) {
            throw new InvalidArgumentException(
                "No parsed value recorded for {$attribute} on ".class_basename($target)." #{$target->getKey()}.",
            );
        }

        return Arr::get($attributes, $attribute);
    }
}
