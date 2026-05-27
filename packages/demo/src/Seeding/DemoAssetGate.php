<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

final class DemoAssetGate
{
    public static function enabled(): bool
    {
        if ((bool) config('demo.runtime.skip_media', false) === true) {
            return false;
        }

        return (bool) config('demo.runtime.seeding', false);
    }
}

