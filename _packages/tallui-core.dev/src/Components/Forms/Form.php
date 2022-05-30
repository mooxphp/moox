<?php

declare(strict_types=1);

namespace TallUiCore\Components\Forms;

use TallUiCore\Components\BladeComponent;
use Illuminate\Contracts\View\View;

class Form extends BladeComponent
{
    /** @var string */
    public $action;

    /** @var string */
    public $method;

    /** @var bool */
    public $hasFiles;

    public function __construct(string $action, string $method = 'POST', bool $hasFiles = false)
    {
        $this->action = $action;
        $this->method = strtoupper($method);
        $this->hasFiles = $hasFiles;
    }

    public function render(): View
    {
        return view('tallui-core::components.forms.form');
    }
}
