<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$autoloadCandidates = [
    // Host app beside Workspace/ (Dev/GitHub/web ← Dev/GitHub/Workspace/moox)
    dirname(__DIR__, 3).'/../../web/vendor/autoload.php',
    // Host app beside the monorepo root
    dirname(__DIR__, 3).'/../web/vendor/autoload.php',
    // Monorepo vendor
    dirname(__DIR__, 3).'/vendor/autoload.php',
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
    throw new RuntimeException('Could not locate composer autoload.php for e-billing tests.');
}

foreach (spl_autoload_functions() ?: [] as $autoloader) {
    if (! is_array($autoloader)) {
        continue;
    }

    $loader = $autoloader[0];

    if ($loader instanceof ClassLoader) {
        $loader->addPsr4('Moox\\EBilling\\Tests\\', __DIR__);

        break;
    }
}

require __DIR__.'/Pest.php';
