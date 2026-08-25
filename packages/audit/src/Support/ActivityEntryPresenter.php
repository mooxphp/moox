<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Moox\Audit\Contracts\ActivitySubjectLabelResolver;
use Moox\Audit\Models\Activity;

final class ActivityEntryPresenter
{
    public const CHANGE_VALUE_DISPLAY_LIMIT = 80;

    public const SENSITIVE_VALUE_MASK = SensitiveAttributeGuard::MASK;

    private static function formatPossiblySensitiveValue(string $key, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (SensitiveAttributeGuard::shouldMaskKey($key)) {
            return SensitiveAttributeGuard::MASK;
        }

        return self::formatValue($value);
    }

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
                $oldFormatted = self::formatPossiblySensitiveValue((string) $key, $oldValue) ?? '—';
                $newFormatted = self::formatPossiblySensitiveValue((string) $key, $newValue) ?? '—';
                $result[(string) $key] = $oldFormatted.' → '.$newFormatted;
            } elseif ($newValue !== null) {
                $result[(string) $key] = self::formatPossiblySensitiveValue((string) $key, $newValue) ?? '—';
            } elseif ($oldValue !== null) {
                $result[(string) $key] = self::formatPossiblySensitiveValue((string) $key, $oldValue) ?? '—';
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
            $keyString = (string) $key;
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
                'field' => self::fieldLabel($keyString),
                'old' => self::formatPossiblySensitiveValue($keyString, $oldValue),
                'new' => self::formatPossiblySensitiveValue($keyString, $newValue),
                'kind' => $kind,
            ];
        }

        return $rows;
    }

    /**
     * Compact change list for table columns, e.g. "Status → published, Color: red +1".
     */
    public static function changedFieldsSummary(mixed $changes, int $limit = 3): string
    {
        $items = array_map(
            static fn (array $row): string => self::changeSummaryItem($row),
            self::changeRows($changes),
        );

        if ($items === []) {
            return '—';
        }

        $visible = array_slice($items, 0, max(1, $limit));
        $remaining = count($items) - count($visible);
        $summary = implode(', ', $visible);

        if ($remaining > 0) {
            $summary .= ' +'.$remaining;
        }

        return $summary;
    }

    public static function isFailureEntry(mixed $changes): bool
    {
        return self::failureOutcomeValue($changes) !== null;
    }

    public static function failureOutcomeLabel(mixed $changes): ?string
    {
        $value = self::failureOutcomeValue($changes);

        if ($value === null) {
            return null;
        }

        return self::failureOutcomeValueLabel($value);
    }

    public static function failureOutcomeValueLabel(string $value): ?string
    {
        if (! self::isFailureOutcomeValue($value)) {
            return null;
        }

        $labels = config('audit.failure_outcome_labels', []);

        if (is_array($labels) && isset($labels[$value]) && is_string($labels[$value])) {
            return self::resolveConfiguredLabel($labels[$value]);
        }

        $key = 'core::audit.outcome_'.$value;
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        $fallback = __('core::audit.outcome_failed');

        if ($fallback !== 'core::audit.outcome_failed') {
            return $fallback;
        }

        return Str::of($value)->replace('_', ' ')->headline()->toString();
    }

    public static function listRecordClasses(mixed $changes): ?string
    {
        if (! self::isFailureEntry($changes)) {
            return null;
        }

        return 'border-s-2 border-danger-600 bg-danger-50/40 dark:bg-danger-950/20';
    }

    public static function failureOutcomeValue(mixed $changes): ?string
    {
        foreach (self::changeRows($changes) as $row) {
            $newValue = $row['new'];

            if (! is_string($newValue) || $newValue === '') {
                continue;
            }

            if (self::isFailureOutcomeValue($newValue)) {
                return $newValue;
            }
        }

        return null;
    }

    private static function isFailureOutcomeValue(string $value): bool
    {
        $configured = config('audit.failure_outcome_values', []);

        if (is_array($configured) && in_array($value, $configured, true)) {
            return true;
        }

        if (str_ends_with($value, '_failed')) {
            return true;
        }

        return in_array($value, ['validator_error', 'failed', 'error'], true);
    }

    /**
     * @param  array{field: string, old: ?string, new: ?string, kind: 'changed'|'added'|'removed'}  $row
     */
    private static function changeSummaryItem(array $row): string
    {
        $field = $row['field'];

        return match ($row['kind']) {
            'changed' => $row['new'] !== null
                ? sprintf('%s → %s', $field, self::formatSummaryDisplayValue($row['new']))
                : $field,
            'added' => $row['new'] !== null
                ? sprintf('%s: %s', $field, self::formatSummaryDisplayValue($row['new']))
                : $field,
            'removed' => $row['old'] !== null
                ? sprintf('%s: %s', $field, self::formatSummaryDisplayValue($row['old']))
                : $field,
            default => $field,
        };
    }

    private static function formatSummaryDisplayValue(string $value): string
    {
        $label = self::failureOutcomeValueLabel($value);

        if ($label !== null) {
            return $label;
        }

        return self::truncateSummaryValue($value);
    }

    private static function truncateSummaryValue(string $value, int $limit = 40): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit - 1)).'…';
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

            $structuredValue = self::formatStructuredValue($value);

            if ($structuredValue !== null) {
                return $structuredValue;
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
     * Compact fallback for nested group/repeater/flexible payloads.
     *
     * @param  array<mixed>  $value
     */
    private static function formatStructuredValue(array $value, int $depth = 0): ?string
    {
        if ($depth >= 2 || $value === []) {
            return null;
        }

        if (self::isFlexibleContentItems($value)) {
            $items = [];

            foreach ($value as $item) {
                if (! is_array($item)) {
                    return null;
                }

                $type = isset($item['type']) && is_string($item['type']) && $item['type'] !== ''
                    ? Str::of((string) $item['type'])->replace(['_', '-'], ' ')->headline()->toString()
                    : 'Block';

                $data = isset($item['data']) && is_array($item['data'])
                    ? self::formatAssocSummary($item['data'], $depth + 1)
                    : null;

                $items[] = $data !== null && $data !== ''
                    ? sprintf('%s: %s', $type, $data)
                    : $type;
            }

            return self::joinStructuredItems($items);
        }

        if (array_is_list($value)) {
            $items = [];

            foreach ($value as $item) {
                $formatted = self::formatStructuredItem($item, $depth + 1);

                if ($formatted === null) {
                    return null;
                }

                $items[] = $formatted;
            }

            return self::joinStructuredItems($items);
        }

        return self::formatAssocSummary($value, $depth + 1);
    }

    private static function formatStructuredItem(mixed $item, int $depth): ?string
    {
        if (is_scalar($item) || $item === null || is_bool($item)) {
            return self::formatValue($item);
        }

        if (! is_array($item)) {
            return null;
        }

        $named = self::formatNamedItemList([$item]);

        if ($named !== null) {
            return $named;
        }

        $media = self::formatMediaLikeValue($item);

        if ($media !== null) {
            return $media;
        }

        $link = self::formatLinkLikeValue($item);

        if ($link !== null) {
            return $link;
        }

        return self::formatStructuredValue($item, $depth);
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function formatAssocSummary(array $value, int $depth): ?string
    {
        if ($value === [] || $depth >= 3) {
            return null;
        }

        $parts = [];

        foreach ($value as $key => $item) {
            if (! is_string($key) && ! is_int($key)) {
                continue;
            }

            $formatted = null;

            if (is_scalar($item) || $item === null || is_bool($item)) {
                $formatted = self::formatValue($item);
            } elseif (is_array($item)) {
                $formatted = self::formatStructuredValue($item, $depth);
            }

            if ($formatted === null || $formatted === '—') {
                continue;
            }

            $parts[] = sprintf('%s: %s', self::fieldLabel((string) $key), $formatted);
        }

        if ($parts === []) {
            return null;
        }

        return self::joinStructuredItems($parts, '; ');
    }

    /**
     * @param  list<string>  $items
     */
    private static function joinStructuredItems(array $items, string $separator = ' | ', int $limit = 2): string
    {
        if (count($items) <= $limit) {
            return implode($separator, $items);
        }

        return implode($separator, array_slice($items, 0, $limit)).' +'.(count($items) - $limit);
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function isFlexibleContentItems(array $value): bool
    {
        if (! array_is_list($value) || $value === []) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_array($item) || ! isset($item['type'], $item['data']) || ! is_array($item['data'])) {
                return false;
            }
        }

        return true;
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

            foreach (array_keys($item) as $key) {
                if (! in_array($key, ['id', 'title', 'name', 'label'], true)) {
                    return null;
                }
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

        $resolved = self::resolveConfiguredSubjectLabel($activity);

        if ($resolved !== null) {
            return $resolved;
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
        $config = self::subjectModelConfig($type);
        $configuredLabel = is_array($config) ? ($config['label'] ?? null) : null;

        if (is_string($configuredLabel) && $configuredLabel !== '') {
            return self::resolveConfiguredLabel($configuredLabel);
        }

        $basename = class_basename($type);

        return Str::of($basename)
            ->replaceEnd('Translation', ' translation')
            ->headline()
            ->toString();
    }

    /**
     * Read a subject attribute from the live model, or from the activity snapshot
     * when the subject was deleted.
     */
    public static function subjectAttributeValue(?Activity $activity, string $attribute): mixed
    {
        if ($activity === null || $attribute === '') {
            return null;
        }

        $subject = $activity->subject;

        if ($subject instanceof Model) {
            $value = $subject->getAttribute($attribute);

            if (self::isPresentAttributeValue($value)) {
                return $value;
            }
        }

        $changes = self::normalizeAttributeChanges($activity->attribute_changes);

        foreach (['attributes', 'old'] as $bucket) {
            $bucketValues = is_array($changes[$bucket] ?? null) ? $changes[$bucket] : [];

            if (! array_key_exists($attribute, $bucketValues)) {
                continue;
            }

            $value = $bucketValues[$attribute];

            if (self::isPresentAttributeValue($value)) {
                return $value;
            }
        }

        return null;
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
        if ($activity === null) {
            return null;
        }

        $type = $activity->subject_type;
        $config = is_string($type) ? self::subjectModelConfig($type) : null;
        $titleAttribute = is_array($config) ? ($config['title_attribute'] ?? null) : null;

        if (is_string($titleAttribute) && $titleAttribute !== '') {
            $configuredTitle = self::subjectAttributeValue($activity, $titleAttribute);

            if (is_string($configuredTitle) && $configuredTitle !== '') {
                return $configuredTitle;
            }

            if (is_scalar($configuredTitle) && (string) $configuredTitle !== '') {
                return (string) $configuredTitle;
            }
        }

        foreach (['title', 'name', 'label'] as $attribute) {
            $value = self::subjectAttributeValue($activity, $attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function resolveConfiguredSubjectLabel(Activity $activity): ?string
    {
        $type = $activity->subject_type;

        if (! is_string($type) || $type === '') {
            return null;
        }

        $config = self::subjectModelConfig($type);
        $resolverClass = is_array($config) ? ($config['subject_label_resolver'] ?? null) : null;

        if (! is_string($resolverClass) || $resolverClass === '' || ! class_exists($resolverClass)) {
            return null;
        }

        $resolver = app($resolverClass);

        if (! $resolver instanceof ActivitySubjectLabelResolver) {
            return null;
        }

        $label = $resolver->resolve($activity);

        return is_string($label) && $label !== '' ? $label : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function subjectModelConfig(string $type): ?array
    {
        $modelClass = self::resolveSubjectModelClass($type);

        return AuditConfigResolver::resolveModel($modelClass)
            ?? ($modelClass !== $type ? AuditConfigResolver::resolveModel($type) : null);
    }

    private static function resolveSubjectModelClass(string $type): string
    {
        if (class_exists($type)) {
            return $type;
        }

        $morphed = Relation::getMorphedModel($type);

        return is_string($morphed) && $morphed !== '' ? $morphed : $type;
    }

    /**
     * @param  array<string, mixed>|Collection<string, mixed>|null  $changes
     * @return array<string, mixed>
     */
    private static function normalizeAttributeChanges(array|Collection|null $changes): array
    {
        if ($changes instanceof Collection) {
            return $changes->toArray();
        }

        return is_array($changes) ? $changes : [];
    }

    private static function isPresentAttributeValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return $value !== '';
        }

        return true;
    }

    private static function resolveConfiguredLabel(string $label): string
    {
        if (str_starts_with($label, 'trans//')) {
            return __(substr($label, strlen('trans//')));
        }

        return $label;
    }
}
