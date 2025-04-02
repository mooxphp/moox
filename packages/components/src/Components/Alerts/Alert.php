<?php

namespace Moox\Components\Components\Alerts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $message = null,
        public ?string $content = null,
        public ?string $buttons = null
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components::components.alerts.alert');
    }
}
