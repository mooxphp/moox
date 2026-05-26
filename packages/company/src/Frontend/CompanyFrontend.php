<?php

declare(strict_types=1);

namespace Moox\Company\Frontend;

use Moox\Frontend\Frontend;

class CompanyFrontend extends Frontend
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
