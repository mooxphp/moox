<?php

namespace Moox\Components\Components\Icons;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Icon extends Component
{
    /**
     * The name of the icon.
     */
    public string $name;

    /**
     * The prefix of the icon.
     */
    public string $prefix;

    /**
     * The color of the icon.
     */
    public string $color;

    /**
     * The class of the icon.
     */
    public string $class;

    /**
     * The size of the icon.
     */
    public string $size;

    /**
     * Create a new icon component.
     */
    public function __construct(
        string $name,
        string $prefix = 'google_symbols_',
        string $color = 'currentColor',
        string $class = '',
        string $size = '16',
    ) {
        $this->name = $name;
        $this->prefix = $prefix;
        $this->color = $color;
        $this->class = $class;
        $this->size = $size;
    }

    /**
     * Render the icon component.
     */
    public function render(): View
    {
        return view('components::components.icons.icon', [
            'name' => $this->name,
            'prefix' => $this->prefix,
            'color' => $this->color,
            'class' => $this->class,
            'size' => $this->size,
        ]);
    }
}
