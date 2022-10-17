<?php

namespace App\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;

class Label extends Component
{
    /** @var string */
    public $for;

    public function __construct(string $for)
    {
        $this->for = $for;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() : View
    {
        return view('components.label');
    }

    public function fallback(): string
    {
        return Str::ucfirst(str_replace('_', ' ', $this->for));
    }
}
