<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Moox\Builder\Compiler\SchemaCompiler;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

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

it('compiles tabs spanning the full section width', function (): void {
    $definition = FieldGroupDefinition::fromArray([
        'name' => 'Tabbed',
        'slug' => 'tabbed',
        'placement' => 'default',
        'fields' => [
            [
                'name' => 'first_tab',
                'label' => 'First tab',
                'type' => 'tab',
                'children' => [
                    ['name' => 'inside', 'label' => 'Inside', 'type' => 'text'],
                ],
            ],
        ],
    ]);

    $compiler = app(SchemaCompiler::class);
    $sections = $compiler->compile(collect([$definition]));

    $sectionReflection = new ReflectionProperty(Section::class, 'childComponents');
    $sectionReflection->setAccessible(true);
    $children = collect($sectionReflection->getValue($sections[0]))->flatten();

    $tabs = $children->first(fn (mixed $component): bool => $component instanceof Tabs);

    expect($tabs)->toBeInstanceOf(Tabs::class)
        ->and($tabs->getColumnSpan())->toBe(['default' => 'full']);
});

it('collects conditional trigger field names for a field group', function (): void {
    $definition = FieldGroupDefinition::fromArray([
        'name' => 'Conditional',
        'slug' => 'conditional',
        'placement' => 'default',
        'fields' => [
            ['name' => 'customer_type', 'label' => 'Customer type', 'type' => 'text'],
            [
                'name' => 'company',
                'label' => 'Company',
                'type' => 'text',
                'settings' => [
                    'conditions' => [
                        'enabled' => true,
                        'action' => 'show',
                        'logic' => 'and',
                        'rules' => [
                            ['field' => 'customer_type', 'operator' => 'equals', 'value' => 'business'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $compiler = app(SchemaCompiler::class);
    $reflection = new ReflectionClass($compiler);
    $method = $reflection->getMethod('conditionalTriggerNames');
    $method->setAccessible(true);

    expect($method->invoke($compiler, $definition->fields))->toBe(['customer_type'])
        ->and($definition->fields->firstWhere('name', 'company')?->hasConditions())->toBeTrue();
});
