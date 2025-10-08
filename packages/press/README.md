# Moox Press

Say Hello to Laravel without saying Goodbye to WordPress.

Moox Press is a Laravel package, Filament plugin and WordPress MU plugin that allows to integrate and smoothly migrate from WordPress to Laravel.

It allows to

-   Migrate the WordPress DB to Laravel
-   Use the Laravel tables in WordPress
-   Use Laravel and WordPress simultanously
-   Smoothly migrate to Laravel by
    -   Replacing the WordPress Frontend with Moox Frontend
    -   Replacing the WordPress Admin with Filament Admin Panel
    -   Use Moox Post, Page, and "CPTs" with Gutenberg

## Requirements

-   PHP >= 8.3
-   Laravel >= 11
-   Filament 4
-   WordPress >= 6.5

## Installation

Moox Press can be installed by using the Moox Installer:

```bash
composer require moox/press
php artisan moox:install
```

The Moox Installer will

-   Install Filament and all required Packages for Laravel
-   Install WordPress and the MU Plugins to get the brigde running
-   Connect your existing Moox Data with WordPress, provide demo data or migrate your WordPress database

## Configuration

For Herd and Valet use one of the files from the wsconfig folder.

For NGINX:

```config
    // after this
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    // add this
    location /wp/ {
        try_files $uri $uri/ /wp/index.php?$query_string;
    }
```

For Apache (.htaccess):

```apache
RewriteEngine On
RewriteRule ^wp/(.*)$ /wp/index.php [QSA,L]
```

## Migration

When the initial installation is already done, and you might have forgotten to migrate your WordPress, you can use the following command:

```bash
php artisan moox:migratewp
```

The Moox Migrate WP command will help you to get your existing WordPress data (DB + Upload) into Moox. Please note, that we do not fully support a lot of WordPress plugins and page builders. To wire that stuff might be hard work. To get things like Elementor and WPML running, it might be a good idea to prepare the DB using existing migration tools in WP.

## Development

While there is a working package and WP plugin that currently works by providing Laravel models for WP tables, we are planning to develop Moox Press from scratch and use the Laravel databases and proxy them to fake WP tables. A new concept that will allow us to instantly get all Moox Entities running as CPT, Custom Taxonomy with Custom Fields / ACF.

### Package Structure

The Laravel package Moox Press (moox/press) cares for all requirements:

```json

```

and it provides Moox Press MU Plugins, versioned with the Laravel package

```php
<?php
require_once WP_CONTENT_DIR . '/../vendor/moox/press/src/WPDBProxy.php';
require_once WP_CONTENT_DIR . '/../vendor/moox/press/src/FileProxy.php';
require_once WP_CONTENT_DIR . '/../vendor/moox/press/src/WPRouting.php';
```

as well as some config of course

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Press Mapping
    |--------------------------------------------------------------------------
    |
    | This configuration is used to map Moox Entities to WordPress.
    |
    */
    'mapping' => [
      'post' => [
        'moox/post' => 'post',
        'moox/page' => 'page',
      	'moox/media' => 'attachment',
        'moox/product' => 'product',
      ],
      'taxonomy' => [
      	'moox/category' => 'category',
      	'moox/tag' => 'tag',
      ],
      'user' => [
        'moox/user' => 'user',
        'moox/permission' => 'capability',
      ],
		],

    /*
    |--------------------------------------------------------------------------
    | Press Roles
    |--------------------------------------------------------------------------
    |
    | Mapping roles for user (BE), webuser (FE) and customer (Shop).
    |
    */
    'roles' => [
        'user' => [
          'super_admin',
          'administrator',
          'editor',
          'author',
          'contributor',
          'shop_manager',
        ],
        'webuser' => [
          'subscriber',
        ],
        'customer' => [
          'customer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Press Routing
    |--------------------------------------------------------------------------
    |
    | Enable or Disable WordPress Features and redirects.
    |
    */
  	'enable_features' => [
      	'enable_wpdebug' => env('ENABLE_WPDEBUG', false),
        'enable_wpadmin' => env('ENABLE_WPADMIN', false),
        'enable_wplogin' => env('ENABLE_WPLOGIN', false),
        'enable_website' => env('ENABLE_WEBSITE', false),
        'secure_website' => env('SECURE_WEBSITE', false),
        'redirect_to_wp' => env('REDIRECT_TO_WP', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Press Revisions
    |--------------------------------------------------------------------------
    |
    | How many revisions should be kept in sync between Laravel and WP.
    |
    */
    'revisions' => [
        'enabled' => true,
        'max' => 10,
    ],

];
```

and a wp-config file

```php
<?php

use Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__.'/../../');
$dotenv->load();

define('WP_SITEURL', $_ENV['APP_URL'].$_ENV['PRESS_URL_SLUG']);
define('WP_HOME', $_ENV['APP_URL'].$_ENV['PRESS_URL_SLUG']);

$wp_debug = isset($_ENV['ENABLE_WPDEBUG']) ? $_ENV['ENABLE_WPDEBUG'] : false;

define('WP_DEBUG', $wp_debug);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__.'/');
}

