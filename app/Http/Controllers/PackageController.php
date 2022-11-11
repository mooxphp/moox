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
        $content = Storage::directories('');
        return view('packageOverview',['packageName'=>$packageName]);
    }
}
