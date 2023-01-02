<?php

declare(strict_types=1);

namespace Usetall\TalluiAdminPanel\Http\Controllers;

use Illuminate\Contracts\View\View;

class AdminPanelController
{
    public function index(): View
    {
        return view('tallui-admin-panel::adminPanel');
    }
}
