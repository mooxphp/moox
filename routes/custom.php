<?php

/*
|   Use the Custom feature to include routes for packages
|   that are not available to all devs, because you load
|   them from /_custom
|
|   Copy this file to e. g. routes/custom_myproject.php.
|   Add 'myproject' to TUI_CUSTOM_PROJECTS in .env
*/

use Illuminate\Support\Facades\Route;

Route::get('/yourproject', function () {
    // ...
});
