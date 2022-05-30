<?php

declare(strict_types=1);

namespace TallUiCore\Components\Forms\Inputs;

use TallUiCore\Components\BladeComponent;
use Illuminate\Contracts\View\View;

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
        return view('tallui-core::components.forms.inputs.textarea');
    }
}
