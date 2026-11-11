<?php

namespace Moox\Mcp\Resources\Packages;

use Laravel\Mcp\Resource;

class PackagesResource extends Resource
{
    public function render(): View
    {
        return view('packages.index');
    }
}
