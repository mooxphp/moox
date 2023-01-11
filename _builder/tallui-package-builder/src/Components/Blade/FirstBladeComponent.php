<?php

declare(strict_types=1);

namespace Usetall\Skeleton\Components\Blade;

use Illuminate\Contracts\View\View;
use Usetall\Skeleton\Components\BladeComponent;

class FirstBladeComponent extends BladeComponent
{
    /** @var array<mixed> */
    protected static $assets = ['example'];

    public string $first_var = '';

    public function mount(): void
    {
        // mount
    }

    public function render(): View
    {
        return view(':builder::components.blade.first-blade-component');
    }
}
