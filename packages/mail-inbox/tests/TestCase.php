<?php

declare(strict_types=1);

namespace Moox\MailInbox\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as AppTestCase;

class TestCase extends AppTestCase
{
    use RefreshDatabase;
}
