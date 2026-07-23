<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

/**
 * Emulates Filament's relative "../" state-path resolution so we can verify
 * that the conditional-logic "field" Select resolves its sibling options from
 * the correct repeater depth, mirroring the real editor nesting:
 * fields.{uuid}.settings.conditions.rules.{uuid}.field
 */
function conditionalLogicFieldOptions(array $state, string $ruleContainerPath): array
{
    $resolve = static function (string $path) use ($ruleContainerPath): string {
        $container = $ruleContainerPath;

        while (str_starts_with($path, '../')) {
            $container = str_contains($container, '.')
                ? substr($container, 0, (int) strrpos($container, '.'))
                : '';
            $path = substr($path, 3);
        }

        if ($container === '') {
            return $path;
        }

        return $path === '' ? $container : "{$container}.{$path}";
    };

    $get = static fn (string $path = ''): mixed => data_get($state, $resolve($path));

    $method = new ReflectionMethod(FieldGroupResource::class, 'siblingFieldOptions');
    $method->setAccessible(true);

    /** @var array<string, string> $options */
    $options = $method->invoke(null, $get);

    return $options;
}

it('lists value-storing sibling fields as conditional-logic options', function (): void {
    $state = [
        'fields' => [
            'a' => [
                'name' => 'kundentyp',
                'type' => 'select',
                'label' => 'Kundentyp',
                'settings' => [
                    'conditions' => [
                        'rules' => [
                            'r1' => ['field' => null, 'operator' => 'equals', 'value' => 'business'],
                        ],
                    ],
                ],
            ],
            'b' => ['name' => 'firmenname', 'type' => 'text', 'label' => 'Firmenname'],
            'c' => ['name' => 'section', 'type' => 'tab', 'label' => 'Section'],
        ],
    ];

    $options = conditionalLogicFieldOptions($state, 'fields.a.settings.conditions.rules.r1');

    expect($options)->toBe(['firmenname' => 'Firmenname']);
});

it('returns no options when no sibling fields exist yet', function (): void {
    $state = [
        'fields' => [
            'a' => [
                'name' => 'kundentyp',
                'type' => 'select',
                'label' => 'Kundentyp',
                'settings' => ['conditions' => ['rules' => ['r1' => ['field' => null]]]],
            ],
        ],
    ];

    $options = conditionalLogicFieldOptions($state, 'fields.a.settings.conditions.rules.r1');

    expect($options)->toBe([]);
});
