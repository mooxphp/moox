<?php

Route::get('api/core', \Moox\Core\Http\Controllers\Api\CoreController::class.'@index');
Route::get('api/models', \Moox\Core\Http\Controllers\Api\ModelController::class.'@index');
