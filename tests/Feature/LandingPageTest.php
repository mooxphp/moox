<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function testHealthyResponse()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testContainsWelcome()
    {
        $response = $this->get('/');

        $response->assertSee('Moox');
    }
}
