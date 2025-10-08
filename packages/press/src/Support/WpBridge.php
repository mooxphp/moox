<?php

namespace Moox\Press\Support;

use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;

class WpBridge
{
    public function run(string $path = '/wp/')
    {
        $wpIndex = base_path('public/wp/index.php');

        if (!file_exists($wpIndex)) {
            return new Response('WordPress not installed.', 500);
        }

        $env = [
            'REQUEST_URI' => $path,
            'SCRIPT_NAME' => '/wp/index.php',
            'PHP_SELF'    => '/wp/index.php',
            'DOCUMENT_ROOT' => base_path('public'),
            'SERVER_NAME' => request()->server('SERVER_NAME', 'localhost'),
            'SERVER_PORT' => request()->server('SERVER_PORT', '80'),
            'HTTPS' => request()->secure() ? 'on' : 'off',
        ];

        $php = trim(shell_exec('which php')) ?: PHP_BINARY;

        $process = new Process([
            $php,
            $wpIndex,
        ], base_path('public'), $env);

        $process->run();

        return new Response(
            $process->getOutput(),
            $process->isSuccessful() ? 200 : 500,
            ['Content-Type' => 'text/html']
        );
    }
}
