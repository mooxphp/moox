<?php

declare(strict_types=1);

namespace Usetall\TalluiWebComponents\Components\Livewire;

use Illuminate\Contracts\View\View;
use Usetall\TalluiWebComponents\Components\LivewireComponent;

class FirstLivewireComponent extends LivewireComponent
{
    protected static $assets = ['example'];

    public string $first_var = '';

    public function mount(): void
    {
        // mount
    }

    public function render(): View
    {
        return view('tallui-web-components::components.livewire.first-livewire-component');
    }
}
