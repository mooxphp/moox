<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Moox\Audit\Models\Activity;

final class ActivityEntryPresenter
{
    public const CHANGE_VALUE_DISPLAY_LIMIT = 80;

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
                'field' => self::fieldLabel((string) $key),
                'old' => $oldValue !== null ? self::formatValue($oldValue) : null,
                'new' => $newValue !== null ? self::formatValue($newValue) : null,
                'kind' => $kind,
            ];
        }

        return $rows;
    }

    /**
     * Compact field list for table columns, e.g. "Deutsch, Description +1".
     */
    public static function changedFieldsSummary(mixed $changes, int $limit = 3): string
    {
        $labels = array_column(self::changeRows($changes), 'field');

        if ($labels === []) {
            return '—';
        }

        $visible = array_slice($labels, 0, max(1, $limit));
        $remaining = count($labels) - count($visible);
        $summary = implode(', ', $visible);

        if ($remaining > 0) {
            $summary .= ' +'.$remaining;
        }

        return $summary;
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

        if (is_array($value)) {
            if ($value === []) {
                return '—';
            }

            $mediaLabel = self::formatMediaLikeValue($value);

            if ($mediaLabel !== null) {
                return $mediaLabel;
            }

            $linkLabel = self::formatLinkLikeValue($value);

            if ($linkLabel !== null) {
                return $linkLabel;
            }

            $scalarList = self::formatScalarList($value);

            if ($scalarList !== null) {
                return $scalarList;
            }

            $namedList = self::formatNamedItemList($value);

            if ($namedList !== null) {
                return $namedList;
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function formatLinkLikeValue(array $value): ?string
    {
        if (isset($value['id'])) {
            return null;
        }

        $hasUrl = array_key_exists('url', $value);
        $hasLabel = array_key_exists('label', $value);

        if (! $hasUrl && ! $hasLabel) {
            return null;
        }

        foreach (array_keys($value) as $key) {
            if (! in_array($key, ['url', 'label', 'opens_in_new_tab', 'target'], true)) {
                return null;
            }
        }

        $url = is_string($value['url'] ?? null) ? trim($value['url']) : '';
        $label = is_string($value['label'] ?? null) ? trim($value['label']) : '';

        if ($url === '' && $label === '') {
            return null;
        }

        if ($label !== '' && $url !== '') {
            $formatted = sprintf('%s (%s)', $label, $url);
        } else {
            $formatted = $label !== '' ? $label : $url;
        }

        $opensInNewTab = (bool) ($value['opens_in_new_tab'] ?? (($value['target'] ?? null) === '_blank'));

        return $opensInNewTab ? $formatted.' ↗' : $formatted;
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function formatScalarList(array $value): ?string
    {
        if (! array_is_list($value)) {
            return null;
        }

        foreach ($value as $item) {
            if ($item !== null && ! is_scalar($item)) {
                return null;
            }
        }

        $labels = array_map(
            static fn (mixed $item): string => $item === null ? '—' : (string) $item,
            $value,
        );

        return self::joinLimitedLabels($labels);
    }

    /**
     * Relation/option payloads resolved to {id, title|name|label} rows.
     *
     * @param  array<mixed>  $value
     */
    private static function formatNamedItemList(array $value): ?string
    {
        $items = array_is_list($value) ? $value : array_values($value);

        if ($items === []) {
            return null;
        }

        $labels = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                return null;
            }

            $label = null;

            foreach (['title', 'name', 'label'] as $attribute) {
                $candidate = $item[$attribute] ?? null;

                if (is_string($candidate) && $candidate !== '') {
                    $label = $candidate;
                    break;
                }
            }

            if ($label === null && isset($item['id']) && is_scalar($item['id'])) {
                $label = '#'.$item['id'];
            }

            if ($label === null) {
                return null;
            }

            $labels[] = $label;
        }

        return self::joinLimitedLabels($labels);
    }

    /**
     * @param  list<string>  $labels
     */
    private static function joinLimitedLabels(array $labels, int $limit = 3): string
    {
        if (count($labels) <= $limit) {
            return implode(', ', $labels);
        }

        return implode(', ', array_slice($labels, 0, $limit)).' +'.(count($labels) - $limit);
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function formatMediaLikeValue(array $value): ?string
    {
        if (isset($value['id']) && is_numeric($value['id'])) {
            return self::formatSingleMediaItem($value);
        }

        $items = self::mediaLikeItems($value);

        if ($items === null || $items === []) {
            return null;
        }

        $labels = array_map(self::formatSingleMediaItem(...), $items);

        return self::joinLimitedLabels($labels);
    }

    /**
     * @param  array<mixed>  $value
     * @return list<array<mixed>>|null
     */
    private static function mediaLikeItems(array $value): ?array
    {
        if ($value === []) {
            return null;
        }

        if (array_is_list($value)) {
            $items = [];

            foreach ($value as $item) {
                if (is_numeric($item)) {
                    $items[] = ['id' => (int) $item];

                    continue;
                }

                if (is_array($item) && isset($item['id']) && is_numeric($item['id'])) {
                    $items[] = $item;

                    continue;
                }

                return null;
            }

            return $items;
        }

        if (isset($value['id']) && is_numeric($value['id'])) {
            return null;
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_array($item) || ! isset($item['id']) || ! is_numeric($item['id'])) {
                return null;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function formatSingleMediaItem(array $value): string
    {
        $id = (string) $value['id'];

        foreach (['title', 'file_name', 'alt', 'name'] as $attribute) {
            $label = $value[$attribute] ?? null;

            if (is_string($label) && $label !== '') {
                return sprintf('%s (#%s)', $label, $id);
            }
        }

        return '#'.$id;
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

        return $translated !== $key ? $translated : Str::of($event)->replace('_', ' ')->headline()->toString();
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

        $id = $activity->subject_id;

        if ($id !== null && $id !== '') {
            return sprintf('%s #%s', $typeLabel, $id);
        }

        return $typeLabel;
    }

    public static function subjectIsUnavailable(?Activity $activity): bool
    {
        if ($activity === null) {
            return false;
        }

        if (! is_string($activity->subject_type) || $activity->subject_type === '') {
            return false;
        }

        if ($activity->subject_id === null || $activity->subject_id === '') {
            return false;
        }

        return ! ($activity->subject instanceof Model);
    }

    public static function subjectUnavailableHint(?Activity $activity): ?string
    {
        return self::subjectIsUnavailable($activity)
            ? __('core::audit.subject_unavailable')
            : null;
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

    public static function truncatedChangeTooltip(?string $state): ?string
    {
        if (! is_string($state) || $state === '') {
            return null;
        }

        return mb_strlen($state) > self::CHANGE_VALUE_DISPLAY_LIMIT ? $state : null;
    }

    public static function fieldLabel(string $field): string
    {
        return Str::of($field)
            ->replace(['_', '-'], ' ')
            ->headline()
            ->toString();
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
