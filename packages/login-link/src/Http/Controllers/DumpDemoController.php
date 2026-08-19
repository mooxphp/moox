<?php

declare(strict_types=1);

namespace Moox\LoginLink\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Moox\LoginLink\Handlers\DumpRedemptionHandler;

class DumpDemoController extends Controller
{
    public function __invoke(Request $request): View
    {
        $dump = $request->session()->get(DumpRedemptionHandler::SESSION_KEY, [
            'hint' => 'No dump in session yet. Issue a demo-dump / demo-campaign link and open it.',
        ]);

        return view('login-link::demo.dump', [
            'dump' => $dump,
            'authCheck' => Auth::guard()->check(),
        ]);
    }
}
