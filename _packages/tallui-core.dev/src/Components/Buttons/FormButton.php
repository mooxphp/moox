<?php

declare(strict_types=1);

namespace TallUiCore\Components\Buttons;

use TallUiCore\Components\BladeComponent;
use Illuminate\Contracts\View\View;

class FormButton extends BladeComponent
{
    /** @var string */
    public $action;

    /** @var string */
    public $method;

    public function __construct(string $action, string $method = 'POST')
    {
        $this->action = $action;
        $this->method = strtoupper($method);
    }

    public function render(): View
    {
        return view('tallui-core::components.buttons.form-button');
    }
}
