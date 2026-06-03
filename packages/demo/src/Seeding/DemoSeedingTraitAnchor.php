<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Illuminate\Database\Seeder;

/**
 * @internal Not registered for seeding — keeps demo seeding traits in PHPStan scope when analysing moox/demo alone.
 */
final class DemoSeedingTraitAnchor extends Seeder
{
    use FormatsFakerLocaleText;
    use LoadsImageMediaPool;
    use ReportsMooxSeederProgress;

    public function run(): void {}
}
