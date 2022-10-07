<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Forms\Buttons;

use Usetall\TalluiFormComponents\Components\BladeComponent;
use Illuminate\Contracts\View\View;

class FormButton extends BladeComponent
{
    /** @var string|null */
    public $action;

    /** @var string */
    public $method;

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
