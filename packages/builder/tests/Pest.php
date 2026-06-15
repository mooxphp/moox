<?php

declare(strict_types=1);

use Moox\Builder\Tests\TestCase as BuilderPackageTestCase;

require_once __DIR__.'/TestCase.php';

uses(BuilderPackageTestCase::class)->in(__DIR__.'/Unit', __DIR__.'/Feature');
