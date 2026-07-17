<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\VeraPdf\Support\SafeZipExtractor;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

it('extracts a benign zip under the target directory', function (): void {
    $zipPath = sys_get_temp_dir().'/verapdf-safe-zip-'.uniqid('', true).'.zip';
    $target = sys_get_temp_dir().'/verapdf-safe-out-'.uniqid('', true);

    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE))->toBeTrue();
    $zip->addFromString('nested/readme.txt', 'ok');
    $zip->close();

    SafeZipExtractor::extract($zipPath, $target);

    expect(is_file($target.'/nested/readme.txt'))->toBeTrue()
        ->and((string) file_get_contents($target.'/nested/readme.txt'))->toBe('ok');

    File::delete($zipPath);
    File::deleteDirectory($target);
});

it('rejects zip entries with parent-path segments', function (): void {
    $zipPath = sys_get_temp_dir().'/verapdf-slip-zip-'.uniqid('', true).'.zip';
    $target = sys_get_temp_dir().'/verapdf-slip-out-'.uniqid('', true);
    File::ensureDirectoryExists($target);

    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE))->toBeTrue();
    $zip->addFromString('../evil.txt', 'pwned');
    $zip->close();

    expect(fn () => SafeZipExtractor::extract($zipPath, $target))
        ->toThrow(RuntimeException::class, 'unsafe ZIP entry');

    expect(is_file(dirname($target).'/evil.txt'))->toBeFalse();

    File::delete($zipPath);
    File::deleteDirectory($target);
});

it('rejects absolute zip entries', function (): void {
    $zipPath = sys_get_temp_dir().'/verapdf-abs-zip-'.uniqid('', true).'.zip';
    $target = sys_get_temp_dir().'/verapdf-abs-out-'.uniqid('', true);
    File::ensureDirectoryExists($target);

    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE))->toBeTrue();
    $zip->addFromString('/tmp/absolute-evil.txt', 'pwned');
    $zip->close();

    expect(fn () => SafeZipExtractor::extract($zipPath, $target))
        ->toThrow(RuntimeException::class, 'unsafe ZIP entry');

    File::delete($zipPath);
    File::deleteDirectory($target);
});

it('rejects zip entries with current-directory segments', function (): void {
    $zipPath = sys_get_temp_dir().'/verapdf-dot-zip-'.uniqid('', true).'.zip';
    $target = sys_get_temp_dir().'/verapdf-dot-out-'.uniqid('', true);
    File::ensureDirectoryExists($target);

    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE))->toBeTrue();
    $zip->addFromString('foo/./evil.txt', 'pwned');
    $zip->close();

    expect(fn () => SafeZipExtractor::extract($zipPath, $target))
        ->toThrow(RuntimeException::class, 'unsafe ZIP entry');

    File::delete($zipPath);
    File::deleteDirectory($target);
});

it('rejects symlink zip entries', function (): void {
    $zipPath = sys_get_temp_dir().'/verapdf-link-zip-'.uniqid('', true).'.zip';
    $target = sys_get_temp_dir().'/verapdf-link-out-'.uniqid('', true);
    File::ensureDirectoryExists($target);

    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE))->toBeTrue();
    $zip->addFromString('escape-link', '/tmp/outside');
    $zip->setExternalAttributesName('escape-link', ZipArchive::OPSYS_UNIX, (0o120000 | 0o777) << 16);
    $zip->close();

    expect(fn () => SafeZipExtractor::extract($zipPath, $target))
        ->toThrow(RuntimeException::class, 'unsafe ZIP entry');

    File::delete($zipPath);
    File::deleteDirectory($target);
});

it('throws when the archive cannot be opened', function (): void {
    $path = sys_get_temp_dir().'/verapdf-not-a-zip-'.uniqid('', true).'.zip';
    file_put_contents($path, 'not-a-zip');

    $target = sys_get_temp_dir().'/verapdf-not-zip-out-'.uniqid('', true);

    expect(fn () => SafeZipExtractor::extract($path, $target))
        ->toThrow(RuntimeException::class, 'Cannot open ZIP');

    File::delete($path);
});
