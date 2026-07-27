<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

    public static function subjectLabel(?Activity $activity): string
    {
        if ($activity === null) {
            return '—';
        }

        $type = $activity->subject_type;
        $id = $activity->subject_id;

        if (! is_string($type) || $type === '') {
            return '—';
        }

        $basename = class_basename($type);
        $idLabel = $id === null || $id === '' ? '—' : (string) $id;
        $base = sprintf('%s #%s', $basename, $idLabel);

        $subject = $activity->subject;

        if (! $subject instanceof Model) {
            return $base;
        }

        foreach (['title', 'name', 'label'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return sprintf('%s (%s)', $base, $value);
            }
        }

        return $base;
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
}
