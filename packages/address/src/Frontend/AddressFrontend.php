<?php

declare(strict_types=1);

namespace Moox\Address\Frontend;

class AddressFrontend
{
    public function getTemplate(): string
    {
        return 'moox::page.default';
    }

    public function getContentWidth(): string
    {
        return config('moox.theme.content_width', 'max-w-full');
    }
}
