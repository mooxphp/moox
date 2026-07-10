<?php

declare(strict_types=1);

use Moox\Static\Tests\FeatureTestCase;
use Moox\Static\Tests\TestCase as StaticPackageTestCase;

$packageTestsPath = dirname(__DIR__).'/tests';
require_once $packageTestsPath.'/TestCase.php';
require_once $packageTestsPath.'/FeatureTestCase.php';

uses(StaticPackageTestCase::class)->in($packageTestsPath.'/Unit');
uses(FeatureTestCase::class)->in($packageTestsPath.'/Feature');
