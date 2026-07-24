<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Moox\Transform\Support\ImportRecordSelectOptionBuilder;

test('it formats endpoint group labels with name and route', function (): void {
    $endpoint = new class extends Model
    {
        protected $table = 'api_endpoints';
    };
    $endpoint->forceFill([
        'id' => 2,
        'name' => 'Articlegroup Data',
        'method' => 'get',
        'path' => '/webapi/articlegroups/{id}',
    ]);

    expect(ImportRecordSelectOptionBuilder::formatEndpointGroupLabel($endpoint))
        ->toBe('Articlegroup Data — GET /webapi/articlegroups/{id}');
});

test('it formats import record option labels with external key and status', function (): void {
    $record = new class extends Model
    {
        protected $table = 'api_import_records';

        public $timestamps = false;
    };
    $record->forceFill([
        'id' => 42,
        'external_key' => '109241',
        'status' => 'fetched',
    ]);

    expect(ImportRecordSelectOptionBuilder::formatRecordOptionLabel($record))
        ->toBe('#42 · 109241 · fetched');
});
