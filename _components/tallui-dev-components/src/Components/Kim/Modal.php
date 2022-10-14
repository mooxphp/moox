<?php

declare(strict_types=1);

namespace Usetall\TalluiDevComponents\Components\Kim;

use Usetall\TalluiAppComponents\Components\BladeComponent;

class Modal extends BladeComponent
{
    /** @var array */
    protected static $assets = ['example'];

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function render()
    {
        return view('tallui-dev-components::components.kim.modal');
    }

    /**
     * Turn Modal Visible.
     *
     * @return void
     */
    public function turnModalVisible(): void
    {
        echo 'Hello';
    }
}
