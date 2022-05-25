<?php

namespace Usetall\TalluiCore\Components\Livewire;

use Illuminate\Contracts\View\View;
use Usetall\TalluiCore\Components\LivewireComponent;

class FirstLivewireComponent extends LivewireComponent
{

    protected static $assets = ['example'];

    public function render(): View
    {
        return view('tallui-core::components.livewire.first-livewire-component');
    }
}
