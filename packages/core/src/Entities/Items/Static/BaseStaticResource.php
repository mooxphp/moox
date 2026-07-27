<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static;

use Moox\Core\Entities\BaseResource;
use Moox\Core\Entities\Items\Static\Concerns\HasStaticCodelistHelpers;
use Moox\Core\Entities\Items\Static\Concerns\HasStaticResourceActions;
use Moox\Core\Traits\Tabs\HasResourceTabs;

/**
 * Lean Filament resource base for static reference data with astrotomic translations.
 * No draft/publishing or soft-deleted translation workflow — avoids BaseResource::withTrashed() paths.
 */
abstract class BaseStaticResource extends BaseResource
{
    use HasResourceTabs;
    use HasStaticCodelistHelpers;
    use HasStaticResourceActions;

    protected static function getEntityType(): string
    {
        return 'static';
    }

    protected static function getReadonlyConfig(): bool
    {
        return (bool) config('static.readonly', false);
    }

    public static function enableCreate(): bool
    {
        return ! static::getReadonlyConfig();
    }

    public static function enableEdit(): bool
    {
        return ! static::getReadonlyConfig();
    }

    public static function enableView(): bool
    {
        return true;
    }

    public static function enableDelete(): bool
    {
        return ! static::getReadonlyConfig();
    }

    public static function enableRestore(): bool
    {
        return false;
    }
}
