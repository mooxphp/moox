<?php

namespace Moox\Draft\Frontend;

use Moox\Frontend\Frontend;

class DraftFrontend extends Frontend
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
