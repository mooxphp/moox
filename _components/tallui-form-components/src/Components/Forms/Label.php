<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Forms;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class Label extends BladeComponent
{
    public string $for;

    public function __construct(string $for = "input")
    {
        $this->for = $for;
    }

    public function render(): View
    {
        return view('tallui-form-components::components.forms.label');
    }

    public function fallback(): string
    {
        return Str::ucfirst(str_replace('_', ' ', $this->for));
    }
}
