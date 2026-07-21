<?php

declare(strict_types=1);

use Moox\VeraPdf\Tests\TestCase;

require_once __DIR__.'/Helpers.php';

pest()->extends(TestCase::class)
    ->in('Feature', 'Unit');
