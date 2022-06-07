<?php

declare(strict_types=1);

namespace Usetall\TalluiAppComponents\Components\Blade;

use Illuminate\Contracts\View\View;
use Usetall\TalluiAppComponents\Components\BladeComponent;

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
        return view('tallui-app-components::components.blade.first-blade-component');
    }
}
