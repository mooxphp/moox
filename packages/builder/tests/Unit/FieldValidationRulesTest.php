<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Support\FieldValidationRules;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('maps stored validation rules into structured form state', function (): void {
    $rules = app(FieldValidationRules::class);

    $state = $rules->formStateFor([
        'required' => true,
        'rules' => ['min:3', 'regex:/^[a-z-]+$/', 'starts_with:foo'],
    ], 'text');

    expect($state['rule_rows'])->toBe([
        ['rule' => 'min', 'value' => '3'],
        ['rule' => 'regex', 'value' => '/^[a-z-]+$/'],
        ['rule' => 'starts_with', 'value' => 'foo'],
    ])->and($state['raw_rules'])->toBe('');
});

it('compiles structured validation rows back to laravel rules', function (): void {
    $rules = app(FieldValidationRules::class);

    $validation = $rules->compileValidation([
        'required' => true,
        'rule_rows' => [
            ['rule' => 'min', 'value' => '3'],
            ['rule' => 'alpha_dash'],
            ['rule' => 'starts_with', 'value' => 'foo'],
        ],
        'raw_rules' => "starts_with:foo\nends_with:bar",
    ], 'text');

    expect($validation)->toBe([
        'required' => true,
        'rules' => ['min:3', 'alpha_dash', 'starts_with:foo', 'ends_with:bar'],
    ]);
});

it('ignores unsupported raw validation rules during compile', function (): void {
    $rules = app(FieldValidationRules::class);

    $validation = $rules->compileValidation([
        'required' => false,
        'raw_rules' => "starts_with:foo\nprohibited\ninteger",
    ], 'text');

    expect($validation['rules'])->toBe(['starts_with:foo']);
});

it('filters unsupported persisted validation rules at runtime', function (): void {
    $field = new FieldDefinition(
        name: 'slug',
        label: 'Slug',
        type: 'text',
        validation: ['required' => false, 'rules' => ['min:3', 'prohibited', 'alpha_dash']],
    );

    expect(app(FieldValidationRules::class)->runtimeRulesFor($field))
        ->toBe(['min:3', 'alpha_dash']);
});

it('enforces custom text validation rules server side', function (): void {
    $field = new FieldDefinition(
        name: 'slug',
        label: 'Slug',
        type: 'text',
        validation: ['required' => false, 'rules' => ['min:3', 'alpha_dash']],
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, 'a!'))
        ->toThrow(ValidationException::class);
});

it('enforces custom numeric validation rules server side', function (): void {
    $field = new FieldDefinition(
        name: 'price',
        label: 'Price',
        type: 'number',
        validation: ['required' => false, 'rules' => ['gte:10']],
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, 5))
        ->toThrow(ValidationException::class);
});
