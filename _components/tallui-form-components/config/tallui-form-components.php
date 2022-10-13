<?php

use Usetall\TalluiFormComponents\Components;

return [

    /*
    |--------------------------------------------------------------------------
    | Components
    |--------------------------------------------------------------------------
    |
    | Below you reference all components that should be loaded for your app.
    | By default all components from tallui-form-components are loaded in. You can
    | disable or overwrite any component class or alias that you want.
    |
    */

    'components' => [
        'input' => Components\Inputs\Input::class,
        'form' => Components\Forms\Form::class,
        'button' => Components\Forms\Buttons\Button::class,
        'logout' => Components\Forms\Buttons\Logout::class,
        'form-button' => Components\Forms\Buttons\FormButton::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire Components
    |--------------------------------------------------------------------------
    |
    | Below you reference all the Livewire components that should be loaded
    | for your app. By default all components from tallui-form-components are loaded in.
    |
    */

    'livewire' => [
        //'form' => Components\Forms\Form::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Components Prefix
    |--------------------------------------------------------------------------
    |
    | This value will set a prefix for all tallui-form-components components.
    | By default it's empty. This is useful if you want to avoid
    | collision with or otherwise overwrite core components.
    |
    | If set with "tui", for example, you can reference components like:
    |
    | <x-tui-alert />
    |
    */

    'prefix' => '',

    /*
    |--------------------------------------------------------------------------
    | Asset Libraries
    |--------------------------------------------------------------------------
    |
    | Components can require these asset files through their static `$assets`
    | property.
    |
    */

    'assets' => [

        'example' => [
            'https://unpkg.com/example/dist/example.min.css',
            'https://unpkg.com/example/dist/example.min.js',
        ],

    ],

];
