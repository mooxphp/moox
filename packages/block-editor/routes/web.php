<?php

use Illuminate\Support\Facades\Route;
use Moox\BlockEditor\Http\Controllers\BlockEditorController;

Route::prefix('moox')->group(function () {
    Route::get('/block-editor', [BlockEditorController::class, 'web'])->name('block-editor.web');
    Route::get('/mail-editor', [BlockEditorController::class, 'mail'])->name('block-editor.mail');
});

Route::prefix('moox/block-editor')->group(function () {
    Route::get('images/{path}', function (string $path) {
        $file = base_path('packages/block-editor/public/images/'.$path);
        if (file_exists($file)) {
            return response()->file($file, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }
        abort(404);
    })->where('path', '.*');

    Route::get('assets/{path}', function (string $path) {
        $file = base_path('packages/block-editor/public/assets/'.$path);

        if (file_exists($file)) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $knownTypes = [
                'js' => 'text/javascript',
                'mjs' => 'text/javascript',
                'css' => 'text/css',
                'map' => 'application/json',
                'json' => 'application/json',
                'wasm' => 'application/wasm',
            ];
            $detected = mime_content_type($file) ?: 'application/octet-stream';
            $mimeType = $knownTypes[$extension] ?? $detected;

            return response()->file($file, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        abort(404);
    })->where('path', '.*');
});

Route::get('/favicon.ico', function () {
    $file = base_path('packages/block-editor/public/assets/favicon.ico');
    if (file_exists($file)) {
        return response()->file($file, [
            'Content-Type' => 'image/x-icon',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
    abort(404);
});
