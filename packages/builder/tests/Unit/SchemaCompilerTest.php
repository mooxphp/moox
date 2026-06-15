<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

uses(TestCase::class);

use Filament\Schemas\Components\Section;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Tests\TestCase;

it('compiles a section with sorted fields and merged rules', function (): void {
    $group = FieldGroup::query()->create([
        'name' => 'Vehicle data',
        'slug' => 'vehicle-data',
        'location_rules' => [],
        'active' => true,
    ]);

    $group->fields()->createMany([
        [
            'name' => 'third',
            'label' => 'Third',
            'type' => 'text',
            'sort' => 2,
            'validation' => ['required' => false, 'rules' => []],
        ],
        [
            'name' => 'first',
            'label' => 'First',
            'type' => 'text',
            'sort' => 0,
            'validation' => ['required' => true, 'rules' => []],
        ],
        [
            'name' => 'second',
            'label' => 'Second',
            'type' => 'number',
            'sort' => 1,
            'config' => ['min' => 1],
            'validation' => ['required' => false, 'rules' => []],
        ],
    ]);

    $group->load('fields.options');
    $definition = FieldGroupDefinition::fromModel($group);

    $compiler = app(SchemaCompiler::class);
    $sections = $compiler->compile(collect([$definition]));

    expect($sections)->toHaveCount(1)
        ->and($sections[0])->toBeInstanceOf(Section::class)
        ->and($definition->fields)->toHaveCount(3)
        ->and($definition->fields->pluck('name')->all())->toBe(['first', 'second', 'third']);

    $rules = $compiler->rules(collect([$definition]));

    expect($rules['first'])->toContain('required')
        ->and($rules['second'])->toContain('min:1');
});
