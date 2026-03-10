<?php

declare(strict_types=1);

namespace Moox\FileIcons\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileIconController extends Controller
{
    public function __invoke(string $icon): BinaryFileResponse
    {
        $iconPath = __DIR__.'/../../../resources/svg/'.$icon;

        if (! file_exists($iconPath)) {
            abort(404);
        }

        return response()->file($iconPath, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000', // 1 Jahr Cache
        ]);
    }
}
