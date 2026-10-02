<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Extra local paths TCPDF may read
    |--------------------------------------------------------------------------
    |
    | Fonts and this package root are always trusted. Dest apps append layout
    | assets (logo, signature) here before the first Pdf::render() call.
    |
    */
    'allowed_paths' => [],
];
