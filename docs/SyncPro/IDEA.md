# Idea

from user:

```php
/* Must be provided by Sync, use a static maybe?

Select::make('platforms')

->label('Platforms')

->multiple()

->options(function () {

return Platform::pluck('name', 'id')->toArray();

})

->afterStateHydrated(function ($component, $state, $record) {

if ($record && class_exists(PlatformRelationService::class)) {

$platformService = app(PlatformRelationService::class);

$platforms = $platformService->getPlatformsForModel($record);

$component->state($platforms->pluck('id')->toArray());

}

})

->dehydrated(false)

->reactive()

->afterStateUpdated(function ($state, callable $set, $record) {

if ($record && class_exists(PlatformRelationService::class)) {

$platformService = app(PlatformRelationService::class);

$platformService->syncPlatformsForModel($record, $state ?? []);

}

})

->preload()

->searchable()

->visible(fn () => class_exists(Platform::class))

->columnSpan([

'default' => 12,

'md' => 12,

'lg' => 12,

]),

*/
```

## File sync

File Sync Branch deployed und für schnelleres Deployment Composer aus auf moox und moox-demo!

Jetzt müsste gedingst werden, aber ich habe noch keine Resource dafür

-   Image default
-   Spatie media ... später
-   WpUser, WpPost ... basis sollte laufen dafür

FileResolverInterface includes methods for resolving file fields, getting file fields, and getting file data.

AbstractFileResolver implements the common logic for resolving files and provides an abstract method for getting file fields.

WpUserFileResolver extends the AbstractFileResolver and implements the specific logic for WpUser models, including handling the custom avatar field.
REMINDER: Update Press docs with Transformer, Resolver and Handler

2. Implement FileResolver Interface:

    - Create a `FileResolverInterface` in the Sync package
    - Implement `AbstractFileResolver` class
    - Update `WpUserFileSyncResolver` to extend `AbstractFileResolver`

3. Enhance PrepareSyncJob:

    - Implement file detection logic using the config's `file_sync_fieldsearch`
    - Use the appropriate `FileResolver` based on the model class
    - Add file metadata to sync payload

4. Update SyncWebhookController:

    - Modify to handle file sync information in the incoming webhook
    - Dispatch `FileSyncJob` for each file that needs syncing

5. Refactor FileSyncJob:

    - Implement chunked file transfer logic
    - Add file comparison using metadata (size, hash) to determine if sync is needed
    - Use the config's `file_sync_allowed_extensions` for validation

6. Enhance FileSyncController:

    - Add endpoints for receiving and handling file chunks
    - Implement logic to reassemble files on the target platform
    - Ensure only allowed file extensions are processed

7. Update WebhookAuthMiddleware:
    - Ensure it can handle authentication for both regular sync and file sync requests

-- done til here, also changed SyncResponseWebhook --

-- new answer with missing changes --

-- implement the flow of additional data in transformer, synchandler and finish the wpuser file sync --

8. Implement File Cleanup and Error Handling:

    - Add logic to clean up temporary files after successful transfer or on failure
    - Implement proper error handling and logging for file sync operations

9. Update Existing Models and Transformers:

    - Modify relevant models to work with the new file sync system
    - Adjust transformers to include file information when necessary

10. Testing and Documentation:
    - Create unit and integration tests for the new file sync functionality
    - Update the package documentation to reflect the new file sync capabilities and configuration options

This revised plan takes into account the prepared configuration and existing files, providing a more targeted approach to implementing file synchronization in Moox Sync.

Would you like to start with the first step, finalizing the configuration? Or is there a specific part of this plan you'd like to focus on first?

## File Sync V2

-   HasMedia to support Spatie Media Library, incl. Collections
-   See New model array (chat, scroll top)

## AI Prompt

I want to improve the package Moox Sync (packages/sync), documented in packages/sync/README.md.

I have two platforms for testing Sync, I created them and the platforms are synced automatically every five minutes as expected. I have a couple of sync records

Moox Sync syncs "normal" records like Moox Builder Items as well as "special" records like WpUser (using two model bindings, called Transformer and SyncHandler), all CRUD operations work fine. Sync also deletes user meta on user delete on the target platform. Perfect.

