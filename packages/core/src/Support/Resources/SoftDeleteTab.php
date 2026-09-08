<?php

declare(strict_types=1);

namespace Moox\Core\Support\Resources;

final class SoftDeleteTab
{
    /** @var list<string> */
    public const TRASH_TABS = ['trash', 'deleted'];

    public static function isTrashTab(?string $tab): bool
    {
        return is_string($tab) && $tab !== '' && in_array($tab, self::TRASH_TABS, true);
    }

    /**
     * @param  (callable(): (?string))|null  $currentTabResolver
     * @param  array<string, mixed>  $requestQuery
     */
    public static function resolve(
        ?string $explicit = null,
        ?callable $currentTabResolver = null,
        array $requestQuery = [],
    ): ?string {
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        if ($currentTabResolver !== null) {
            $current = $currentTabResolver();
            if (is_string($current) && $current !== '') {
                return $current;
            }
        }

        foreach (['tab', 'activeTab'] as $key) {
            $value = $requestQuery[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  (callable(): (?string))|null  $currentTabResolver
     * @param  array<string, mixed>  $requestQuery
     */
    public static function shouldIncludeTrashed(
        ?string $explicit = null,
        ?callable $currentTabResolver = null,
        array $requestQuery = [],
    ): bool {
        return self::isTrashTab(self::resolve($explicit, $currentTabResolver, $requestQuery));
    }
}
