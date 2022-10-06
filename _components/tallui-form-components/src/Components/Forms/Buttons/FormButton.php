<?php

declare(strict_types=1);

namespace BladeUIKit\Components\Buttons;

use Livewire\Component;
use Illuminate\Contracts\View\View;

class FormButton extends Component
{
    /** @var string|null */
    public $action;

    /** @var string */
    public $method;

    public function __construct(string $action = null, string $method = 'POST')
    {
        $this->action = $action;
        $this->method = strtoupper($method);
    }

    public function render(): View
    {
        return view('buttons.form-button');
    }
}
