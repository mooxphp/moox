<?php

declare(strict_types=1);

namespace Usetall\TalluiWebComponents\Components\Blade;

use Illuminate\Contracts\View\View;
use Usetall\TalluiWebComponents\Components\BladeComponent;

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
        return view('tallui-web-components::components.blade.first-blade-component');
    }
}
