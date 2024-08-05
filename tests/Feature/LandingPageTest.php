<?php

namespace Tests\Feature;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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

        $response->assertSee('Welcome');
    }
}
