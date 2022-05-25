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

        dd("Puke!");


        return view('/var/www/html/_packages/tallui-core/resources/views/components/livewire/first-livewire-component.blade.php');
    }
}
