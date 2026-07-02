<?php

declare(strict_types=1);

use Moox\Transform\Support\Operations\AnyTruthyInlineValueOperation;
use Moox\Transform\Support\Operations\CaseInlineValueOperation;
use Moox\Transform\Support\Operations\CoalesceInlineValueOperation;
use Moox\Transform\Support\Operations\IntegerInlineValueOperation;
use Moox\Transform\Support\Operations\MapInlineValueOperation;
use Moox\Transform\Support\Operations\NotTruthyInlineValueOperation;
use Moox\Transform\Support\Operations\StatusFromDeletedInlineValueOperation;
use Moox\Transform\Support\Operations\TruthyInlineValueOperation;

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
    | Queued transform job
    |--------------------------------------------------------------------------
    |
    | timeout: 0 = no job timeout (requires a Horizon supervisor with timeout 0).
    |
    */
    'job_queue' => 'transform',
    'job_timeout' => 0,

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
    |   source.field|map:1=a,2=b,*=c|upper
    |   coalesce:source.a,source.b
    |   any_truthy:source.deleted,source.inactive|not_truthy
    |
    */
    'inline_value_operations' => [
        MapInlineValueOperation::class,
        CaseInlineValueOperation::class,
        TruthyInlineValueOperation::class,
        NotTruthyInlineValueOperation::class,
        IntegerInlineValueOperation::class,
        StatusFromDeletedInlineValueOperation::class,
        CoalesceInlineValueOperation::class,
        AnyTruthyInlineValueOperation::class,
    ],

];
