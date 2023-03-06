<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch\Components\Blade;

use Illuminate\Contracts\View\View;
use Usetall\TalluiIconsSearch\Components\BladeComponent;

class IconsShowBladeComponent extends BladeComponent
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
        return view('tallui-icons-show::components.blade.icons-show-blade-component');
    }
}
