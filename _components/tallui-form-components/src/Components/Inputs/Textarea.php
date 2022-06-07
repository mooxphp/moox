<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Inputs;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class Textarea extends BladeComponent
{
    /** @var string */
    public $name;

    /** @var string */
    public $id;

    /** @var int */
    public $rows;

    public function __construct(string $name, string $id = null, $rows = 3)
    {
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->rows = $rows;
    }

    public function render(): View
    {
        return view('tallui-form-components::components.forms.inputs.textarea');
    }
}
