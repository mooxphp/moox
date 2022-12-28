<?php

declare(strict_types=1);

namespace Usetall\TalluiAdminPanel\Components\Livewire;

use Illuminate\Contracts\View\View;
use Usetall\TalluiAdminPanel\Components\LivewireComponent;

class FirstLivewireComponent extends LivewireComponent
{
    /** @var array<mixed> */
    protected static $assets = ['example'];

    public string $first_var = '';

    public function mount(): void
    {
        // mount
    }

    public function render(): View
    {
        return view('tallui-admin-panel::components.livewire.first-livewire-component');
    }
}
