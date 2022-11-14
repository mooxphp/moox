<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TestPHP extends Component
{
    /** @var array<mixed> */
    public $array = [];

    /** @var array<mixed> */
    public $array2 = ['testing'];

    /** @var array<mixed> */
    public $array3 = [];

    /** @var array<mixed> */
    public $array4 = ['some', 'testing'];

    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.test-p-h-p');
    }
}
