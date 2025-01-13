<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Textarea;

class JsonField extends Textarea
{
    protected string $view = 'forms.components.json-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrateStateUsing(function ($state) {
            if (is_string($state))
                return json_decode($state, true);
        });
    }
}
