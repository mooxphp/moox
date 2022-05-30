<?php

declare(strict_types=1);

namespace Usetall\TalluiCore\Components\Blade;

use Illuminate\Contracts\View\View;
use Usetall\TalluiCore\Components\BladeComponent;

class FirstBladeComponent extends BladeComponent
{
    /** @var array */
    protected static $assets = ['example'];

    /** @var string|null */
    public string $first_var = "";

    public function mount()
    {
        // mount
    }

    public function render(): View
    {
        return view('tallui-core::components.blade.first-blade-component');
    }
}
