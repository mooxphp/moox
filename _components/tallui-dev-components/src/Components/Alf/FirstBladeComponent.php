<?php

declare(strict_types=1);

namespace Usetall\TalluiDevComponents\Components\Alf;

use Illuminate\Contracts\View\View;
use Usetall\TalluiDevComponents\Components\BladeComponent;

class FirstBladeComponent extends BladeComponent
{
    /** @var array */
    protected static $assets = ['example'];

    /** @var string|null */
    public string $first_var = '';

    public function mount(): void
    {
        // mount
    }

    public function render(): View
    {
        return view('tallui-dev-components::components.alf.first-blade-component');
    }
}
