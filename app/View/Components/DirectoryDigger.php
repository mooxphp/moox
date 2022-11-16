<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DirectoryDigger extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->getPath();
    }

    public function getPath(){
        $path = (string) base_path()."\_components";

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.directory-digger');
    }
}
