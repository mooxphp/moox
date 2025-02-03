<?php

namespace Moox\DataLanguages\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $language = session('locale');

        app()->setLocale($language);

        return $next($request);
    }
}
