<?php

namespace Moox\Components\Components\Buttons;

use Illuminate\View\Component;

class Button extends Component
{
    /**
     * The button type.
     */
    public string $type;

    /**
     * The button icon.
     */
    public ?string $icon;

    /**
     * The button size.
     */
    public string $size;

    /**
     * Whether the button is disabled.
     */
    public bool $disabled;

    /**
     * Whether the button is in a loading state.
     */
    public bool $loading;

    /**
     * The button style.
     */
    public string $style;

    /**
     * The button variant.
     */
    public string $variant;

    /**
     * Create a new button component.
     */
    public function __construct(
        string $type = 'button',
        ?string $icon = null,
        string $size = 'md',
        bool $disabled = false,
        bool $loading = false,
        string $variant = 'primary',
        string $style = 'filled',
    ) {
        $this->type = $type;
        $this->icon = $icon;
        $this->size = $size;
        $this->disabled = $disabled;
        $this->loading = $loading;
        $this->variant = $variant;
        $this->style = $style;
    }

    /**
     * Render the button component.
     */
    public function render()
    {
        return view('components::components.buttons.button', [
            'type' => $this->type,
            'icon' => $this->icon,
            'size' => $this->size,
            'disabled' => $this->disabled,
            'loading' => $this->loading,
            'variant' => $this->variant,
            'style' => $this->style,
        ]);
    }
}
