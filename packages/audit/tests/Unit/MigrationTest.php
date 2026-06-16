<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Moox\Audit\Tests\TestCase;

uses(TestCase::class);

it('creates activity_log table with moox columns', function (): void {
    expect(Schema::hasTable('activity_log'))->toBeTrue()
        ->and(Schema::hasColumns('activity_log', [
            'id',
            'log_name',
            'entry_type',
            'scope',
            'description',
            'subject_type',
            'subject_id',
            'event',
            'causer_type',
            'causer_id',
            'attribute_changes',
            'properties',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});
