<?php

namespace Moox\Components\Components\Cards;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardTitle extends Component
{
    public string $tag = 'h2';

    /**
     * Create a new component instance.
     */
    public function __construct(string $tag = 'h2')
    {
        $this->tag = $tag;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components::components.cards.card-title');
    }
}
