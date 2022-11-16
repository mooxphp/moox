<?php

use App\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [PackageController::class, 'welcome']);
Route::get('/packages', [PackageController::class, 'packagesOverview']);
Route::get('/package/{packageName}', [PackageController::class, 'package']);

Route::get('/custom/alf', function () {
    return view('custom.custom_alf');
});
Route::get('/custom/kim', function () {
    return view('custom.custom_kim');
});
Route::get('/custom/reinhold', function () {
    return view('custom.custom_reinhold');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
