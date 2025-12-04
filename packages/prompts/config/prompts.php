<?php

/*
|--------------------------------------------------------------------------
| Prompts Configuration
|--------------------------------------------------------------------------
|
| This configuration file defines which Artisan commands are allowed to be
| executed through the web interface using prompts.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed Commands
    |--------------------------------------------------------------------------
    |
    | List of Artisan command names that are allowed to be executed through
    | the web interface. Only commands listed here will be available in the
    | Command Runner page.
    |
    | Example:
    | 'allowed_commands' => [
    |     'prompts:test-flow',
    |     'prompts:test-web',
    | ],
    |
    */

    'allowed_commands' => [
        'prompts:test-flow',
        'prompts:publish-news-config',
        // Add more commands here as needed
    ],

];
