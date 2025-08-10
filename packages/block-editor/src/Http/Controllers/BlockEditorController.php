<?php

namespace Moox\BlockEditor\Http\Controllers;

use Illuminate\Routing\Controller;

class BlockEditorController extends Controller
{
    public function web()
    {
        return view('block-editor::editor', [
            'mode' => 'web',
            'initialContent' => [],
        ]);
    }

    public function mail()
    {
        return view('block-editor::editor', [
            'mode' => 'mail',
            'initialContent' => [],
        ]);
    }
}
