<?php

declare(strict_types=1);

namespace Usetall\TalluiCore\Components\Livewire;

use Illuminate\Contracts\View\View;
use Usetall\TalluiCore\Components\LivewireComponent;

class CoreLivewire extends LivewireComponent
{
    /** @var string|null */
    public string $first_var = '';

    public function mount(): void
    {
        // mount
    }

    public function render(): View
    {
        return view('tallui-core::components.livewire.core-livewire');
    }
}
