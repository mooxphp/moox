<?php

use Illuminate\Support\Facades\Route;
use Usetall\TalluiIconsSearch\Controllers\ShowIconController;

Route::get('/icons/{icon}', ShowIconController::class)->name('icons');
