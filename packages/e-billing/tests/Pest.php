<?php

declare(strict_types=1);

use Moox\EBilling\Tests\ContainerTestCase;

pest()->extends(ContainerTestCase::class)
    ->in('Formats');