require_once ABSPATH.'wp-settings.php';
```

and finally some .env-values

```
PRESS_URL_SLUG="/wp"
ENABLE_WPDEBUG=false
ENABLE_WPADMIN=false
ENABLE_WPLOGIN=false
ENABLE_WEBSITE=false
SECURE_WEBSITE=false
REDIRECT_TO_WP=false
```

## Development

Needs

-   MU-plugin to replace WPDB with our Proxy Setup, needs to handle Posts, CPTs, Media, Taxonomies and (A)CF automatically

```php
<?php
class Moox_WPDB extends wpdb {
    // override methods like get_results, insert, update, etc.
}

// Replace global $wpdb
global $wpdb;
$wpdb = new Moox_WPDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
```

-   MU-plugin to override file handling

```php
<?php
/**
 * Plugin Name: Moox Media Proxy
 * Description: Leitet WordPress-Medienpfade transparent auf Laravel Storage oder CDN um.
 * Beispiel mit month/year based folders ...
 */

/**
 * Intercept upload path (for storing uploads)
 */
add_filter('upload_dir', function ($dirs) {
    // Dynamische Basis-URL (z. B. via Laravel-CDN oder Storage disk)
    $baseurl = 'https://cdn.moox.app/uploads';  // dein Pfad zum Laravel Storage
    $subdir  = date('/Y/m'); // z. B. /2025/10

    return [
        'path'    => '/dev/null', // blockiert echten FS-Zugriff
        'url'     => $baseurl . $subdir,
        'subdir'  => $subdir,
        'basedir' => '/dev/null',
        'baseurl' => $baseurl,
        'error'   => false,
    ];
});


/**
 * Generate attachment URL (read)
 */
