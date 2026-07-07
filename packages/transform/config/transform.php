<?php

declare(strict_types=1);

use Moox\Transform\Support\Operations\AnyTruthyInlineValueOperation;
use Moox\Transform\Support\Operations\CaseInlineValueOperation;
use Moox\Transform\Support\Operations\CoalesceInlineValueOperation;
use Moox\Transform\Support\Operations\IntegerInlineValueOperation;
use Moox\Transform\Support\Operations\LookupModelIdInlineValueOperation;
use Moox\Transform\Support\Operations\MapInlineValueOperation;
use Moox\Transform\Support\Operations\NotTruthyInlineValueOperation;
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
    | Import record payload reader (application binding)
    |--------------------------------------------------------------------------
    |
    | Class implementing Moox\Transform\Contracts\ImportRecordPayloadReader.
    | Configure this in the application when using api_import_record sources.
    |
    */
    'import_record_payload_reader' => null,

    /*
    |--------------------------------------------------------------------------
    | Import record model for Filament run forms (application binding)
    |--------------------------------------------------------------------------
    */
    'import_record_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Import record select (Filament run form)
    |--------------------------------------------------------------------------
    |
    | endpoint_relation: Eloquent relation name from the import record model to
    | the API endpoint (e.g. apiEndpoint). Auto-detected when null.
    | select_limit: maximum options loaded per search / initial open.
    |
    */
    'import_record_endpoint_relation' => null,
    'import_record_select_limit' => 100,

    /*
    |--------------------------------------------------------------------------
    | Import record run context
    |--------------------------------------------------------------------------
    */
    'import_record_context_key' => 'import_record_id',
    'default_import_record_id_template' => '{{context.import_record_id}}',

    /*
    |--------------------------------------------------------------------------
    | Additional model scan paths for Filament destination model select
    |--------------------------------------------------------------------------
    |
    | Each path should point to a directory containing Eloquent model classes.
    | The application may add package model directories here.
    |
    */
    'additional_model_scan_paths' => [],

    /*
    |--------------------------------------------------------------------------
    | Default source projection for ad-hoc transform runs
    |--------------------------------------------------------------------------
    */
    'default_source_projection' => [],

    /*
    |--------------------------------------------------------------------------
    | Locale variant resolver (application binding)
    |--------------------------------------------------------------------------
    |
    | Class implementing Moox\Transform\Contracts\LocaleVariantResolver.
    |
    */
    'locale_variant_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Bulk transform defaults
    |--------------------------------------------------------------------------
    */
    'bulk' => [
        'chunk_size' => 100,
        'persist_children' => true,
        'write_strategy' => 'row',
        'max_failure_samples' => 50,
        'source' => [
            'strategy' => 'eager',
            'chunk_size' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inline value operations for source expressions
    |--------------------------------------------------------------------------
    |
    | Operations are used for expressions like:
    |   source.field|map:1=a,2=b,*=c|upper
    |   coalesce:source.a,source.b
    |   any_truthy:source.deleted,source.inactive|not_truthy
    |   lookup_id:App\\Models\\Post,external_id,source.post_id
    |
    */
    'inline_value_operations' => [
        MapInlineValueOperation::class,
        CaseInlineValueOperation::class,
        TruthyInlineValueOperation::class,
        NotTruthyInlineValueOperation::class,
        IntegerInlineValueOperation::class,
        CoalesceInlineValueOperation::class,
        AnyTruthyInlineValueOperation::class,
        LookupModelIdInlineValueOperation::class,
    ],

];
