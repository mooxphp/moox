<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Moox\Media\Support\MediaUploadValidator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'media.upload.resource.accepted_file_types' => [
            'image/*',
            'application/pdf',
            '.txt',
        ],
    ]);
});

it('accepts files matching mime wildcards from resource config', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg');

    app(MediaUploadValidator::class)->ensureAccepted($file);

    expect(true)->toBeTrue();
});

it('accepts files matching an exact mime type', function (): void {
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    app(MediaUploadValidator::class)->ensureAccepted($file);

    expect(true)->toBeTrue();
});

it('accepts files matching an extension allowlist entry', function (): void {
    $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

    app(MediaUploadValidator::class)->ensureAccepted($file);

    expect(true)->toBeTrue();
});

it('rejects files that are not in the allowlist', function (): void {
    $file = UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload');

    expect(fn () => app(MediaUploadValidator::class)->ensureAccepted($file))
        ->toThrow(ValidationException::class);
});

it('uses an explicit allowlist when provided instead of resource config', function (): void {
    $file = UploadedFile::fake()->image('photo.jpg');

    expect(fn () => app(MediaUploadValidator::class)->ensureAccepted($file, ['application/pdf']))
        ->toThrow(ValidationException::class);
});

it('skips validation when the allowlist is empty', function (): void {
    config(['media.upload.resource.accepted_file_types' => []]);

    $file = UploadedFile::fake()->create('anything.bin', 10, 'application/octet-stream');

    app(MediaUploadValidator::class)->ensureAccepted($file);

    expect(true)->toBeTrue();
});

it('treats null api allowlist as resource fallback by passing null', function (): void {
    $file = UploadedFile::fake()->image('photo.png');

    // MediaStoreRequest passes null when config media.upload.api.accepted_file_types is null.
    app(MediaUploadValidator::class)->ensureAccepted($file, null);

    expect(true)->toBeTrue();
});
