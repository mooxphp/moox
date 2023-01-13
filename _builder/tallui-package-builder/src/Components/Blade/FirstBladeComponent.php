<?php

declare(strict_types=1);

namespace Usetall\TalluiPackageBuilder\Components\Blade;

use Illuminate\Contracts\View\View;
use Usetall\TalluiPackageBuilder\Components\BladeComponent;

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
        return view('tallui-package-builder::components.blade.first-blade-component');
    }
}
