<?php

declare(strict_types=1);

use Moox\Contact\Tests\FeatureTestCase;
use Moox\Contact\Tests\TestCase as ContactPackageTestCase;

$packageTestsPath = dirname(__DIR__).'/tests';
require_once $packageTestsPath.'/TestCase.php';
require_once $packageTestsPath.'/FeatureTestCase.php';

uses(ContactPackageTestCase::class)->in($packageTestsPath.'/Unit');
uses(FeatureTestCase::class)->in($packageTestsPath.'/Feature');
