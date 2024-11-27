<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_healthy_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_contains_welcome()
    {
        $response = $this->get('/');

        $response->assertSee('Moox');
    }
}
