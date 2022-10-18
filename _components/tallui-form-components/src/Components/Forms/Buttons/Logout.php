<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Forms\Buttons;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class Logout extends BladeComponent
{
    public string $action;

    public function __construct(string $action = null)
    {
        $this->action = $action ?? route('logout');
    }

    public function render(): View
    {
        return view('tallui-form-components::components.forms.buttons.logout');
    }
}
