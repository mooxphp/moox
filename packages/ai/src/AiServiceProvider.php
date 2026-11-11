<?php

namespace Moox\Ai;

use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Ai::class);
    }
}
