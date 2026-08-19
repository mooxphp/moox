<?php

it('package declares no models', function () {
    $srcPath = dirname(__DIR__).'/src';
    $files = glob($srcPath.'/Models/*.php');

    expect($files)->toBeEmpty();
});

it('package declares no migrations', function () {
    $dbPath = dirname(__DIR__).'/database/migrations';

    expect(is_dir($dbPath))->toBeFalse();
});
