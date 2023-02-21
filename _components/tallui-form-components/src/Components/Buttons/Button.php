<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Buttons;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class Button extends BladeComponent
{
    public string $name;

    public string $id;

    public string $type;

    public string $value;

    public function __construct(string $name = "button", string $id = null, string $type = null, string $value = null)
    {
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->type = $type ?? 'button';
        $this->value = old($name, $value ?? '');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('tallui-form-components::components.buttons.button');
    }
}
