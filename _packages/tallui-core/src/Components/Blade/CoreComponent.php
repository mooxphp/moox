<?php

declare(strict_types=1);

namespace Usetall\TalluiCore\Components\Blade;

use Illuminate\Contracts\View\View;
use Usetall\TalluiCore\Components\BladeComponent;

class CoreComponent extends BladeComponent
{
    /** @var string|null */
    public string $first_var = "";

    public function mount(): void
    {
        // mount
    }

    public function render(): View
    {
        return view('tallui-core::components.blade.core-component');
    }
}
