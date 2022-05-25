<?php

namespace Usetall\TalluiCore\Components\Blade;

use Illuminate\Contracts\View\View;
use Usetall\TalluiCore\Components\BladeComponent;

class FirstBladeComponent extends BladeComponent
{
    public $first_var = "";

    public function mount()
    {
        // mount
    }

    public function render(): View
    {

        dd("Puke in FirstBladeComponent!");

        return view('tallui-core::components.blade.first-blade-component');
    }
}
