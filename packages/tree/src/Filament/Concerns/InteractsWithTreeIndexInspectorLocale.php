<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Moox\Tree\Support\TreeLocale;

trait InteractsWithTreeIndexInspectorLocale
{
    protected function syncTreeInspectorLocaleToRequest(): void
    {
        if (! filled($this->lang ?? null)) {
            return;
        }

        request()->query->set('lang', (string) $this->lang);
        TreeLocale::syncToRequest((string) $this->lang);
    }
}
