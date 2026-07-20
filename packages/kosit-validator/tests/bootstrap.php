<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$autoloadCandidates = [
    dirname(__DIR__, 3).'/vendor/autoload.php',
    dirname(__DIR__, 3).'/../../web/vendor/autoload.php',
    dirname(__DIR__, 3).'/../web/vendor/autoload.php',
];

$autoloadLoaded = false;

foreach ($autoloadCandidates as $candidate) {
    $resolved = realpath($candidate);

    if ($resolved !== false && is_file($resolved)) {
        require $resolved;
        $autoloadLoaded = true;

        break;
    }
}

if (! $autoloadLoaded) {
    throw new RuntimeException('Could not locate composer autoload.php for kosit-validator tests.');
}

foreach (spl_autoload_functions() ?: [] as $autoloader) {
    if (! is_array($autoloader)) {
        continue;
    }

    $loader = $autoloader[0];

    if ($loader instanceof ClassLoader) {
        $loader->addPsr4('Moox\\KositValidator\\Tests\\', __DIR__, prepend: true);
        $loader->addPsr4('Moox\\KositValidator\\', dirname(__DIR__).'/src', prepend: true);

        break;
    }
}

require __DIR__.'/Pest.php';
