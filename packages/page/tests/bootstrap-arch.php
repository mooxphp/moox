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

uses(Pest\Arch\Concerns\Architectable::class)->in(__DIR__);
