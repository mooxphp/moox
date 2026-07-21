<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\VeraPdf\Support\SafeZipExtractor;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

it('extracts a benign zip under the target directory', function (): void {
    $zipPath = buildZipAt('safe-zip', [
        ['name' => 'nested/readme.txt', 'content' => 'ok'],
    ]);
    $target = verapdfTempDir('safe-out');

    SafeZipExtractor::extract($zipPath, $target);

    expect(is_file($target.'/nested/readme.txt'))->toBeTrue()
        ->and((string) file_get_contents($target.'/nested/readme.txt'))->toBe('ok');

    File::delete($zipPath);
    File::deleteDirectory($target);
});

it('rejects unsafe zip entries', function (array $entry, ?string $escapedFileName): void {
    $zipPath = buildZipAt('unsafe-zip', [$entry]);
    $target = verapdfTempDir('unsafe-out');

    expect(fn () => SafeZipExtractor::extract($zipPath, $target))
        ->toThrow(RuntimeException::class, 'unsafe ZIP entry');

    if ($escapedFileName !== null) {
        expect(is_file(dirname($target).'/'.$escapedFileName))->toBeFalse();
    }

    File::delete($zipPath);
    File::deleteDirectory($target);
})->with([
    'parent-path segments' => [['name' => '../evil.txt', 'content' => 'pwned'], 'evil.txt'],
    'absolute entries' => [['name' => '/tmp/absolute-evil.txt', 'content' => 'pwned'], null],
    'current-directory segments' => [['name' => 'foo/./evil.txt', 'content' => 'pwned'], null],
    'symlink entries' => [['name' => 'escape-link', 'content' => '/tmp/outside', 'symlink' => true], null],
]);

it('throws when the archive cannot be opened', function (): void {
    $path = verapdfTempPath('not-a-zip').'.zip';
    file_put_contents($path, 'not-a-zip');

    $target = verapdfTempDir('not-zip-out');

    expect(fn () => SafeZipExtractor::extract($path, $target))
        ->toThrow(RuntimeException::class, 'Cannot open ZIP');

    File::delete($path);
});
