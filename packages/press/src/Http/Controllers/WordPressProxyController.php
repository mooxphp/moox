<?php

namespace Moox\Press\Http\Controllers;

use Illuminate\Routing\Controller;

class WordPressProxyController extends Controller
{
    public function __invoke($any = null)
    {
        $wpIndex = base_path('public/wp/index.php');

        if (!file_exists($wpIndex)) {
            abort(500, 'WordPress not installed');
        }

        // Setup WordPress environment
        $_SERVER['REQUEST_URI'] = '/wp/' . $any;
        $_SERVER['SCRIPT_NAME'] = '/wp/index.php';
        $_SERVER['PHP_SELF'] = '/wp/index.php';

        // Start output buffering
        ob_start();

        require $wpIndex;

        return response(ob_get_clean());
    }
}
