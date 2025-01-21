<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Textarea;

class JsonField extends Textarea
{
    protected string $view = 'forms.components.json-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule('json');
    }
}
