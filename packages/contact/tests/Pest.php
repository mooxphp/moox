<?php

declare(strict_types=1);

use Moox\Contact\Tests\FeatureTestCase;

$packageTestsPath = dirname(__DIR__).'/tests';
require_once $packageTestsPath.'/TestCase.php';
require_once $packageTestsPath.'/FeatureTestCase.php';

uses(FeatureTestCase::class)->in($packageTestsPath.'/Feature');