Moox Sync provides Platform Relations. To enable a model, just add it to the config. Platform Relations care for just syncing records that should be synced to a platform. Perfectly running with WpUsers. Not tested with more than two platforms, though.

The open issues on the package are

1. Sync files or first iteration - link files to the source (or master) platform
2. We depend on the ID, means if Sync creates records on target platforms, these must have the identical id, otherwise Sync will not update them when changes should be synced, instead it creates a new (and incomplete) record. We have a configuration for this in sync.php ...
3. The SyncBackupJob is not yet implemented or it lacks behind 100 commits
4. Sessions, Devices and more models could be synced with Users, these would use that platform relation config, too. The main problem will be setting the correct id, as we can not rely on this auto-increment values, we can not ensure consistency across platforms, we need to set this id on each platform after a user is created, we need to use the correct user-id from the target platform, when we create new sessions and devices. Another thing, sessions and devices should be synced, even if there is no update on user, so the listener will have to listen to these model events, too. That is something we probably want to implement later.
5. Platforms need to be synced without security when synced for the first time, that is implemented but not tested yet
6. Config: Disable Platform management on all not-masters. Action Button too.
7. Currently only the current platform can be source platform, right? Then that must be changed in the Resource. So remove that select? Because when we want to allow every platform to be a source platform, we must also implement to sync syncs and to execute these on the source platforms
8. There are a lot of TODO comments and disabled features in the PlatformResource as well as the SyncResource.
9. WpPost and Taxonomies are not implemented yet, only WpUser.
10. Transformer, with an example
11. Use platform relation can be enabled, even if there is no platform relation for this model set, that should be limited in resource
12. Implement "Redirect to master" feature, as synced entities should only be editable on Master.
13. We have no information from the target back to the source that the sync has worked. Implement feedback as Filament notification when we know that the request was done there.

I am sure, not all of this is crystal clear and I want to tackle these tasks step by step. So don't respond with code or solutions now. Give me an overview, of what's clear and what's not, so we can iterate over each Task in the following chats.

-   ACHTUNG /wp ... auf Forge war beides kaputt auf Envoyer funzt das so gar nicht, muttu symlink :-(

WpUser

-   user_url cannot be null
-   Password change not working in User edit, The password is incorrect.

Builder

-   Unique slug abfangen
-   Relations mit cascade

-   https://moox.org - Moox.org Website on Forge / Hetzner
    -   https://moox.org/moox
    -   https://moox.org/press
    -   https://forge.laravel.com/servers/718293/sites/2445802/deploy/http?token=KjrYw5u8X727TZQY0PuNX8yzEXsHsNA1xOe432BX
-   https://demo.moox.org/ - Demo Platform on Forge / Hetzner
    -   https://demo.moox.org/moox
    -   https://demo.moox.org/press
    -   https://forge.laravel.com/servers/718293/sites/2456317/deploy/http?token=zWkEIULRWNRv9SBHduobef3dU095kFHwx2P7ran1
-   https://shared.moox.de/ - Shared Platform on Envoyer / All-Inkl
    -   https://shared.moox.de/moox
    -   https://shared.moox.de/press
    -   https://shared.moox.de/queue/work?_token=secret&timeout=300
    -   https://shared.moox.de/schedule/run?_token=secret
    -   https://envoyer.io/deploy/WOYFdDpWgwoa2p30PvQIqvBD57Tg4ngwXVKQm1rD
    -   /usr/bin/php82 artisan ...
    -   SyncBackupJob läuft hier noch nicht, alter Stand deployed

Secure the APIs ... HMAC ... TOKEN

Sync needs to handle relations
BackupJob not tested yet, probably not working
Sync needs to copy over files (avatars)! Over API, or (re)-link them
Sync needs to redirect to the master platform
For detailed information one one platform we need a callback from the targets
User relations like meta need to be synced, too
Sessions, devices and passkeys etc. could be synced, too
Sync could have a query option, and the with option, that would finally make sense

Transformer (multiple, sortable) - Translate - Notify

Widgets - Platforms Active, Failure, Locked - Syncs Active - Syncs per Minute/Hour/Day/Week - Sync Errors

Audit
Relationsmanager

Todo in Code, Relationmanager
Platform Relation,

User
WP User

Security (Sessions)

```php

'status', // disable the sync if neccessary

'title', // Model from source to model from target, auto-generated (partially if sync_all_fields is false, mapped if field_mappings is set)

'source_platform_id',

'source_model', // Read from ReST API

'target_platform_id',

'target_model', // Write locally using Sync Client or to ReST API

'use_platform_relations', // Use platform relations to sync only records related to the platform

'if_exists', // What to do if the record already exists in the target platform (update, skip, error)

'sync_ids', // IDs of models to sync, optional

'sync_all_fields',

'field_mappings', // only if sync_all_fields is false

'use_transformer_class', // custom class to transform the data

'has_errors', // If there are errors in the last sync

'error_message', // Error message from the last sync

'interval',

'last_sync',

```

Moox Sync is a Filament plugin that allows you to synchronize data between platforms. It is made for Moox plugins, specially Moox User but can also be used for other models.

## New model array

https://chatgpt.com/c/66e0e0cd-0718-800c-9c69-1f37a2afd14e

Idea:

-   Know model compatibility, Relations and Files even before Syncs are set-up
-   Change the SyncResource to use this information, means see if compatible or not (minor)
-   Offer the relations to be edited in Sync ... sync true/false
-   Offer the files to be edited in Sync ... sync true/false
-   Offer the fields to sync different models, if partly
-   Deny to sync models that will not work
-   Use https://filamentphp.com/docs/3.x/forms/fields/repeater

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->json('compatibility_json')->nullable();
            $table->json('relationships_json')->nullable();
            $table->json('files_json')->nullable();
            $table->json('fields_json')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sync_models');
    }
}

