<?php

declare(strict_types=1);

$autoloadCandidates = [
    dirname(__DIR__, 3).'/vendor/autoload.php',
    dirname(__DIR__, 2).'/vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;

        break;
    }
}

require_once __DIR__.'/Concerns/CreatesPageSchema.php';
require_once __DIR__.'/Concerns/ProvidesFilament.php';
require_once __DIR__.'/TestCase.php';
require_once __DIR__.'/FeatureTestCase.php';
require_once __DIR__.'/FilamentTestCase.php';
require_once __DIR__.'/helpers.php';
