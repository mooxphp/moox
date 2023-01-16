<?php

declare(strict_types=1);

namespace Usetall\TalluiPackageBuilder\Components\Livewire;

use Illuminate\Contracts\View\View;
use Usetall\TalluiPackageBuilder\Components\LivewireComponent;

class TalluiPackageBuilderLivewireComponent extends LivewireComponent
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
        return view('tallui-package-builder::components.livewire.tallui-package-builder-livewire-component');
    }
}
