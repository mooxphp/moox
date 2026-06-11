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
    | The package ships demo commands for CLI (see README). They are not
    | enabled for the web UI by default — add signatures here when needed:
    |
    | 'allowed_commands' => [
    |     'prompts:project-setup',
    |     'prompts:test-failed',
    | ],
    |
    */

    'allowed_commands' => [
        'prompts:project-setup',
        'prompts:test-failed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The navigation group where the Command Runner and Command Executions
    | will appear in the Filament navigation. Common options:
    | - 'System' (default)
    | - 'Jobs'
    | - 'Tools'
    | - null (no group)
    |
    */

    'navigation_group' => 'System',

];
