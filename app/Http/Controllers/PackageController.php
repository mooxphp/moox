<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class PackageController extends Controller
{
    public function welcome()
    {
        return view('welcome');
    }

    public function packagesOverview()
    {
        return view('packageOverview');
    }

    public function package($packageName)
    {
        $content = Storage::get('/storage/_components/tallui-dev-components/README.md');
        dd($content);

        return view('packageOverview', ['packageName' => $packageName]);
    }
}
