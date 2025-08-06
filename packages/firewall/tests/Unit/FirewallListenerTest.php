<?php

namespace Moox\Firewall\Tests\Unit;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Moox\Firewall\Listeners\FirewallListener;

class FirewallListenerTest extends TestCase
{
    protected FirewallListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new FirewallListener;

        Config::set('firewall.enabled', true);
        Config::set('firewall.backdoor', true);
        Config::set('firewall.backdoor_token', 'test-token');
    }

    public function test_firewall_disabled_returns_early()
    {
        Config::set('firewall.enabled', false);

        $request = Request::create('/');
        $event = new RouteMatched($request, $request->route());

        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_whitelisted_ip_returns_early()
    {
        Config::set('firewall.whitelist', ['127.0.0.1']);

        $request = Request::create('/');
        $event = new RouteMatched($request, $request->route());

        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_excluded_route_returns_early()
    {
        Config::set('firewall.exclude', ['api/*']);

        $request = Request::create('/api/test');
        $event = new RouteMatched($request, $request->route());

        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_backdoor_disabled_shows_access_denied()
    {
        Config::set('firewall.backdoor', false);

        $request = Request::create('/');
        $event = new RouteMatched($request, $request->route());

        View::shouldReceive('make')
            ->with('firewall::access-denied')
            ->andReturnSelf();
        View::shouldReceive('render')
            ->andReturn('Access denied');

        $this->expectOutputString('Access denied');

        $this->listener->handle($event);
    }

    public function test_backdoor_url_restriction()
    {
        Config::set('firewall.backdoor_url', '/backdoor');

        $request = Request::create('/some-other-page');
        $event = new RouteMatched($request, $request->route());

        View::shouldReceive('make')
            ->with('firewall::access-denied')
            ->andReturnSelf();
        View::shouldReceive('render')
            ->andReturn('Access denied');

        $this->expectOutputString('Access denied');

        $this->listener->handle($event);
    }

    public function test_valid_token_authenticates_user()
    {
        Config::set('firewall.backdoor_url', '/backdoor');

        $request = Request::create('/backdoor?backdoor_token=test-token');
        $event = new RouteMatched($request, $request->route());

        $this->listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_invalid_token_shows_error()
    {
        Config::set('firewall.backdoor_url', '/backdoor');

        $request = Request::create('/backdoor?backdoor_token=wrong-token');
        $event = new RouteMatched($request, $request->route());

        View::shouldReceive('make')
            ->with('firewall::backdoor', ['firewall_error' => null])
            ->andReturnSelf();
        View::shouldReceive('render')
            ->andReturn('Backdoor form');

        $this->expectOutputString('Backdoor form');

        $this->listener->handle($event);
    }
}
