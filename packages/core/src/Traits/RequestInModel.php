<?php

namespace Moox\Core\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

trait RequestInModel
{
    /**
     * Get the request data.
     */
    public function getRequestData(string $key)
    {
        $components = Request::input('components', []);

        if (empty($components)) {
            Log::error('RequestInModel failed: Request components are missing or empty.');

            return null;
        }

        $firstComponent = $components[0] ?? [];

        return $firstComponent['updates']['data.'.$key] ?? null;
    }
}
