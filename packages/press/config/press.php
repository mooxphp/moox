<?php

use Illuminate\Validation\Rules\Password;
use Moox\Press\Models\WpUser;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core. Example:
|
| 'trans//core::core.all',
| loads from common.php
| outputs 'All'
|
*/
return [

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'category' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.category',
            'plural' => 'trans//core::core.categories',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'commentMeta' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::comment.wp_comment_meta',
            'plural' => 'trans//core::comment.wp_comment_metas',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'comment' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::comment.comment',
            'plural' => 'trans//core::comment.comments',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'media' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.media',
            'plural' => 'trans//core::core.media',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'option' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.option',
            'plural' => 'trans//core::core.options',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'page' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::page.page',
            'plural' => 'trans//core::page.pages',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'post_status',
                            'operator' => '!=',
                            'value' => 'trash',
                        ],
                    ],
                ],

                'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'post_status',
                            'operator' => '=',
                            'value' => 'publish',
                        ],
                    ],
                ],
                'drafts' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'post_status',
                            'operator' => '=',
                            'value' => 'draft',
                        ],
                    ],
                ],
            ],
        ],

        'postMeta' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::post.wp_post_meta',
            'plural' => 'trans//core::post.wp_post_metas',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'post' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::post.post',
            'plural' => 'trans//core::post.posts',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'post_status',
                            'operator' => '!=',
                            'value' => 'trash',
                        ],
                    ],
                ],

                'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'post_status',
                            'operator' => '=',
                            'value' => 'publish',
                        ],
                    ],
                ],
                'drafts' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'post_status',
                            'operator' => '=',
                            'value' => 'draft',
                        ],
                    ],
                ],
                'trash' => [
                    'label' => 'trans//core::core.trash',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'post_status',
                            'operator' => '=',
                            'value' => 'trash',
                        ],
                    ],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'rubrik' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.rubrik',
            'plural' => 'trans//core::core.rubriks',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'training' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.training',
            'plural' => 'trans//core::core.trainings',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'tag' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.tag',
            'plural' => 'trans//core::core.tags',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'termMeta' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.wp_term_meta',
            'plural' => 'trans//core::core.wp_term_metas',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'termRelationships' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.wp_term_relationship',
            'plural' => 'trans//core::core.wp_term_relationships',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'term' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.term',
            'plural' => 'trans//core::core.terms',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'termTaxonomy' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.wp_term_taxonomy',
            'plural' => 'trans//core::core.wp_term_taxonomies',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'theme' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.theme',
            'plural' => 'trans//core::core.themes',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'userMeta' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::user.wp_user_meta',
            'plural' => 'trans//core::user.wp_user_metas',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'user' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::user.user',
            'plural' => 'trans//core::user.users',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],

        'wiki' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::core.wiki',
            'plural' => 'trans//core::core.wikis',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | This values are for grouping the navigation items into the
    | right group in the Filament Admin Panel. By default,
    | everything we use three Moox-compatible groups.
    |
    */

    'press_navigation_group' => 'trans//core::post.press',
    'system_navigation_group' => 'trans//core::core.system',
    'meta_navigation_group' => 'trans//core::core.meta',
    'user_navigation_group' => 'trans//core::user.users',

    /*
    |--------------------------------------------------------------------------
    | WordPress Path
    |--------------------------------------------------------------------------
    |
    | Set up the path, prefix, and slug for your WordPress installation.
    | You may have a simple copy of WordPress or use Composer.
    | In either way, you need PHPdotenv to use the .env
    |
    */

    'wordpress_path' => env('WP_PATH', '/public/wp'),

    /*
    |--------------------------------------------------------------------------
    | WordPress Prefix
    |--------------------------------------------------------------------------
    |
    | Set up the table prefix for your WordPress installation.
    | It defaults to wp_ but specially when implementing a
    | WordPress multisite, you may want to change it.
    |
    */

    'wordpress_prefix' => env('WP_PREFIX', 'wp_'),

    /*
    |--------------------------------------------------------------------------
    | WordPress Slug
    |--------------------------------------------------------------------------
    |
    | Set up the URL slug for your WordPress installation.
    | Depending on your setup, your frontend can be
    | redirected to the WordPress installation.
    |
    */

    'wordpress_slug' => env('WP_SLUG', '/wp'),

    /*
    |--------------------------------------------------------------------------
    | WordPress Auth
    |--------------------------------------------------------------------------
    |
    | Set auth_wordpress to true to use Laravel for authentication of
    | your WordPress users in your Laravel application.
    | You need the moox_press WordPress plugin.
    |
    */

    'auth_wordpress' => env('AUTH_WP', false),

    /*
    |--------------------------------------------------------------------------
    | WordPress Lock
    |--------------------------------------------------------------------------
    |
    | Set lock_wordpress to true to bring your complete WordPress
    | behind the Login, that can be either the WordPress login
    | or the Laravel login, depending on auth_wordpress.
    |
    */

    'lock_wordpress' => env('LOCK_WP', false),

    /*
    |--------------------------------------------------------------------------
    | WordPress Redirects
    |--------------------------------------------------------------------------
    |
    | Set up the redirects for your WordPress installation.
    | index for redirecting all requests to WordPress,
    | to_wp for redirecting to WP Admin after login.
    |
    */

    'redirect_index' => env('REDIRECT_INDEX', false),
    'redirect_login' => env('REDIRECT_LOGIN', false),
    'redirect_logout' => env('REDIRECT_LOGOUT', false),
    'redirect_editor' => env('REDIRECT_EDITOR', false),
    // Deprecated
    'redirect_to_wp' => env('REDIRECT_TO_WP', false),
    // New
    'redirect_after_login' => env('REDIRECT_AFTER_LOGIN', ''),
    // Default: '' means: go to Moox Admin
    // Frontend: 'frontend' means: go to frontend (currently WordPress)
    // Admin: 'wpadmin' means: go to wp-admin

    /*
    |--------------------------------------------------------------------------
    | Moox Roles to WordPress Roles
    |--------------------------------------------------------------------------
    |
    | This array maps the Moox roles to the WordPress roles.
    |
    */

    'moox_roles' => [
        'System Administrator' => 'Administrator',
        'Administrator' => 'Editor',
        'Editor' => 'Editor',
        'Author' => 'Author',
        'Contributor' => 'Contributor',
        'Subscriber' => 'Subscriber',
    ],

    /*
    |--------------------------------------------------------------------------
    | WordPress Roles and Capabilities
    |--------------------------------------------------------------------------
    |
    | This array maps the WordPress roles to the serialized capabilitiy
    | array, that is stored in the usermeta table.
    |
    */

    'wp_roles' => [
        'Administrator' => serialize(['administrator' => true]),
        'Editor' => serialize(['editor' => true]),
        'Author' => serialize(['author' => true]),
        'Contributor' => serialize(['contributor' => true]),
        'Subscriber' => serialize(['subscriber' => true]),
        'No role for this site' => serialize(['' => true]),
    ],

    'wp_user_levels' => [
        'administrator' => 10,
        'editor' => 7,
        'author' => 2,
        'contributor' => 1,
        'subscriber' => 0,
    ],

    'default_user_attributes' => [
        'user_registered' => now()->toDateTimeString(),
        'user_status' => '0',
        // TODO: suppress errors for first and last name
        // 'display_name' => $first_name.' '.$last_name ?? $user_login ?? '',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Meta
    |--------------------------------------------------------------------------
    |
    | These are the user meta keys for the WordPress users. Defined meta
    | keys will be appended to the user model and can be used
    | in the application. Be careful with this.
    |
    */

    'default_user_meta' => [
        'nickname' => '',
        'first_name' => '',
        'last_name' => '',
        'description' => '',
        'rich_editing' => 'true',
        'comment_shortcuts' => 'false',
        'admin_color' => 'fresh',
        'use_ssl' => '0',
        'show_admin_bar_front' => 'true',
        env('WP_PREFIX', 'wp_').'capabilities' => serialize([
            'subscriber' => true,
        ]),
        env('WP_PREFIX', 'wp_').'user_level' => '0',
        'dismissed_wp_pointers' => '',
        env('WP_PREFIX', 'wp_').'dashboard_quick_press_last_post_id' => '0',
        'remember_token' => '',
        'mm_sua_attachment_id' => '',
        // locale currently not used
        // syntax_highlighting currently not used
    ],

    /*
    |--------------------------------------------------------------------------
    | User Avatar Attachment ID meta key
    |--------------------------------------------------------------------------
    |
    | This is the user meta key for the WordPress user avatar attachment ID.
    | It is used to store the attachment ID of the user avatar.
    | Must contain the ID of the Post of type attachment.
    |
    */

    'user_avatar_meta' => 'mm_sua_attachment_id',

    /*
    |--------------------------------------------------------------------------
    | Post Meta
    |--------------------------------------------------------------------------
    |
    | These are the post meta keys for the WordPress posts. Defined meta
    | keys will be appended to the post model and can be used
    | in the application. Be careful with this.
    |
    */

    'default_post_meta' => [
        '_wp_page_template' => '',
        '_edit_lock' => '',
        '_edit_last' => '',
        '_thumbnail_id' => '',
        '_wp_attached_file' => '',
        '_wp_attachment_metadata' => '',
        '_wp_old_slug' => '',
        '_wp_trash_meta_status' => '',
        '_wp_trash_meta_time' => '',
        '_pingme' => '',
        '_encloseme' => '',
        '_menu_order' => '',
        '_wp_post_lock' => '',
        '_wp_post_revision' => '',
        '_wp_post_type' => '',
        '_wp_old_date' => '',
        '_wp_old_status' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth guards
    |--------------------------------------------------------------------------
    |
    | Define the columns for the username, email and password for the
    | different guards. This is necessary for the login process
    | to allow login with username or email address.
    |
    */

    'auth' => [
        'press' => [
            'username' => 'name',
            'email' => 'email',
            'password' => 'password',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WordPress User Model
    |--------------------------------------------------------------------------
    |
    | If you use a custom WordPress User Model, you can define it here.
    | We already provide a default model for WordPress users.
    |
    */

    'wpModel' => WpUser::class,

    /*
    |--------------------------------------------------------------------------
    | Password Validation
    |--------------------------------------------------------------------------
    |
    | Define the password validation rules for your WordPress users.
    | If you want to be hacked pretty soon, you can disable
    | the password validation by emptying the rules.
    |
    */

    'password' => [
        'validation' => [
            'rules' => Password::min(20)
                ->max(64)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
        ],
        'helperText' => 'Das Passwort muss zwischen 20 und 64 Zeichen lang sein, Groß- und Kleinbuchstaben, Zahlen und Sonderzeichen enthalten.',
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | Enable or disable the API and configure all entities.
    | Public or secured by platform or sanctum.
    | Available at /api/{entity}
    |
    */

    'use_api' => true,
    'entities' => [
        'wp_users' => [
            'api' => [
                'enabled' => true,
                'public' => false, // false for private, true for public
                'auth_type' => 'platform', // 'platform' for platform tokens or 'sanctum' for user-tied tokens
                'route_only' => ['index', 'show', 'store', 'destroy', 'update'],
            ],
        ],
    ],
    'ip_whitelist' => config('user-session.whitelisted_ips'),
];
