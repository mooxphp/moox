<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Forms;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class Alert extends BladeComponent
{
    public function __construct()
    {
        //
    }

    public function render(): View
    {
        return view('tallui-form-components::components.forms.alert');
    }
}
