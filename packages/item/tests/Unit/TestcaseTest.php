<?php

use Tests\TestCase;

it('shows which testcase is being used', function () {
    // Aktuelle Test-Klasse Info
    $testClass = get_class($this);
    $parentClass = get_parent_class($this);

    dump();
    dump('=== TESTCASE USAGE ===');
    if ($this instanceof TestCase) {
        dump('✅ Using Root Laravel TestCase');
        dump('TestCase file: '.(new ReflectionClass('Tests\\TestCase'))->getFileName());
    } elseif ($this instanceof Moox\Item\Tests\TestCase) {
        dump('📦 Using Package TestCase (Testbench)');
        dump('TestCase file: '.(new ReflectionClass('Moox\\Item\\Tests\\TestCase'))->getFileName());
    } else {
        dump('❌ Using fallback PHPUnit TestCase');
    }

    expect(true)->toBeTrue();
});
