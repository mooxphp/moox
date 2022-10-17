<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Forms\Buttons;

use Usetall\TalluiFormComponents\Components\BladeComponent;

class Button extends BladeComponent
{
    /** @var string */
    public $name;

    /** @var string */
    public $id;

    /** @var string */
    public $type;

    /** @var string */
    public $value;

    public function __construct(string $name, string $id = null, string $type = null, string $value = null)
    {
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->type = $type ?? 'button';
        $this->value = old($name, $value ?? '');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|view-string
     */
    public function render()
    {
        return view('tallui-form-components::components.forms.buttons.button');
    }
}
