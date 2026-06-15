<?php

declare(strict_types=1);

use Moox\Builder\Storage\TypedValueDriver;

return [
    'default_driver' => env('BUILDER_DRIVER', 'typed'),
    'drivers' => [
        'typed' => TypedValueDriver::class,
    ],
    'navigation_group' => env('BUILDER_NAVIGATION_GROUP', 'Felder'),

    /*
    |--------------------------------------------------------------------------
    | Builder entities
    |--------------------------------------------------------------------------
    |
    | Register Filament resources that may use custom field groups. The array
    | key is the entity identifier used in location rules and storage.
    |
    | Packages can merge additional entries in their service provider:
    |
    |   config(['builder.entities.product' => [
    |       'resource' => ProductResource::class,
    |       'label' => 'Products',
    |   ]]);
    |
    */
    'entities' => [
        // 'item' => [
        //     'resource' => \Moox\Item\Resources\ItemResource::class,
        //     'label' => 'Items',
        // ],
    ],
];
