<?php

namespace Moox\Mcp;

use Illuminate\Support\ServiceProvider;

class McpServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Mcp::class);
    }
}
