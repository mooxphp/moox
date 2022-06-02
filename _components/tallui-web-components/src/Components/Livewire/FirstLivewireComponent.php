<?php

declare(strict_types=1);

namespace Usetall\TalluiWebComponents\Components\Livewire;

use Illuminate\Contracts\View\View;

class FirstLivewireComponent extends Component
{
    /** @var array */
    protected static $assets = ['example'];

    /** @var string|null */
    public string $first_var = "";

    public function mount(): void
    {
        // mount
    }

    public function render(): View
    {
        return view('livewire.first-livewire-component');
    }
}
