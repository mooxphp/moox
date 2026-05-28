<?php

declare(strict_types=1);

use Moox\Address\Tests\FeatureTestCase;
use Moox\Address\Tests\TestCase as AddressPackageTestCase;

$packageTestsPath = dirname(__DIR__).'/tests';
require_once $packageTestsPath.'/TestCase.php';
require_once $packageTestsPath.'/FeatureTestCase.php';

uses(AddressPackageTestCase::class)->in($packageTestsPath.'/Unit');
uses(FeatureTestCase::class)->in($packageTestsPath.'/Feature');
