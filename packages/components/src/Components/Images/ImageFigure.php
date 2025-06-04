<?php

namespace Moox\Components\Components\Images;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ImageFigure extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components::components.images.image-figure');
    }
}