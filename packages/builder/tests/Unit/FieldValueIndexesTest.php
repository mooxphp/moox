<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Illuminate\Support\Facades\Schema;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('creates string and boolean value indexes for filter and sort queries', function (): void {
    expect(Schema::hasIndex('builder_field_values', 'bfv_entity_field_locale_string_index'))->toBeTrue()
        ->and(Schema::hasIndex('builder_field_values', 'bfv_entity_field_locale_boolean_index'))->toBeTrue();
});
