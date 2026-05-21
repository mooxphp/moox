<?php

declare(strict_types=1);

use Moox\Address\Tests\FeatureTestCase;
use Moox\Address\Tests\TestCase as AddressPackageTestCase;

$packageTestsPath = dirname(__DIR__).'/tests';

uses(AddressPackageTestCase::class)->in($packageTestsPath.'/Unit');
uses(FeatureTestCase::class)->in($packageTestsPath.'/Feature');
