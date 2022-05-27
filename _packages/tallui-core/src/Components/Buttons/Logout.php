<?php

declare(strict_types=1);

namespace TallUiCore\Components\Buttons;

use TallUiCore\Components\BladeComponent;
use Illuminate\Contracts\View\View;

class Logout extends BladeComponent
{
    /** @var string */
    public $action;

    public function __construct(string $action = null)
    {
        $this->action = $action ?? route('logout');
    }

    public function render(): View
    {
        return view('tallui-core::components.buttons.logout');
    }
}
