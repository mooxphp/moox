<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Forms;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class Form extends BladeComponent
{
    public ?string $action;

    public string $method;

    public bool $hasFiles;

    public function __construct(string $action = null, string $method = 'POST', bool $hasFiles = false)
    {
        $this->action = $action;
        $this->method = strtoupper($method);
        $this->hasFiles = $hasFiles;
    }

    public function render(): View
    {
        return view('tallui-form-components::components.forms.form');
    }
}
