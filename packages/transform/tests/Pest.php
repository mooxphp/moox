<?php

declare(strict_types=1);

use Moox\Transform\Tests\TestCase;

require_once __DIR__.'/Support/TransformTestSupport.php';

pest()->extends(TestCase::class)->in('Feature', 'Unit');
