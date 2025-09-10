<?php

// Simple: Always try to use Laravel TestCase when available
if (class_exists('\Tests\TestCase')) {
    uses(Tests\TestCase::class)->in('Feature', 'Unit');
} else {
    uses(Moox\Item\Tests\TestCase::class)->in('Feature', 'Unit');
}
