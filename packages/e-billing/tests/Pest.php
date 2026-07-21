<?php

declare(strict_types=1);

use Moox\EBilling\Tests\ContainerTestCase;
use Moox\EBilling\Tests\TestCase;

pest()->extends(ContainerTestCase::class)
    ->in('Formats');

pest()->extends(TestCase::class)
    ->in('Feature');
