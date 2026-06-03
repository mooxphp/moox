<?php

declare(strict_types=1);

use Moox\Company\Tests\FeatureTestCase;
use Moox\Company\Tests\TestCase as CompanyPackageTestCase;

$packageTestsPath = dirname(__DIR__).'/tests';
require_once $packageTestsPath.'/TestCase.php';
require_once $packageTestsPath.'/FeatureTestCase.php';

uses(CompanyPackageTestCase::class)->in($packageTestsPath.'/Unit');
uses(FeatureTestCase::class)->in($packageTestsPath.'/Feature');
