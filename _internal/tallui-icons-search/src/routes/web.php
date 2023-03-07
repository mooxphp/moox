<?php

use Illuminate\Support\Facades\Route;
use Usetall\TalluiIconsSearch\Models\Icon;

Route::view('/icons/{icon}', function(Icon $icon){
    return view('tallui-icons-search::components.blade.icons-show', [
        'icon' => $icon,
    ]);
})->name('icons');


