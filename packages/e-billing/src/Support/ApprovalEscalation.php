<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Carbon;

/**
 * Pure helpers for overdue-approval escalation age and level selection.
 * Thresholds and level keys come from config; no recipients or wording.
 */
final class ApprovalEscalation
{
    /**
     * @return list<array{key: string, after: int, unit: string}>
     */
    public static function configuredLevels(): array
    {
        $raw = config('e-billing.escalation.levels', []);

        if (! is_array($raw)) {
            return [];
        }

        $levels = [];

        foreach ($raw as $level) {
            if (! is_array($level)) {
                continue;
            }

            $key = trim((string) ($level['key'] ?? ''));
            $after = (int) ($level['after'] ?? 0);
            $unit = strtolower(trim((string) ($level['unit'] ?? '')));

            if ($key === '' || $after < 1 || ! in_array($unit, ['hours', 'days'], true)) {
                continue;
            }

            $levels[] = [
                'key' => $key,
                'after' => $after,
                'unit' => $unit,
            ];
        }

        return $levels;
    }

    /**
     * @return list<string>
     */
    public static function configuredLevelKeys(): array
    {
        return array_map(
            static fn (array $level): string => $level['key'],
            self::configuredLevels(),
        );
    }

    /**
     * @param  array{key: string, after: int, unit: string}  $level
     */
    public static function levelIsMet(array $level, Carbon $createdAt, ?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        if ($level['unit'] === 'hours') {
            return $createdAt->diffInSeconds($now, false) >= ($level['after'] * 3600);
        }

        $dayCounting = (string) config('e-billing.escalation.day_counting', 'working');

        if ($dayCounting === 'calendar') {
            return $createdAt->copy()->startOfDay()->diffInDays($now->copy()->startOfDay(), false) >= $level['after'];
        }

        return self::workingDaysElapsed($createdAt, $now) >= $level['after'];
    }

    /**
     * Count working dates strictly after created_at's date through now's date.
     */
    public static function workingDaysElapsed(Carbon $createdAt, Carbon $now): int
    {
        /** @var list<int> $weekdays */
        $weekdays = array_values(array_map(
            static fn (mixed $day): int => (int) $day,
            is_array(config('e-billing.escalation.working_weekdays'))
                ? config('e-billing.escalation.working_weekdays')
                : [1, 2, 3, 4, 5],
        ));

        $exclude = [];
        $rawExclude = config('e-billing.escalation.exclude_dates', []);
        if (is_array($rawExclude)) {
            foreach ($rawExclude as $date) {
                $exclude[(string) $date] = true;
            }
        }

        $cursor = $createdAt->copy()->startOfDay()->addDay();
        $end = $now->copy()->startOfDay();
        $count = 0;

        while ($cursor->lte($end)) {
            $iso = (int) $cursor->dayOfWeekIso;
            $ymd = $cursor->toDateString();

            if (in_array($iso, $weekdays, true) && ! isset($exclude[$ymd])) {
                $count++;
            }

            $cursor->addDay();
        }

        return $count;
    }
}
