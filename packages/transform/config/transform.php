<?php

declare(strict_types=1);
use Moox\Transform\Support\Operations\MapInlineValueOperation;

return [
    'enable-panel' => false,

    'navigation_sort' => 200,

    /*
    |--------------------------------------------------------------------------
    | Transform default behavior
    |--------------------------------------------------------------------------
    */
    'graceful_degradation' => true,

    /*
    |--------------------------------------------------------------------------
    | Default locale for draft translation handling
    |--------------------------------------------------------------------------
    */
    'default_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Inline value operations for source expressions
    |--------------------------------------------------------------------------
    |
    | Operations are used for expressions like:
    |   source.field|map:1=a,2=b,*=c
    |
    */
    'inline_value_operations' => [
        MapInlineValueOperation::class,
    ],

];