add_filter('wp_get_attachment_url', function ($url, $post_id) {
    $file = get_post_meta($post_id, '_wp_attached_file', true);
    if (!$file) return $url;

    // Gleiche Base-URL wie oben
    $baseurl = 'https://cdn.moox.app/uploads';

    return $baseurl . '/' . ltrim($file, '/');
}, 10, 2);
```

-   Import that converts the WPDB to Moox / Laravel and moves the files
-   WordPress > 5.5 using Bcrypt, Laravel using Bcrypt, too
-   When importing old hashes convert from PHPass ($P$ prefix) to Bcrypt
-   Multisite always, even if one language

```bash
wp config set WP_ALLOW_MULTISITE true --raw
wp core multisite-convert --title="Mein Netzwerk" --base="/" --subdomains=false
wp site create --slug=fr --title="Französisch" --email=admin@example.com --locale=fr_FR
# then
wp option update upload_path 'wp-content/uploads'
wp option update uploads_use_yearmonth_folders 1
```

or wp-config.php

```php
define( 'WP_ALLOW_MULTISITE', true );
// then
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false ); // oder true für Subdomain-Mode
define( 'DOMAIN_CURRENT_SITE', 'example.com' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );
```

Supports ...

-   Multisite for Languages - like de.moox.org, en.moox.org or /en and /de or even domain routing
-   Multisite for Segmentation - like shoes.moox.org, shirts.moox.org, moox.org/bananas
-   Both a the same time - Mix Segementation or Tenancy with Languages without chaos
-   Multinetwork ...?

Compatible ....

-   https://msls.co/ - FOSS, simple Multisite Language Switcher plugin
-   https://multilingualpress.org/ - Commercial, Enterprise Multisite Translation Plugin
-   https://pluginize.com/plugins/custom-post-type-ui/ - and Custom Post Types in common
-   https://www.advancedcustomfields.com/ - and Custom Fields in common, can problably generate the ACF arrays
-   https://wordpress.org/plugins/simple-user-avatar/ (not Media compatible) OR better https://wordpress.org/plugins/simple-local-avatars/
-   https://wordpress.org/plugins/wp-multi-network/ AND https://wordpress.org/plugins/multisite-enhancements/ =?

Should be compatible in the future (PRO) ...

-   WP Forms, maybe Gravity Forms
-   Yoast SEO
-   WooCommerce
-   WPML (transform on import)
-   Redis Object Cache ...?

## WP Tables

-   `wp_{blog_id}_posts`
-   `wp_{blog_id}_postmeta`
-   `wp_{blog_id}_terms`
-   `wp_{blog_id}_term_taxonomy`
-   `wp_{blog_id}_term_relationships`
-   `wp_{blog_id}_termmeta`
-   `wp_{blog_id}_comments`
-   `wp_{blog_id}_commentmeta`
-   `wp_{blog_id}_links`
-   `wp_{blog_id}_options`

-   `wp_users`
-   `wp_usermeta`
-   `wp_blogs`
-   `wp_site`
-   `wp_sitemeta`
-   `wp_blog_versions`

-   `wp_registration_log`
-   `wp_signups`
-   `wp_blogmeta`

## Posts

### wp_posts

| **Field**             | **Type**            | **Description**                               | **Mapping**             |
| --------------------- | ------------------- | --------------------------------------------- | ----------------------- |
| ID                    | bigint(20) unsigned | Primärschlüssel                               | id                      |
| post_author           | bigint(20) unsigned | User-ID des Autors                            | author_id               |
| post_date             | datetime            | Veröffentlichungsdatum (lokal)                | published_at (local)    |
| post_date_gmt         | datetime            | Veröffentlichungsdatum GMT                    | published_at            |
| post_content          | longtext            | Volltext / Body-Inhalt                        | content                 |
| post_title            | text                | Titel                                         | title                   |
| post_excerpt          | text                | Auszug / Teaser                               | excerpt                 |
| post_status           | varchar(20)         | z. B. publish, draft, private                 | status                  |
| comment_status        | varchar(20)         | open / closed                                 | comment_status          |
| ping_status           | varchar(20)         | Pingback/Trackback erlaubt                    | ❌                      |
| post_password         | varchar(255)        | Passwortschutz                                | password                |
| post_name             | varchar(200)        | Slug / Permalink                              | slug                    |
| to_ping               | text                | Liste URLs zum Pingen                         | ❌                      |
| pinged                | text                | Bereits gepingte URLs                         | ❌                      |
| post_modified         | datetime            | Zuletzt geändert                              | updated_at (local)      |
| post_modified_gmt     | datetime            | Wie oben, GMT                                 | updated_at              |
| post_content_filtered | longtext            | Gefilterter Content (nicht verwendet)         | ❌                      |
| post_parent           | bigint(20) unsigned | ID des Eltern-Posts                           | parent_id               |
| guid                  | varchar(255)        | ..xmpl.com/?p=123 or ...ntent/uploads/img.jpg | url (make host dynamic) |
| menu_order            | int(11)             | Reihenfolge (z. B. Pages)                     | order                   |
| post_type             | varchar(20)         | post, page, attachment, cpt                   | depends on the entity   |
| post_mime_type        | varchar(100)        | image/jpeg, application/pdf, video/mp4        | only media              |
| comment_count         | bigint(20)          | Anzahl Kommentare                             | comments counted        |

### wp_postmetas

| **Field**  | **Type**                                | **Description**                         |
| ---------- | --------------------------------------- | --------------------------------------- |
| meta_id    | bigint(20) unsigned, PK, auto-increment | Primary key                             |
| post_id    | bigint(20) unsigned                     | Foreign key to wp_posts.ID              |
| meta_key   | varchar(255)                            | Name of the field                       |
| meta_value | longtext                                | Value (string, array, serialized, JSON) |

### Post types

| post_type     | **Description**    | **Native?**             | **Notes**                           |
| ------------- | ------------------ | ----------------------- | ----------------------------------- |
| post          | Standard blog post | ✅ Core                 | Usually shown on the blog           |
| page          | Static pages       | ✅ Core                 | Hierarchical                        |
| attachment    | Media uploads      | ✅ Core                 | Tied to post_parent (original post) |
| revision      | Post revisions     | ✅ Core                 | Optional for versioning             |
| nav_menu_item | Menu entries       | ✅ Core                 | For WP menus                        |
| custom_css    | Theme CSS          | ✅ Core                 | Used by Customizer                  |
| cpt_xyz       | Custom Post Types  | ❌ Plugin/theme-defined | E.g. product, event, etc.           |
| wp_block      | Reusable blocks    | ✅ Core                 | since Gutenberg                     |

## Taxononomies

### wp_terms

| **Column** | **Type**            | **Notes**                 |
| ---------- | ------------------- | ------------------------- |
| term_id    | bigint(20) unsigned | Primary key               |
| name       | varchar(200)        | Human-readable name       |
| slug       | varchar(200)        | Unique slug               |
| term_group | bigint(10)          | Legacy (not used anymore) |

### wp_term_taxonomy

| **Column**       | **Type**            | **Notes**                            |
| ---------------- | ------------------- | ------------------------------------ |
| term_taxonomy_id | bigint(20) unsigned | Primary key                          |
| term_id          | bigint(20) unsigned | FK → wp_terms.term_id                |
| taxonomy         | varchar(32)         | e.g. category, post_tag, product_cat |
| description      | longtext            | Optional description                 |
| parent           | bigint(20) unsigned | For hierarchical taxonomies          |
| count            | bigint(20)          | Auto-filled: how many posts use this |

### wp_term_relationships

| **Column**       | **Type**            | **Notes**                              |
| ---------------- | ------------------- | -------------------------------------- |
| object_id        | bigint(20) unsigned | FK → wp_posts.ID                       |
| term_taxonomy_id | bigint(20) unsigned | FK → wp_term_taxonomy.term_taxonomy_id |
| term_order       | int(11)             | Usually 0, rarely used                 |

### wp_termmeta

| **Column** | **Type**            | **Notes**                     |
| ---------- | ------------------- | ----------------------------- |
| meta_id    | bigint(20) unsigned | Primary key                   |
| term_id    | bigint(20) unsigned | FK → wp_terms.term_id         |
| meta_key   | varchar(255)        | e.g. ACF field name           |
| meta_value | longtext            | Stored as string / serialized |

## WP Media

-   `wp_options.upload_path` - default: wpcontent/uploads
-   `wp_options.upload_url_path`- default: empty
-   `wp_options.uploads_use_yearmonth_folders`- default: 0 or 1 for e. g. wp-content/uploads/2025/10/image.jpg
-   `wp_posts.guid`= Original Upload-URL e. g. https://example.com/wp-content/uploads/2025/10/image.jpg
-   `wp_postmeta._wp_attached_file`= Relative path e. g. 2025/10/image.jpg
-   `wp_postmeta._wp_attachment_metadata`= Serialized array

```php
[
  'width' => 1200,
  'height' => 800,
  'file' => '2025/10/image.jpg',
  'sizes' => [
    'thumbnail' => [
      'file' => 'image-150x150.jpg',
      'width' => 150,
      'height' => 150,
    ],
    ...
  ],
]
```

## WP User

### wp_users

| **Column**          | **Type**            | **Notes**                                  |
| ------------------- | ------------------- | ------------------------------------------ |
| ID                  | bigint(20) unsigned | Primary key                                |
| user_login          | varchar(60)         | Unique username                            |
| user_pass           | varchar(255)        | Hashed password (portable PHPass / bcrypt) |
| user_nicename       | varchar(50)         | Slug-safe version of login (for URLs)      |
| user_email          | varchar(100)        | Email address                              |
| user_url            | varchar(100)        | Website URL (optional)                     |
| user_registered     | datetime            | Registration date                          |
| user_activation_key | varchar(255)        | Used for password reset, activation        |
| user_status         | int(11)             | Legacy, always 0                           |
| display_name        | varchar(250)        | Shown name (overrides nicename)            |

### wp_usermeta

| **Column** | **Type**            | **Notes**                                                       |
| ---------- | ------------------- | --------------------------------------------------------------- |
| umeta_id   | bigint(20) unsigned | Primary key                                                     |
| user_id    | bigint(20) unsigned | FK → wp_users.ID                                                |
| meta_key   | varchar(255)        | e.g. nickname, first_name, wp_capabilities, wp_user_level, etc. |
| meta_value | longtext            | Stored as string or serialized                                  |

### Important meta keys

| **Key**                               | **Description**            | **Notes**                                         |
| ------------------------------------- | -------------------------- | ------------------------------------------------- |
| nickname                              | Display nickname           | UI fallback                                       |
| first_name                            | User first name            | Used in backend/profile                           |
| last_name                             | User last name             | Optional                                          |
| description                           | Biographical info          | Appears in author templates                       |
| rich_editing                          | true / false               | Visual editor enabled                             |
| comment_shortcuts                     | true / false               | Keyboard shortcuts in comment moderation          |
| admin_color                           | Admin color scheme         | e.g. fresh, blue, ectoplasm                       |
| use_ssl                               | 1 if forcing SSL for admin | Optional                                          |
| show_admin_bar_front                  | true / false               | Show admin bar when viewing site                  |
| locale                                | e.g. en_US, de_DE          | Backend language                                  |
| wp_user_level                         | 0–10                       | Legacy role level                                 |
| wp_capabilities                       | Serialized array of roles  | e.g. a:1:{s:13:"administrator";b:1;}              |
| wp_dashboard_quick_press_last_post_id | Internal                   | Can ignore                                        |
| session_tokens                        | Active login sessions      | Can ignore                                        |
| dismissed_wp_pointers                 | UI flags                   | Can ignore                                        |
| wp_autosave_draft_ids                 | Draft autosave references  | Rare                                              |
| wp_last_edit_timestamp                | Optional                   | Track profile edits                               |
| wp_1_capabilities                     | Roles for site ID 1        | Multisite                                         |
| wp_1_user_level                       | Level for site ID 1        | Multisite                                         |
| see table 1                           | Currently used             | https://github.com/10up/simple-local-avatars      |
| see table 2                           | Better with media          | https://github.com/MatteoManna/Simple-User-Avatar |

| **Feature**   | **Field**                          | **Type** | **Notes**                  |
| ------------- | ---------------------------------- | -------- | -------------------------- |
| Avatar ID     | simple_local_avatar.media_id       | usermeta | attachment post ID         |
| Avatar URL(s) | simple_local_avatar.full, 96, etc. | usermeta | Derived from WP media      |
| Rating        | simple_local_avatar_rating         | usermeta | Optional                   |
| Default       | simple_local_avatar_default        | option   | Global fallback            |
| Site sharing  | simple_local_avatars               | option   | Controls blog-wide sharing |

| **Feature**      | **Field**                    | **Type** | **Notes**                                          |
| ---------------- | ---------------------------- | -------- | -------------------------------------------------- |
| Avatar URL       | simple_user_avatar           | usermeta | Stores full image URL directly (not attachment ID) |
| Avatar MIME type | simple_user_avatar_mime_type | usermeta | Optional — image validation                        |
| Avatar filename  | simple_user_avatar_filename  | usermeta | Optional — original name                           |
| Avatar upload    | stored in uploads dir        | file     | Not in Media Library (by default)                  |

### wp_comments

| **Column**           | **Type**            | **Notes**                   |
| -------------------- | ------------------- | --------------------------- |
| comment_ID           | bigint(20) unsigned | Primary key                 |
| comment_post_ID      | bigint(20) unsigned | FK → wp_posts.ID            |
| comment_author       | tinytext            | Name (not FK)               |
| comment_author_email | varchar(100)        |                             |
| comment_author_url   | varchar(200)        |                             |
| comment_author_IP    | varchar(100)        |                             |
| comment_date         | datetime            | Local time                  |
| comment_date_gmt     | datetime            | UTC                         |
| comment_content      | text                |                             |
| comment_karma        | int(11)             | Legacy                      |
| comment_approved     | varchar(20)         | 1, 0, or spam               |
| comment_agent        | varchar(255)        | User agent                  |
| comment_type         | varchar(20)         | e.g. comment, pingback      |
| comment_parent       | bigint(20)          | Threading                   |
| user_id              | bigint(20) unsigned | FK → wp_users.ID (optional) |

### wp_options

| **Column**   | **Type**            | **Notes**                      |
| ------------ | ------------------- | ------------------------------ |
| option_id    | bigint(20) unsigned | Primary key                    |
| option_name  | varchar(191)        | Unique key                     |
| option_value | longtext            | Can be serialized              |
| autoload     | varchar(20)         | yes or no (load on every page) |

## wp_links

Deprecated

### wp_blogs

| **Column**   | **Type**            | **Notes**            |
| ------------ | ------------------- | -------------------- |
| blog_id      | bigint(20) unsigned | Primary key          |
| site_id      | bigint(20) unsigned | FK → wp_site.site_id |
| domain       | varchar(200)        | e.g. example.com     |
| path         | varchar(100)        | e.g. /de/, /fr/      |
| registered   | datetime            |                      |
| last_updated | datetime            |                      |
| public       | tinyint(2)          | 1 = visible          |
| archived     | tinyint(2)          |                      |
| mature       | tinyint(2)          |                      |
| spam         | tinyint(2)          |                      |
| deleted      | tinyint(2)          |                      |
| lang_id      | bigint(20)          | Rarely used          |

### wp_site

| **Column** | **Type**            | **Notes**             |
| ---------- | ------------------- | --------------------- |
| id         | bigint(20) unsigned | Primary key           |
| domain     | varchar(200)        |                       |
| path       | varchar(100)        | Root path (usually /) |

### wp_sitemeta

| **Column** | **Type**            | **Notes**        |
| ---------- | ------------------- | ---------------- |
| meta_id    | bigint(20) unsigned | Primary key      |
| site_id    | bigint(20) unsigned | FK → wp_site.id  |
| meta_key   | varchar(255)        | e.g. site_admins |
| meta_value | longtext            |                  |

### wp_blog_versions

| **Column**   | **Type**            | **Notes**                  |
| ------------ | ------------------- | -------------------------- |
| db_version   | varchar(20)         | Internal DB schema version |
| last_updated | datetime            | For each blog              |
| blog_id      | bigint(20) unsigned |                            |

### wp_registration_log

Deprecated

### wp_signups

| **Column**     | **Type**              | **Notes**    |
| -------------- | --------------------- | ------------ |
| signup_id      | bigint(20) unsigned   | Primary key  |
| domain         | varchar(200)          | For new blog |
| path           | varchar(100)          |              |
| title          | longtext              |              |
| user_login     | varchar(60)           |              |
| user_email     | varchar(100)          |              |
| registered     | datetime              |              |
| activated      | datetime              |              |
| active         | tinyint(1)            | 0/1          |
| activation_key | varchar(50)           |              |
| meta           | longtext (serialized) | Extra data   |

### wp_blogmeta

| **Column** | **Type**            | **Notes**             |
| ---------- | ------------------- | --------------------- |
| meta_id    | bigint(20) unsigned | Primary key           |
| blog_id    | bigint(20) unsigned | FK → wp_blogs.blog_id |
| meta_key   | varchar(255)        |                       |
| meta_value | longtext            | Serialized or raw     |

```php
public function revisions(): HasMany
{
    return $this->hasMany(Post::class, 'parent_id')
        ->where('post_type', 'revision');
}
```

```php
public function syncRolesFromWp(User $user, array $capabilities): void
{
    foreach ($capabilities as $wpRole => $active) {
        if ($active && isset($map[$wpRole])) {
            $user->assignRole($map[$wpRole]);
        }
    }
}
```

```php
$table->uuid('locked_by')->nullable(); // editing lock
$table->timestamp('locked_at')->nullable();
$table->json('meta')->nullable();      // for dynamic fields / ACF mapping
$table->uuid('wp_origin_id')->nullable(); // original WP ID
```

| **Role**          | **Scope**    | **Main Capabilities**                                                                                              |
| ----------------- | ------------ | ------------------------------------------------------------------------------------------------------------------ |
| **super_admin**   | Network‑wide | Full access to all sites in the network; manage network settings, sites, users, themes, and plugins.               |
| **administrator** | Single site  | Full control of a single site: manage users, posts, pages, settings, plugins, and themes (site‑level only).        |
| **editor**        | Content      | Create, edit, publish, and delete **any** posts or pages (own and others’); manage categories, tags, and comments. |
| **author**        | Own content  | Create, edit, publish, and delete **own** posts; upload media.                                                     |
| **contributor**   | Drafts only  | Create and edit **own** posts, but **cannot** publish or upload media; posts must be reviewed by an editor/admin.  |
| **subscriber**    | Read‑only    | Read content and manage **own profile only** (e.g., name, password).                                               |
| **customer**      | WooCommerce  | Read own profile- View/purchase orders- Comment- No admin access                                                   |
| **shop_manager**  | WooCommerce  | - Manage WooCommerce orders/products- Limited dashboard access                                                     |

Discuss

-   Versioning, must be proxied for WP as revisions but done in Moox, how?
-   Roles, must be done simliarily in Moox like in WP
-   Logging, must be done in Moox I guess
-   Hooks and Events can be used in Laravel, optionally
-   Atomic Lock Feature: locked_by and locked_at
-   WP Health
