<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static;

use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Core\Entities\Items\Static\Concerns\HasStaticCodelistHelpers;
use Moox\Core\Entities\Items\Static\Concerns\HasStaticResourceActions;

/**
 * Lean Filament resource base for static reference data with astrotomic translations.
 * Reuses BaseItemResource action lists; overrides only Static locale / hard-delete behaviour.
 */
abstract class BaseStaticResource extends BaseItemResource
{
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
