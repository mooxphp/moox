<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Moox\Demo\Console\DemoConsole;
use Moox\Demo\Console\DemoProgressBar;

final class SeedOutput
{
    private static ?DemoConsole $console = null;

    public static function bind(?DemoConsole $console): void
    {
        self::$console = $console;
    }

    public static function isBound(): bool
    {
        return self::$console !== null;
    }

    public static function created(string $label): void
    {
        self::$console?->created($label);
    }

    public static function detail(string $line): void
    {
        self::$console?->detail($line);
    }

    public static function updateTask(string $label): void
    {
        self::$console?->updateTask($label);
    }

    public static function progressBar(int $max, string $message): DemoProgressBar
    {
        if (self::$console === null) {
            throw new \RuntimeException('SeedOutput is not bound to a DemoConsole.');
        }

        return self::$console->progressBar($max, $message);
    }
}
