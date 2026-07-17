<?php

declare(strict_types=1);

use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Tests\ContainerTestCase;

uses(ContainerTestCase::class);

test('create_ebilling_documents stub declares format, hash, and renamed storage columns', function (): void {
    $stub = file_get_contents(dirname(__DIR__, 2).'/database/migrations/create_ebilling_documents_table.php.stub');

    expect($stub)->toContain("\$table->string('format')->default('zugferd')")
        ->and($stub)->toContain("\$table->string('artifact_content_hash')->nullable()")
        ->and($stub)->toContain("\$table->string('storage_disk')->nullable()")
        ->and($stub)->toContain("\$table->string('pdf_storage_path')->nullable()")
        ->and($stub)->toContain("\$table->string('xml_storage_path')->nullable()")
        ->and($stub)->not->toContain('zugferd_storage_path')
        ->and($stub)->not->toContain('zugferd_storage_disk')
        ->and($stub)->toMatch('/\$table->string\(\'scope\'\)->nullable\(\)->index\(\);\s*\}\);/s');
});

test('EbillingDocument fillable and default format mirror the schema', function (): void {
    $document = new EbillingDocument;

    expect($document->getFillable())->toContain(
        'format',
        'artifact_content_hash',
        'storage_disk',
        'pdf_storage_path',
        'xml_storage_path',
    )
        ->and($document->getFillable())->not->toContain('zugferd_storage_path')
        ->and($document->getFillable())->not->toContain('zugferd_storage_disk')
        ->and($document->getAttributes()['format'] ?? $document->format)->toBe('zugferd');
});
