<?php

Route::get('moox/core/assets/{filename}', function (string $filename) {
    $path = base_path('vendor/moox/core/public/'.$filename);

    if (file_exists($path)) {
        return response()->file($path);
    }

    abort(404);
});
