<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Forms\Buttons;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class FormButton extends BladeComponent
{
    public string|null $action;

    public string $method;

    public function __construct(string $action = null, string $method = 'POST')
    {
        $this->action = $action;
        $this->method = strtoupper($method);
    }

    public function render(): View
    {
        return view('tallui-form-components::components.forms.buttons.form-button');
    }
}
