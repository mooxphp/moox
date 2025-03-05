<?php

if (file_exists('.env')) {
    echo "Error: .env file already exists.\n";
    exit(1);
}

$env = file_get_contents('.env.example');

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $env = str_replace('DEVLINK_PACKAGES_PATH=packages', 'DEVLINK_PACKAGES_PATH=packageslocal', $env);
    if (strpos($env, 'DEVLINK_PACKAGES_PATH=packageslocal') === false) {
        $env = $env."\nDEVLINK_PACKAGES_PATH=packageslocal";
    }
}

file_put_contents('.env', $env);

if (file_exists('composer.json-deploy')) {
    copy('composer.json-linked', 'composer.json');
}

exec('composer update');
