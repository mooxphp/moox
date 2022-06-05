<?php

declare(strict_types=1);

namespace VendorName\Skeleton\Components\Blade;

use Illuminate\Contracts\View\View;
use VendorName\Skeleton\Components\BladeComponent;

class FirstBladeComponent extends BladeComponent
{
    /** @var array */
    protected static $assets = ['example'];

    /** @var string|null */
    public string $first_var = "";

    public function mount(): void
    {
        // mount
    }

    public function render(): View
    {
        return view(':builder::components.blade.first-blade-component');
    }

}
