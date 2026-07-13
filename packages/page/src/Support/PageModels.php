<?php

declare(strict_types=1);

namespace Moox\Page\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Moox\Page\Models\Page;
use Moox\Page\Models\PageTranslation;

final class PageModels
{
    /**
     * @return class-string<Page>
     */
    public static function page(): string
    {
        return self::resolve('page.models.page', Page::class);
    }

    /**
     * @return class-string<PageTranslation>
     */
    public static function pageTranslation(): string
    {
        return self::resolve('page.models.page_translation', PageTranslation::class);
    }

    /**
     * @param  class-string  $fallback
     * @return class-string
     */
    private static function resolve(string $configKey, string $fallback): string
    {
        $configured = function_exists('app') && app()->bound('config')
            ? app('config')->get($configKey)
            : null;

        $class = is_string($configured) && $configured !== '' ? $configured : $fallback;

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Configured class for [{$configKey}] does not exist: {$class}");
        }

        if (! is_a($class, Model::class, true)) {
            throw new InvalidArgumentException("Configured class for [{$configKey}] must extend ".Model::class.": {$class}");
        }

        return $class;
    }
}
