<?php

declare(strict_types=1);

use Moox\EBilling\Support\StoredRelativePath;

test('stored relative path rejects traversal and absolute paths', function (string $path): void {
    StoredRelativePath::assertSafe($path);
})->throws(InvalidArgumentException::class)->with([
    '../secrets.pdf',
    'ebilling/manual-uploads/source/../../.env',
    '/etc/passwd',
    'C:/Windows/win.ini',
    '',
]);

test('stored relative path accepts a normal upload location', function (): void {
    expect(StoredRelativePath::assertUnderDirectory(
        'ebilling/manual-uploads/source/01KZX.pdf',
        'ebilling/manual-uploads/source',
    ))->toBe('ebilling/manual-uploads/source/01KZX.pdf');
});

test('stored relative path rejects files outside the upload directory', function (): void {
    StoredRelativePath::assertUnderDirectory('inbox/other.pdf', 'ebilling/manual-uploads/source');
})->throws(InvalidArgumentException::class);
