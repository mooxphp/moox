<?php

namespace Moox\Attribute\Frontend;

use Moox\Frontend\Frontend;

class AttributeFrontend extends Frontend
{
    public function getTemplate(): string
    {
        return 'moox::page.default'; // Blade template to render
    }

    public function getContentWidth(): string
    {
        return config('moox.theme.content_width', 'max-w-full'); // Default content width
    }
}
