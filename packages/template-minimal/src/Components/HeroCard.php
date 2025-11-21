<?php

namespace Moox\TemplateMinimal\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HeroCard extends Component
{
    public ?array $item = null;
    /**
     * Create a new component instance.
     */
    public function __construct(array $item = null)
    {
        $this->item = $item;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('template-minimal::components.hero-card');
    }
}