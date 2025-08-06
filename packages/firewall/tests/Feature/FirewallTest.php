<?php

namespace Moox\Firewall\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class FirewallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['firewall.enabled' => true]);
        config(['firewall.backdoor' => true]);
        config(['firewall.backdoor_token' => 'test-token']);
    }

    public function test_firewall_blocks_access_when_enabled()
    {
        config(['firewall.enabled' => true]);
        config(['firewall.backdoor' => false]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Access denied');
    }

    public function test_firewall_allows_access_when_disabled()
    {
        config(['firewall.enabled' => false]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Access denied');
    }

    public function test_firewall_allows_whitelisted_ips()
    {
        config(['firewall.whitelist' => ['127.0.0.1']]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Access denied');
    }

    public function test_firewall_excludes_specified_routes()
    {
        config(['firewall.exclude' => ['api/*']]);

        $response = $this->get('/api/test');

        $response->assertStatus(200);
        $response->assertDontSee('Access denied');
    }

    public function test_backdoor_authentication_with_valid_token()
    {
        config(['firewall.backdoor_url' => '/backdoor']);

        $response = $this->get('/backdoor?backdoor_token=test-token');

        $response->assertStatus(302);
        $response->assertRedirect('/');
    }

    public function test_backdoor_authentication_with_invalid_token()
    {
        config(['firewall.backdoor_url' => '/backdoor']);

        $response = $this->get('/backdoor?backdoor_token=wrong-token');

        $response->assertStatus(200);
        $response->assertSee('Invalid token');
    }

    public function test_access_denied_on_non_backdoor_url()
    {
        config(['firewall.backdoor_url' => '/backdoor']);

        $response = $this->get('/some-other-page');

        $response->assertStatus(200);
        $response->assertSee('Access denied');
    }
}
