<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;

class Input extends Component
{
    /** @var string */
    public $name;

    /** @var string */
    public $id;

    /** @var string */
    public $type;

    /** @var string */
    public $value;

    public function __construct(string $name, string $id = null, string $type = null, string $value)
    {
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->type = $type ?? 'text';
        $this->value = old($name, $value ?? '');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.input');
    }
}
