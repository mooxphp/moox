<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->session()->has('locale')) {
            app()->setLocale($request->session()->get('locale'));
            Carbon::setLocale($request->session()->get('locale'));
        } else {
            app()->setLocale(session('locale', 'en'));
            Carbon::setLocale(session('locale', 'en'));
        }

        return $next($request);
    }
}
