<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch\Controllers;

use Usetall\TalluiIconsSearch\Models\Icon;

final class ShowIconController
{
    public function __invoke(Icon $icon)
    {
        return view('tallui-icons-search::components.blade.icons-show', [
            'icon' => $icon,
            'icons' => Icon::relatedIcons($icon),
        ]);
    }
}
