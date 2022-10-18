<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Inputs;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class Textarea extends BladeComponent
{
    public string $name;

    public string $id;

    public int $rows;

    public function __construct(string $name, string $id = null, int $rows = 3)
    {
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->rows = $rows;
    }

    public function render(): View
    {
        return view('tallui-form-components::components.inputs.textarea');
    }
}
