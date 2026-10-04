<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Livewire\Livewire;
use Moox\Definition\Tests\Support\SchemaHost;
use Moox\Definition\Tests\TestCase;

pest()->extends(TestCase::class)->in('Feature', 'Unit');

function definitionSchema(): Schema
{
    $component = Livewire::test(SchemaHost::class)->instance();

    if (! $component instanceof SchemaHost) {
        throw new RuntimeException('Schema host did not boot.');
    }

    return Schema::make($component);
}
