<?php

namespace Moox\Core\Http\Controllers\Api;

use Illuminate\Routing\Controller;

class CoreController extends Controller
{
    public function index()
    {
        $packages = config('core');

        return response()->json($packages);
    }
}
