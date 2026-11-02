<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * ViewInvoice collapsible `<details>` open state for one page visit.
 *
 * Soft force-open: serverOpen false→true seeds user open once; the reviewer may
 * close afterward. Clearing serverOpen keeps the user preference. Page-session
 * only — Livewire public props, not browser storage.
 */
final class CollapsibleOpen
{
    /**
     * @param  array<string, bool>  $userOpen
     * @param  array<string, bool>  $forceSeeded
     * @return array{open: bool, userOpen: array<string, bool>, forceSeeded: array<string, bool>}
     */
    public static function resolve(
        string $key,
        bool $serverOpen,
        array $userOpen,
        array $forceSeeded,
    ): array {
        if ($serverOpen) {
            if (! ($forceSeeded[$key] ?? false)) {
                $userOpen[$key] = true;
                $forceSeeded[$key] = true;
            }
        } else {
            unset($forceSeeded[$key]);
        }

        return [
            'open' => $userOpen[$key] ?? false,
            'userOpen' => $userOpen,
            'forceSeeded' => $forceSeeded,
        ];
    }

    /**
     * @param  array<string, bool>  $userOpen
     * @return array<string, bool>
     */
    public static function userSet(string $key, bool $open, array $userOpen): array
    {
        $userOpen[$key] = $open;

        return $userOpen;
    }
}
