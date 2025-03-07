<?php

namespace Moox\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session()->get('locale') ??
        request()->get('locale') ??
        request()->cookie('switch_locale') ??
        config('app.locale', 'en') ??
        request()->getPreferredLanguage();

        app()->setLocale($locale);

        Log::info('Set locale to '.app()->getLocale());

        return $next($request);
    }
}
