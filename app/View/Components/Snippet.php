<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Snippet extends Component
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function render(): View
    {
        return view('components.snippet');
    }

    public function snippet(): string
    {
        return file_get_contents(resource_path("snippets/{$this->name}.md"));
    }
}
