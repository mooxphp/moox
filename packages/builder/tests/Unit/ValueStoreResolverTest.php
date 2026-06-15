<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItemResource.php';

uses(TestCase::class);

use Moox\Builder\Storage\TypedValueDriver;
use Moox\Builder\Storage\ValueStoreResolver;
use Moox\Builder\Tests\TestCase;

it('resolves the typed value driver', function (): void {
    $resolver = app(ValueStoreResolver::class);

    expect($resolver->for())->toBeInstanceOf(TypedValueDriver::class)
        ->and($resolver->for())->toBeInstanceOf(TypedValueDriver::class);
});
