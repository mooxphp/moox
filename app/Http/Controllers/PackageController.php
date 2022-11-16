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
        $content = Storage::directories('');

        return view('packageOverview', ['packageName' => $packageName]);
    }
}
