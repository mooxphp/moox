<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Support;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class SchemaHost extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public function render(): View
    {
        return view('definition::schema-host');
    }
}
