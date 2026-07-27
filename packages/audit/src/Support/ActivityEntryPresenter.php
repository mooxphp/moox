<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Moox\Audit\Models\Activity;

final class ActivityEntryPresenter
{
    /**
     * @return array<string, string>
     */
    public static function flattenChanges(mixed $changes): array
    {
        if ($changes instanceof Collection) {
            $changes = $changes->all();
        }

        if (! is_array($changes)) {
            return [];
        }

        $old = is_array($changes['old'] ?? null) ? $changes['old'] : [];
        $attributes = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $keys = array_unique([...array_keys($old), ...array_keys($attributes)]);
        $result = [];

        foreach ($keys as $key) {
            $oldValue = $old[$key] ?? null;
            $newValue = $attributes[$key] ?? null;

            if ($oldValue == $newValue) {
                continue;
            }

            if ($oldValue !== null && $newValue !== null) {
                $result[(string) $key] = self::formatValue($oldValue).' → '.self::formatValue($newValue);
            } elseif ($newValue !== null) {
                $result[(string) $key] = self::formatValue($newValue);
            } elseif ($oldValue !== null) {
                $result[(string) $key] = self::formatValue($oldValue);
            }
        }

        return $result;
    }

    /**
     * @return list<array{field: string, old: ?string, new: ?string, kind: 'changed'|'added'|'removed'}>
     */
    public static function changeRows(mixed $changes): array
    {
        if ($changes instanceof Collection) {
            $changes = $changes->all();
        }

        if (! is_array($changes)) {
            return [];
        }

        $old = is_array($changes['old'] ?? null) ? $changes['old'] : [];
        $attributes = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $keys = array_unique([...array_keys($old), ...array_keys($attributes)]);
        $rows = [];

        foreach ($keys as $key) {
            $oldValue = $old[$key] ?? null;
            $newValue = $attributes[$key] ?? null;

            if ($oldValue == $newValue) {
                continue;
            }

            if ($oldValue !== null && $newValue !== null) {
                $kind = 'changed';
            } elseif ($newValue !== null) {
                $kind = 'added';
            } else {
                $kind = 'removed';
            }

            $rows[] = [
                'field' => (string) $key,
                'old' => $oldValue !== null ? self::formatValue($oldValue) : null,
                'new' => $newValue !== null ? self::formatValue($newValue) : null,
                'kind' => $kind,
            ];
        }

        return $rows;
    }

    public static function propertyValue(?Activity $activity, string $key): ?string
    {
        if ($activity === null) {
            return null;
        }

        $properties = self::flattenProperties($activity->properties);

        $value = $properties[$key] ?? null;

        return is_string($value) && $value !== '' && $value !== '—' ? $value : null;
    }

    /**
     * @return array<string, string>
     */
    public static function flattenProperties(mixed $properties): array
    {
        if ($properties instanceof Collection) {
            $properties = $properties->all();
        }

        if (! is_array($properties)) {
            return [];
        }

        $result = [];

        foreach ($properties as $key => $value) {
            $result[(string) $key] = self::formatValue($value);
        }

        return $result;
    }

    public static function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function headline(?Activity $activity): string
    {
        if ($activity === null) {
            return '—';
        }

        $causer = self::causerLabel($activity);
        $event = self::eventLabel($activity);
        $subject = self::subjectLabel($activity);

        if ($causer === '—') {
            $causer = __('core::audit.system');
        }

        return trim(sprintf('%s %s %s', $causer, $event, $subject));
    }

    public static function eventLabel(?Activity $activity): string
    {
        if ($activity === null) {
            return '—';
        }

        $event = trim((string) ($activity->event ?: $activity->description ?: ''));

        if ($event === '') {
            return '—';
        }

        $key = 'core::audit.event_'.$event;
        $translated = __($key);

        return $translated !== $key ? $translated : Str::of($event)->replace('_', ' ')->lower()->toString();
    }

    public static function hasDistinctDescription(?Activity $activity): bool
    {
        if ($activity === null) {
            return false;
        }

        $description = trim((string) $activity->description);
        $event = trim((string) ($activity->event ?? ''));

        return $description !== '' && strcasecmp($description, $event) !== 0;
    }

    public static function subjectLabel(?Activity $activity): string
    {
        if ($activity === null) {
            return '—';
        }

        $type = $activity->subject_type;

        if (! is_string($type) || $type === '') {
            return '—';
        }

        $typeLabel = self::subjectTypeLabel($type);
        $title = self::subjectTitle($activity);

        if ($title !== null) {
            return sprintf('%s: %s', $typeLabel, $title);
        }

        return $typeLabel;
    }

    public static function subjectIdLabel(?Activity $activity): ?string
    {
        if ($activity === null) {
            return null;
        }

        $id = $activity->subject_id;

        if ($id === null || $id === '') {
            return null;
        }

        return '#'.$id;
    }

    public static function subjectTypeLabel(string $type): string
    {
        $basename = class_basename($type);

        return Str::of($basename)
            ->replaceEnd('Translation', ' translation')
            ->headline()
            ->toString();
    }

    public static function causerLabel(?Activity $activity): string
    {
        if ($activity === null) {
            return '—';
        }

        $causer = $activity->causer;

        if ($causer instanceof Model) {
            foreach (['name', 'title', 'email'] as $attribute) {
                $value = $causer->getAttribute($attribute);

                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }

            return class_basename($causer::class).' #'.$causer->getKey();
        }

        return '—';
    }

    private static function subjectTitle(?Activity $activity): ?string
    {
        $subject = $activity?->subject;

        if (! $subject instanceof Model) {
            return null;
        }

        foreach (['title', 'name', 'label'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
