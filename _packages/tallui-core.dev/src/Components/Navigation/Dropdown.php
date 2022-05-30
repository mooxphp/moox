<?php

declare(strict_types=1);

namespace TallUiCore\Components\Navigation;

use TallUiCore\Components\BladeComponent;
use Illuminate\Contracts\View\View;

class Dropdown extends BladeComponent
{
    protected static $assets = ['alpine'];

    public function render(): View
    {
        return view('tallui-core::components.navigation.dropdown');
    }
}
