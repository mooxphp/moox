<?php

declare(strict_types=1);

namespace TallUiCore\Components\Forms;

use TallUiCore\Components\BladeComponent;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

class Error extends BladeComponent
{
    /** @var string */
    public $field;

    /** @var string */
    public $bag;

    public function __construct(string $field, string $bag = 'default')
    {
        $this->field = $field;
        $this->bag = $bag;
    }

    public function render(): View
    {
        return view('tallui-core::components.forms.error');
    }

    public function messages(ViewErrorBag $errors): array
    {
        $bag = $errors->getBag($this->bag);

        return $bag->has($this->field) ? $bag->get($this->field) : [];
    }
}
