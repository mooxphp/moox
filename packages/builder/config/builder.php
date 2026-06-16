<?php

declare(strict_types=1);

return [
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
