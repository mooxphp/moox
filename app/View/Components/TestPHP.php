<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TestPHP extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */

     public $array = [];
     public $array2 = ['testing'];
     public $array3 = array();
     public $array4 = array('some','testing');
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
