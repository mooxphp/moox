<?php

namespace Moox\Components\Components\Tooltips;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Tooltip extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $message = null
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components::components.tooltips.tooltip');
    }
}
