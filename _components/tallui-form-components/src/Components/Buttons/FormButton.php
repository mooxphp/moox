<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Buttons;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class FormButton extends BladeComponent
{
    public ?string $action;

    public string $method;

    public function __construct(string $action = null, string $method = 'POST')
    {
        $this->action = $action;
        $this->method = strtoupper($method);
    }

    public function render(): View
    {
        return view('tallui-form-components::components.buttons.form-button');
    }
}
