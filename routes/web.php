<?php

use App\Http\Controllers\ShowIconController;
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

Route::get('/', function () { return view('welcome'); })->name('home');
Route::view('/blade-icons', 'blade-icons.index')->name('blade-icons');
Route::get('/blade-icons/{icon}', ShowIconController::class)->name('blade-icon');


Route::get('/collection', [ShowIconController::class, 'collection']);


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
