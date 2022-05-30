<?php

declare(strict_types=1);

namespace Usetall\TalluiWebComponents\Components\Livewire;

class FirstLivewireComponent extends Component
{
    /** @var array */
    protected static $assets = ['example'];

    /** @var string|null */
    public string $first_var = "";

    public function mount()
    {
        // mount
    }

    public function render()
    {
        return view('livewire.first-livewire-component');
    }
}
