<?php

declare(strict_types=1);

use Moox\Transform\Contracts\ImportRecordPayloadReader;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\ConfiguredImportRecordPayloadReader;
use Moox\Transform\Support\SourcePayloadResolver;
use Moox\Transform\Support\TemplateValueResolver;
use Moox\Transform\Tests\Support\FakeImportRecordPayloadReader;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';
require_once dirname(__DIR__, 2).'/Support/FakeImportRecordPayloadReader.php';

uses(TestCase::class);

beforeEach(function (): void {
    createTestTables();
    FakeImportRecordPayloadReader::$payloads = [];
    app()->instance(ImportRecordPayloadReader::class, new FakeImportRecordPayloadReader);
});

test('it resolves detail import record payloads for single transforms', function (): void {
    FakeImportRecordPayloadReader::$payloads[55] = [
        'ArticleGroup' => ['Id' => 1883, 'Name' => 'Example'],
        'FileName' => 'article_group_data_1883_de.json',
    ];

    $definition = TransformDefinition::query()->create([
        'name' => 'detail-import-record',
        'destination_model' => TransformDummyModel::class,
        'destination_match' => ['price_label' => 'ArticleGroup.Id'],
        'source_references' => [
            [
                'source_type' => 'api_import_record',
                'record_id' => '{{context.import_record_id}}',
            ],
        ],
        'field_map' => [
            'title' => 'ArticleGroup.Name',
            'price_label' => 'ArticleGroup.Id',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'context' => [
                'import_record_id' => 55,
            ],
        ],
    ]);

    $resolver = new SourcePayloadResolver(
        new ConfiguredImportRecordPayloadReader,
        new TemplateValueResolver,
    );

    $resolution = $resolver->resolve($record, $definition);

    expect($resolution['payload']['ArticleGroup']['Id'] ?? null)->toBe(1883)
        ->and($resolution['payload']['ArticleGroup']['Name'] ?? null)->toBe('Example');
});

test('it skips list import record payloads in the resolver', function (): void {
    FakeImportRecordPayloadReader::$payloads[56] = [
        ['DataId' => 1, 'Name' => 'One'],
        ['DataId' => 2, 'Name' => 'Two'],
    ];

    $definition = TransformDefinition::query()->create([
        'name' => 'list-import-record',
        'destination_model' => TransformDummyModel::class,
        'destination_match' => ['price_label' => 'defaults.status'],
        'source_references' => [
            [
                'source_type' => 'static',
                'alias' => 'defaults',
                'data' => ['status' => 'active'],
            ],
            [
                'source_type' => 'api_import_record',
                'record_id' => '{{context.import_record_id}}',
                'alias' => 'node',
            ],
        ],
        'field_map' => [
            'title' => 'node.Name',
            'price_label' => 'defaults.status',
        ],
        'validation_rules' => [],
        'is_active' => true,
    ]);

    $record = TransformRecord::query()->create([
        'transform_definition_id' => $definition->id,
        'source_projection' => [
            'context' => [
                'import_record_id' => 56,
            ],
        ],
    ]);

    $resolver = new SourcePayloadResolver(
        new ConfiguredImportRecordPayloadReader,
        new TemplateValueResolver,
    );

    $resolution = $resolver->resolve($record, $definition);

    expect($resolution['payload']['defaults']['status'] ?? null)->toBe('active')
        ->and($resolution['payload']['node'] ?? 'missing')->toBe('missing');
});
