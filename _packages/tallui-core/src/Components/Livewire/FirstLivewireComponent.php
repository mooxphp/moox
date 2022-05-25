<?php

namespace Usetall\TalluiCore\Components\Livewire;

use Illuminate\Contracts\View\View;
use Usetall\TalluiCore\Components\LivewireComponent;

class FirstLivewireComponent extends LivewireComponent
{
    public $first_var = "";

    public function mount()
    {
        // mount
    }

    public function render(): View
    {
        return view('livewire.first-livewire-component');
    }
}
