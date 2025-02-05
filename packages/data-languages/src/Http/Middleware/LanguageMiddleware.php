<?php

namespace Moox\DataLanguages\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $language = session('locale');

        app()->setLocale($language);

        Log::info('Set locale to '. app()->getLocale());
        return $next($request);
    }
}
