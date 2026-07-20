<?php

declare(strict_types=1);

use Moox\KositValidator\Support\RecursiveFileFinder;

it('finds a file in a nested directory', function (): void {
    $root = sys_get_temp_dir().'/kosit-recursive-finder-'.uniqid('', true);
    mkdir($root.'/nested/deep', 0777, true);
    file_put_contents($root.'/nested/deep/target.txt', 'ok');

    $found = RecursiveFileFinder::find($root, 'target.txt');

    expect($found)->toBe($root.'/nested/deep/target.txt');

    unlink($root.'/nested/deep/target.txt');
    rmdir($root.'/nested/deep');
    rmdir($root.'/nested');
    rmdir($root);
});

it('returns null when the file is not found', function (): void {
    $root = sys_get_temp_dir().'/kosit-recursive-finder-'.uniqid('', true);
    mkdir($root, 0777, true);

    expect(RecursiveFileFinder::find($root, 'missing.txt'))->toBeNull();

    rmdir($root);
});

it('ignores directories that share the target filename when filesOnly is true', function (): void {
    $root = sys_get_temp_dir().'/kosit-recursive-finder-'.uniqid('', true);
    mkdir($root.'/nested/scenarios.xml/real', 0777, true);
    file_put_contents($root.'/nested/scenarios.xml/real/scenarios.xml', '<scenarios/>');

    $found = RecursiveFileFinder::find($root, 'scenarios.xml', filesOnly: true);

    expect($found)->toBe($root.'/nested/scenarios.xml/real/scenarios.xml');

    unlink($root.'/nested/scenarios.xml/real/scenarios.xml');
    rmdir($root.'/nested/scenarios.xml/real');
    rmdir($root.'/nested/scenarios.xml');
    rmdir($root.'/nested');
    rmdir($root);
});
