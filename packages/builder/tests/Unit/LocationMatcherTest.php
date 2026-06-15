<?php

declare(strict_types=1);

use Moox\Builder\Compiler\LocationMatcher;
use Moox\Builder\Data\LocationContext;

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
            ['param' => 'taxonomy', 'operator' => '==', 'value' => 'news'],
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
