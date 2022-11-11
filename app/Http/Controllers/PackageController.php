<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PackageController extends Controller
{
    function welcome(){
        return view('welcome');
    }

    function packagesOverview(){


        return view('packageOverview');
    }

    function package($packageName){
        $content = Storage::get('/storage/_components/tallui-dev-components/README.md');
        dd($content);
        return view('packageOverview',['packageName'=>$packageName]);
    }
}
