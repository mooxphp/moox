<?php

declare(strict_types=1);

use Moox\Builder\Compiler\LocationMatcher;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Services\FieldGroupPersistence;

it('matches entity equals and not equals rules with OR groups', function (): void {
    $matcher = new LocationMatcher;
    $context = new LocationContext('item');

    $rules = [
        [
            ['param' => 'entity', 'operator' => '==', 'value' => 'page'],
        ],
        [
            ['param' => 'entity', 'operator' => '==', 'value' => 'item'],
        ],
    ];

    expect($matcher->matches($rules, $context))->toBeTrue();

    $notRules = [
        [
            ['param' => 'entity', 'operator' => '!=', 'value' => 'item'],
        ],
    ];

    expect($matcher->matches($notRules, $context))->toBeFalse();
});

it('fails closed for unknown location params', function (): void {
    $matcher = new LocationMatcher;
    $context = new LocationContext('item');

    $rules = [
        [
            ['param' => 'template', 'operator' => '==', 'value' => 'landing'],
        ],
    ];

    expect($matcher->matches($rules, $context))->toBeFalse();
});

it('requires all rules in an AND group to match', function (): void {
    $matcher = new LocationMatcher;
    $context = new LocationContext('item');

    $rules = [
        [
            ['param' => 'entity', 'operator' => '==', 'value' => 'item'],
            ['param' => 'entity', 'operator' => '==', 'value' => 'page'],
        ],
    ];

    expect($matcher->matches($rules, $context))->toBeFalse();
});

it('does not match any entity when location rules are empty', function (): void {
    $matcher = new LocationMatcher;

    expect($matcher->matches([], new LocationContext('item')))->toBeFalse()
        ->and($matcher->matches([], new LocationContext('record')))->toBeFalse();
});

it('matches record type and user role params when present in the context', function (): void {
    $matcher = new LocationMatcher;

    $rules = [[
        ['param' => 'entity', 'operator' => '==', 'value' => 'draft'],
        ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
        ['param' => 'user_role', 'operator' => 'in', 'value' => 'admin,editor'],
    ]];

    $context = new LocationContext('draft', [
        'record_type' => 'page',
        'user_role' => ['editor'],
    ]);

    expect($matcher->matches($rules, $context))->toBeTrue();
});

it('matches taxonomy term ids using taxonomy param prefixes', function (): void {
    $matcher = new LocationMatcher;

    $rules = [[
        ['param' => 'entity', 'operator' => '==', 'value' => 'draft'],
        ['param' => 'taxonomy:tag', 'operator' => 'in', 'value' => '12,99'],
    ]];

    $context = new LocationContext('draft', [
        'taxonomy:tag' => [12, 34],
    ]);

    expect($matcher->matches($rules, $context))->toBeTrue();
});

it('ignores record specific params on create forms without a record', function (): void {
    $matcher = new LocationMatcher;

    $rules = [[
        ['param' => 'entity', 'operator' => '==', 'value' => 'draft'],
        ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
    ]];

    expect($matcher->matches($rules, new LocationContext('draft')))->toBeTrue();
});

it('ignores record specific params when the param is missing on an existing record', function (): void {
    $matcher = new LocationMatcher;

    $rules = [[
        ['param' => 'entity', 'operator' => '==', 'value' => 'item'],
        ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
    ]];

    $record = new class extends \Illuminate\Database\Eloquent\Model
    {
    };

    expect($matcher->matches($rules, new LocationContext('item', [], $record)))
        ->toBeTrue();
});

it('does not ignore record specific params when the param is present and mismatches', function (): void {
    $matcher = new LocationMatcher;

    $rules = [[
        ['param' => 'entity', 'operator' => '==', 'value' => 'item'],
        ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
    ]];

    $record = new class extends \Illuminate\Database\Eloquent\Model
    {
    };

    expect($matcher->matches($rules, new LocationContext('item', ['record_type' => 'post'], $record)))
        ->toBeFalse();
});

it('matches entity scope for cached definition loading', function (): void {
    $matcher = new LocationMatcher;

    $rules = [[
        ['param' => 'entity', 'operator' => '==', 'value' => 'draft'],
        ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
    ]];

    expect($matcher->matchesEntityScope($rules, 'draft'))->toBeTrue()
        ->and($matcher->matchesEntityScope($rules, 'item'))->toBeFalse();
});

it('merges location constraints into every selected entity rule group', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    $rules = $persistence->mergeEntityRulesWithConstraints(
        ['item', 'draft'],
        [
            [
                'param' => 'record_type',
                'operator' => '==',
                'value' => 'page',
            ],
            [
                'param' => 'taxonomy',
                'taxonomy' => 'tag',
                'operator' => 'in',
                'value' => '12, 34',
            ],
        ],
    );

    expect($rules)->toBe([
        [
            ['param' => 'entity', 'operator' => '==', 'value' => 'item'],
            ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
            ['param' => 'taxonomy:tag', 'operator' => 'in', 'value' => [12, 34]],
        ],
        [
            ['param' => 'entity', 'operator' => '==', 'value' => 'draft'],
            ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
            ['param' => 'taxonomy:tag', 'operator' => 'in', 'value' => [12, 34]],
        ],
    ]);
});

it('round trips location constraints through form extraction helpers', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    $rules = [
        [
            ['param' => 'entity', 'operator' => '==', 'value' => 'draft'],
            ['param' => 'record_type', 'operator' => '==', 'value' => 'page'],
            ['param' => 'taxonomy:tag', 'operator' => 'in', 'value' => [12, 34]],
        ],
    ];

    expect($persistence->constraintsFromLocationRules($rules))->toBe([
        [
            'param' => 'record_type',
            'operator' => '==',
            'value' => 'page',
        ],
        [
            'param' => 'taxonomy',
            'taxonomy' => 'tag',
            'operator' => 'in',
            'value' => [12, 34],
        ],
    ]);
});
