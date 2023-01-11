<?php

declare(strict_types=1);

namespace Usetall\Skeleton\Components\Livewire;

use Illuminate\Contracts\View\View;
use Usetall\Skeleton\Components\LivewireComponent;

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
        return view(':builder::components.livewire.first-livewire-component');
    }
}