```

So I need a command, that does:

-   Read the whole db-table
-   Detect relations by foreign key
-   Detect pivot tables, read data, extract used relations
-   Make an educated guess on required
    -   If the foreign key column is set to `NOT NULL`, required
    -   If a foreign key is set to `ON DELETE CASCADE`, required
    -   All others are not required
-   See, if models table
    -   has all fields not nullable in another model, add them to compatible (what means we can sync from them as source)
    -   has identical fields, set full to true
-   Find files that should be synced
    -   by native fields (a config array has search strings, to detect these fields)
    -   by Spatie Media Library relation
    -   by other relations, we are able to detect
    -   by WordPress meta (our own model, so it is easy)
-   Create this array

```php
    'model_array' => [
        'User' => [
		    'compatibility' => [
			    'Customer', => [
	                'model' => '\App\Models\Customer',
	                'full' => true, // otherwise partly compatible
			    ],
			],
	        'relationships' => [
			    'Posts', => [
			        'model' => '\App\Models\Post',
	                'name' => 'posts',
	                'type' => 'hasMany',
	                'required' => true,
			    ],
            ],
	        'fields' => [
	            'id' => [
		            'type' => 'integer',
					'nullable' => false
				],
	            'name' => [
		            'type' => 'string',
		            'nullable' => false
		        ],
	        ],
	        'files' => [
		        // if a native field provides a file url
		        'Image' => [
					'type' => 'native',
					'name' => 'image_url',
				],
				// if Spatie's Media Library is used
				// I assume it is easy to detect that
				'Media' => [
					'type' => 'media',
				],
				// if there is a collection
				'Thumbs' => [
					'type' => 'media',
					'collection' => 'thumbs',
				],
				// if it is another relation
				// we would be able to detect that in db, I assume
				'Gallery' => [
					'type' => 'relation',
					'name' => 'gallery',
				],
				// if there's a meta relation and a post, like in WP
				// that is easy to detect, as it is our own model
				'Avatar' => [
					'type' => 'press',
					'key' => 'avatar_id', // meta key
				],
	        ],
        ],
    ],
```

-   Save to Eloquent model SyncModel
-   SyncModel can then have custom Entries that overwrite the generated Array
