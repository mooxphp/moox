<?php

namespace Moox\Core\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

trait RequestInModel
{
    public function getRequestData($key)
    {
        $components = Request::input('components', []);

        if (empty($components)) {
            Log::warning('Request components are missing or empty.');

            return null;
        }

        $firstComponent = $components[0] ?? [];

        if (! isset($firstComponent['updates']["data.$key"])) {
            Log::warning("Request key '$key' not found in updates.");
        }

        return $firstComponent['updates']["data.$key"] ?? null;
    }
}
