<?php

declare(strict_types=1);

namespace Usetall\TalluiCore\Components\Livewire;

use Illuminate\Contracts\View\View;
use Usetall\TalluiCore\Components\LivewireComponent;

class FirstLivewireComponent extends LivewireComponent
{
    /** @var array */
    protected static $assets = ['example'];

    /** @var string|null */
    public string $first_var = "";

    public function mount()
    {
        // mount
    }

    public function render(): View
    {
        return view('tallui-core::components.livewire.first-livewire-component');
    }
}
