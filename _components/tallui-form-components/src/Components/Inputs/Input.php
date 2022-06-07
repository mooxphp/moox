<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents\Components\Inputs;

use Illuminate\Contracts\View\View;
use Usetall\TalluiFormComponents\Components\BladeComponent;

class Input extends BladeComponent
{
    /** @var string */
    public $name;

    /** @var string */
    public $id;

    /** @var string */
    public $type;

    /** @var string */
    public $value;

    public function __construct(string $name, string $id = null, string $type = 'text', ?string $value = '')
    {
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->type = $type;
        $this->value = old($name, $value ?? '');
    }

    public function render(): View
    {
        return view('tallui-form-components::components.inputs.input');
    }
}
