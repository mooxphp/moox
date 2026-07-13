<?php

declare(strict_types=1);

namespace Moox\Page\Support;

use Illuminate\Support\Facades\View;
use Moox\Page\Models\Page;

class PageLayoutResolver
{
    public function resolveLayout(Page $page): string
    {
        $layout = $page->layout;

        if (! is_string($layout) || $layout === '') {
            return (string) config('page.default_layout', 'default');
        }

        return $layout;
    }

    public function resolveView(Page $page): string
    {
        $layout = $this->resolveLayout($page);
        $layouts = config('page.layouts', []);
        $view = is_array($layouts[$layout] ?? null)
            ? ($layouts[$layout]['view'] ?? null)
            : ($layouts[$layout] ?? null);

        if (! is_string($view) || $view === '') {
            $defaultLayouts = config('page.layouts.default', []);
            $view = is_array($defaultLayouts)
                ? ($defaultLayouts['view'] ?? 'default.page')
                : 'default.page';
        }

        if (! is_string($view) || $view === '' || ! View::exists($view)) {
            return 'default.page';
        }

        return $view;
    }
}
