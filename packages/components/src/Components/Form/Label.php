<?php

namespace Moox\Components\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Label extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $name = 'name'
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components::components.form.label');
    }
}
