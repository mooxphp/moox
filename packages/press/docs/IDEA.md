# Idea

-   Remember Login, Long Session Cookie
-   Redirect Login
-   Redefine env for WP/PRESS
    -   WP_PREFIX -> WP_TABLE_PREFIX
    -   WP_PATH -> WP_PUBLIC_PATH
    -   WP_SLUG -> WP_URL_SLUG
    -   AUTH_WP -> WP_MOOX_LOGIN
    -   LOCK_WP -> WP_FRONTEND_LOGIN
    -   REDIRECT_INDEX -> WP_FRONTEND // VS Laravel
    -   REDIRECT_LOGOUT -> ...
-   'redirect_index' => env('REDIRECT_INDEX', false),
-   'redirect_login' => env('REDIRECT_LOGIN', false),
-   'redirect_logout' => env('REDIRECT_LOGOUT', false),
-   'redirect_editor' => env('REDIRECT_EDITOR', false),
-   // Deprecated 'redirect_to_wp' => env('REDIRECT_TO_WP', false),
-   // New 'redirect_after_login' => env('REDIRECT_AFTER_LOGIN', ''),
-   // Default: '' means: go to Moox Admin
-   // Frontend: 'frontend' means: go to frontend (currently WordPress)
-   // Admin: 'wpadmin' means: go to wp-admin
-   https://github.com/MatteoManna/Simple-User-Avatar ersetzen
-   Nochmal alle Relations checken, comments, tags etc.
-   Neues Package heco Intra
    -   Wiki
    -   Taxonomien von Wiki
    -   Custom Models, Traits, Views von https://github.com/heco-gmbh/intranet-frontend/
    -   daraus einen Builder oder eine Doku
-   Press Installer
    -   Should test, if all envs are set and wp is available
    -   Should ask to install wp or refer to the manual installation, if not available
        -   Run wp-install and set the envs
    -   Should test, if the plugin is activated, otherwise refer to the docs
    -   Should install Moox Press
        -   Set auth and filesystem
        -   Create PressPanelProvider
        -   Jobs?
    -   Should ask to enable features and set the envs
        -   Should the WordPress Login be replaced by Moox Press Login?
        -   Where should we redirect to after login?
            -   Admin (default)
            -   Website
            -   WordPress Admin
            -   WordPress Website
        -   Should all requests to / be redirected to /wp?
            -   Yes (default)
            -   No
    -   Should do a final test or print the config.

## Readme

### New Laravel project

Even if it is possible to install Moox Press into an existing Laravel project, we recommend to start with a freshly installed Laravel:

### Install WordPress

Moox Press piggybacks on WordPress and connects to the WordPress database via Eloquent models. You are free, to install WordPress via Composer or manually into a subfolder of /public.

If you want to use Composer to manage WordPress, copy this composer.json into /public and do a `composer install`afterwards:

```json
{
    "name": "moox/wp-install",
    "type": "wordpress-core",
    "autoload": {
        "psr-4": {
            "Moox\\WpInstall\\": "src/"
        }
    },
    "authors": [
        {
            "name": "Moox Developers",
            "email": "devs@moox.org"
        }
    ],
    "require": {
        "roots/wordpress": "^6.4",
        "vlucas/phpdotenv": "^5.6"
    },
    "extra": {
        "wordpress-install-dir": "wp"
    },
    "config": {
        "allow-plugins": {
            "roots/wordpress-core-installer": true
        }
    }
}
```

If you want to manually install WordPress, just remove roots/wordpress from require and just install phpdotenv.

To use the .env file in WordPress, copy the wp-config.php to your WordPress folder and adapt it to your needs:

```php
<?php

use Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__.'/../../');
$dotenv->load();

define('DB_NAME', $_ENV['DB_DATABASE']);
define('DB_USER', $_ENV['DB_USERNAME']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

define('WP_SITEURL', $_ENV['APP_URL'].$_ENV['WP_SLUG']);
define('WP_HOME', $_ENV['APP_URL'].$_ENV['WP_SLUG']);

define('AUTH_KEY', $_ENV['WP_AUTH_KEY']);
define('SECURE_AUTH_KEY', $_ENV['WP_SECURE_AUTH_KEY']);
define('LOGGED_IN_KEY', $_ENV['WP_LOGGED_IN_KEY']);
define('NONCE_KEY', $_ENV['WP_NONCE_KEY']);
define('AUTH_SALT', $_ENV['WP_AUTH_SALT']);
define('SECURE_AUTH_SALT', $_ENV['WP_SECURE_AUTH_SALT']);
define('LOGGED_IN_SALT', $_ENV['WP_LOGGED_IN_SALT']);
define('NONCE_SALT', $_ENV['WP_NONCE_SALT']);

define('MOOX_HASH', $_ENV['APP_KEY']);

define('LOCK_WP', isset($_ENV['LOCK_WP']) ? $_ENV['LOCK_WP'] : false);
define('AUTH_WP', isset($_ENV['AUTH_WP']) ? $_ENV['AUTH_WP'] : false);
define('REDIRECT_INDEX', isset($_ENV['REDIRECT_INDEX']) ? $_ENV['REDIRECT_INDEX'] : false);
define('REDIRECT_TO_WP', isset($_ENV['REDIRECT_TO_WP']) ? $_ENV['REDIRECT_TO_WP'] : false);
define('REDIRECT_LOGIN', isset($_ENV['REDIRECT_LOGIN']) ? $_ENV['REDIRECT_LOGIN'] : false);
define('REDIRECT_LOGOUT', isset($_ENV['REDIRECT_LOGOUT']) ? $_ENV['REDIRECT_LOGOUT'] : false);
define('REDIRECT_EDITOR', isset($_ENV['REDIRECT_EDITOR']) ? $_ENV['REDIRECT_EDITOR'] : false);
define('FORGOT_PASSWORD', isset($_ENV['FORGOT_PASSWORD']) ? $_ENV['FORGOT_PASSWORD'] : false);
define('REGISTRATION', isset($_ENV['REGISTRATION']) ? $_ENV['REGISTRATION'] : false);
define('ENABLE_MFA', isset($_ENV['ENABLE_MFA']) ? $_ENV['ENABLE_MFA'] : false);

$table_prefix = isset($_ENV['WP_PREFIX']) ? $_ENV['WP_PREFIX'] : 'wp_';

$wp_debug = isset($_ENV['WP_DEBUG']) ? $_ENV['WP_DEBUG'] : false;

define('WP_DEBUG', $wp_debug);

if (! defined('ABSPATH')) {
	define('ABSPATH', __DIR__.'/');
}

require_once ABSPATH.'wp-settings.php';
```

### WordPress Plugin

Then install the WordPress plugin: https://github.com/mooxphp/press-wp. Simply download the ZIP-file and install it via WP Admin or manually into your plugins folder. Don't forget to activate.

### Laravel Package

Do a

```bash
composer require moox/press
php artisan mooxpress:install
```

Todo: test and finish the installer

### WordPress Single-SignOn

To use your WordPress users to login into Laravel, you need to add

```php
->login(Moox\Press\Services\Login::class)
```

to your AdminPanelProvider.php.

Todo: add Login Class by installer

### Activate Filament Resources

How to manually ...

```php
->plugins([
	\Moox\Press\WpUserPlugin::make(),
	\Moox\Press\WpPostPlugin::make(),
	\Moox\Press\WpMediaPlugin::make(),
	\Moox\Press\WpPagePlugin::make(),
	\Moox\Press\WpPostMetaPlugin::make(),
	\Moox\Press\WpUserMetaPlugin::make(),
	\Moox\Press\WpOptionPlugin::make(),
	\Moox\Press\WpTermMetaPlugin::make(),
	\Moox\Press\WpTermRelationshipPlugin::make(),
	\Moox\Press\WpTermPlugin::make(),
	\Moox\Press\WpTermTaxonomyPlugin::make(),
	\Moox\Press\WpCommentMetaPlugin::make(),
	\Moox\Press\WpCommentPlugin::make(),
]);
```

Todo: installer

### Env and Config

-   alle env-Schalter erklären, SSO ...

```
APP_KEY=base64:oPHLftv+xN9Q68KvBhlSUpNdmpv8+oL0h/bNr+Yyisg=

WP_PREFIX="jku8u_"
WP_PATH="/public/wp"
WP_SLUG="/wp"
ADMIN_SLUG="/admin"
WP_DEBUG=false

IP_WHITELIST=""

LOCK_WP=true
AUTH_WP=true
REDIRECT_INDEX=true
REDIRECT_TO_WP=true
REDIRECT_LOGIN=true
REDIRECT_LOGOUT=true
REDIRECT_EDITOR=true
FORGOT_PASSWORD=false
REGISTRATION=false
ENABLE_MFA=false

WP_AUTH_KEY="ThisIsASecretKey!DoNotUseItInProduction!ChangeIt!"
WP_SECURE_AUTH_KEY="ThisIsASecretKey!DoNotUseItInProduction!ChangeIt!"
WP_LOGGED_IN_KEY="ThisIsASecretKey!DoNotUseItInProduction!ChangeIt!"
WP_NONCE_KEY="ThisIsASecretKey!DoNotUseItInProduction!ChangeIt!"
WP_AUTH_SALT="ThisIsASecretKey!DoNotUseItInProduction!ChangeIt!"
WP_SECURE_AUTH_SALT="ThisIsASecretKey!DoNotUseItInProduction!ChangeIt!"
WP_LOGGED_IN_SALT="ThisIsASecretKey!DoNotUseItInProduction!ChangeIt!"
WP_NONCE_SALT="ThisIsASecretKey!DoNotUseItInProduction!ChangeIt!"
```

### The Auth flow

-   Kompletter Auth Flow

### Post model

```php
$postId = 1;
$post = Moox\Press\Models\WpPost::find($postId);
$prefix = "wp_";

// As the Post model is mounted onto wp_posts, these fields are natively available

var_dump($post->post_author);
var_dump($post->post_date);
var_dump($post->post_date_gmt);
var_dump($post->post_content);
var_dump($post->post_title);
var_dump($post->post_excerpt);
var_dump($post->post_status);
var_dump($post->comment_status);
var_dump($post->ping_status);
var_dump($post->post_password);
var_dump($post->post_name);
var_dump($post->to_ping);
var_dump($post->pinged);
var_dump($post->post_modified);
var_dump($post->post_modified_gmt);
var_dump($post->post_content_filtered);
var_dump($post->post_parent);
var_dump($post->guid);
var_dump($post->menu_order);
var_dump($post->post_type);
var_dump($post->post_mime_type);
var_dump($post->comment_count);

// Providing compatibility to Laravel (Moox Blog)

var_dump($user->id);
var_dump($post->content);
var_dump($post->title);
var_dump($post->slug);
var_dump($post->type);
var_dump($post->created_by);
var_dump($post->created_at);
var_dump($post->updated_by);
var_dump($post->updated_at);
var_dump($post->published_by);
var_dump($post->published_at);
var_dump($post->deleted_by);
var_dump($post->deleted_at);
var_dump($post->expires_at);

// While some of the most wanted meta fields are directly accessible and natively used in Filament like so

var_dump($post->_pingme);
var_dump($post->_encloseme);
var_dump($post->_edit_lock);
var_dump($post->_wp_attached_file);
var_dump($post->_wp_page_template);
var_dump($post->_wp_attachment_metadata);

// All meta fields are accesible like so

var_dump($user->meta("is_soon_to_be_expired"));

// Accessing ACF meta

var_dump($post->value);

// Accessing ACF groups and repeaters

var_dump($post->value);

```

### User model

```php
$userId = 1;
$user = Moox\Press\Models\WpUser::find($userId);
$prefix = "wp_";

// As the User model is mounted onto wp_users, these fields are natively available

var_dump($user->user_login);
var_dump($user->user_pass);
var_dump($user->user_nicename);
var_dump($user->user_email);
var_dump($user->user_url);
var_dump($user->user_registered);
var_dump($user->user_activation_key);
var_dump($user->user_status);
var_dump($user->display_name);
// multisite var_dump($user->spam);
// multisite var_dump($user->deleted);

// The WordPress users table lacks some features. Most Laravel packages working with users rely on these fields. Following fields are provided by the model, these fields are attributes, some stored as WordPress-compatible usermeta:

var_dump($user->id);
var_dump($user->name);
var_dump($user->email);
var_dump($user->password); // not bcrypt but phpass
var_dump($user->created_at); // set in boot-method
var_dump($user->updated_at); // set in boot-method
var_dump($user->remember_token);
var_dump($user->email_verified_at);

// While some of the most wanted meta fields are directly accessible and natively used in Filament like so

var_dump($user->first_name);
var_dump($user->last_name);
var_dump($user->nickname);
var_dump($user->description);
var_dump($user->session_tokens);
var_dump($user->moox_user_attachment_id); // Avatar

// All meta fields are accesible like so

var_dump($user->meta("rich_editing"));
var_dump($user->meta("syntax_highlighting"));

// Prefixed fields are accessible using the prefix or without prefix not getting confused by (otherwise not supporting) WP multisite

var_dump($user->meta($prefix . "capabilities"));
var_dump($user->meta("capabilities"));
var_dump($user->meta("user_level"));
var_dump($user->meta("user-settings"));
var_dump($user->meta("user-settings-time"));
var_dump($user->meta("media_library_mode"));
var_dump($user->meta("dashboard_quick_press_last_post_id"));
```

## Layouts

Grid, Switcher (see Core) for example for Media

-   https://gist.github.com/AAbosham/c6be7f2fac17bd3662a21d9f5da19156
-   https://filamentphp.com/plugins/tgeorgel-table-layout-toggle (see Core)
-   https://www.youtube.com/watch?v=N036kkmwTe0 Filament Daily showing simple Grid
-   https://filamentphp.com/docs/3.x/tables/layout#controlling-column-width-using-a-grid

others

-   remove lockfiles from all repos? package-lock too?

## Handling Rate Limiting Exceptions

Your handling of `TooManyRequestsException` by displaying a notification and potentially returning `null` is a sound approach. However, consider redirecting the user back to the previous page with an error message instead of returning `null`. This would provide a better user experience by not leaving the user on a potentially broken or unexpected state of the application:

phpCopy code

`return redirect()->back()->withErrors(['error' => 'Your custom error message here'])->withInput();`

### User Meta

-   Mit einem Relationmanager User -> Meta haben wir sofort Zugriff auf alle Metafelder im User-Kontext
-   Beim Erstellen eines neuen Benutzers müssen die Meta-Felder geschrieben werden

```php

// in config please

$wp_user_meta_array = [

	"nickname" => $data->user_login,
	"first_name" => $first_name
	$prefix . "_capabilities" => $from_cap_array

];


// in config please

$wp_user_capabilities = [

	"Administrator" => "a:1:{s:13:"administrator";b:1;}",

]



```

Key = "nickname"
Default = user_login

Key = "first_name"
Default = from virtual firstname field

Key = "rich_editing"
Default = true

Key = $prefix . "capabilties"
Default = a:1:{s:10:"subscriber";b:1;}

Key = "mm_sua_attachment_id"
Default = emtpy
Info:

-   meta_value is the ID of the post
-   file must be uploaded in /uploads/year/month
-   new entry in posts table (wohooooo)
-   meta key mit der post id

-   How to save these user_meta keys on or after save (event)
-   Role should be a single select field
-   Capabilities can be stored as ready-made serialized arrays
    -   User can only have ONE role
    -   roles are somewhat hardcoded

Role admin

```
a:1:{s:13:"administrator";b:1;}
```

Role editor

```
a:1:{s:6:"editor";b:1;}
```

### Todo in Readme:

-
-   filament user oder seeding

How to WP?

-   Install Laravel
-   Install Filament (require moox/press)
-   Provide a WordPress (manual or composer)
    -   composer require johnpbloch/wordpress-core
    -   ln -s ../vendor/johnpbloch/wordpress-core/ wp
    -   run symlink.sh (see below)
    -   "seed" DB
-   Do .envs
-   Do ENV for wp_config
-   Add Routes?
-   Edit config/auth.php (unfinished)
    -   $this->mergeConfigFrom( **DIR**.'/path/to/your/config/wpauth.php', 'auth' );

Installer:

-   Where is WP folder?

    -   Check if WP can be loaded
    -   Set config value
    -   Create symlinks

-   install wp in public
-   create folders
    -   wp-content
        -   themes
            -   ...

```
<a href="https://app.codacy.com/gh/mooxphp/moox-server/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade"><img src="https://app.codacy.com/project/badge/Grade/640e9fd6b75f43b8b0e23d4b3489cc09"/></a>
```

symlinks

```bash
mkdir ./public/wp

ln -s ../../vendor/roots/wordpress-no-content/wp-admin ./public/wp/wp-admin
ln -s ../../vendor/roots/wordpress-no-content/wp-includes ./public/wp/wp-includes
ln -s ../../vendor/roots/wordpress-no-content/index.php ./public/wp/index.php
ln -s ../../vendor/roots/wordpress-no-content/wp-activate.php ./public/wp/wp-activate.php
ln -s ../../vendor/roots/wordpress-no-content/wp-blog-header.php ./public/wp/wp-blog-header.php
ln -s ../../vendor/roots/wordpress-no-content/wp-comments-posts.php ./public/wp/wp-comments-posts.php
ln -s ../../vendor/roots/wordpress-no-content/wp-config-sample.php ./public/wp/wp-config-sample.php
ln -s ../../vendor/roots/wordpress-no-content/wp-comments-posts.php ./public/wp/wp-comments-posts.php
ln -s ../../vendor/roots/wordpress-no-content/wp-cron.php ./public/wp/wp-cron.php
ln -s ../../vendor/roots/wordpress-no-content/wp-links-opml.php ./public/wp/wp-links-opml.php
ln -s ../../vendor/roots/wordpress-no-content/wp-load.php ./public/wp/wp-load.php
ln -s ../../vendor/roots/wordpress-no-content/wp-login.php ./public/wp/wp-login.php
ln -s ../../vendor/roots/wordpress-no-content/wp-mail.php ./public/wp/wp-mail.php
ln -s ../../vendor/roots/wordpress-no-content/wp-settings.php ./public/wp/wp-settings.php
ln -s ../../vendor/roots/wordpress-no-content/wp-signup.php ./public/wp/wp-signup.php
ln -s ../../vendor/roots/wordpress-no-content/wp-trackback.php ./public/wp/wp-trackback.php
ln -s ../../vendor/roots/wordpress-no-content/xmlrpc.php ./public/wp/xmlrpc.php

```

wp-content/themes - child?
wp-content/languages

wp-content/plugins - custom?
wp-content/uploads

## Press Posts

Posts incl CPT and ACF (partly).

## Press Taxonomies

Categories, Tags and Custom Taxomomies.

## Press Users

To use WordPress' wp_users table for authentication through Laravel, Moox Press includes a WpUsers Model connected to all useful stuff than user profiles, user management, 2-factor authentication.

The Moox User Login plugin for WordPress on the other side, allows you to authenticate against WordPress. A seamless experience for your users.

## Moox WordPress

-   Zugriff aus Filament auf Posts (CPT filterbar), Categories, Tags
-   Kompletter Zugriff auf Custom Fields, ACF Repeater
-   WordPress als Composer Dependency
-   Auth Bridge, Laravel user in WP
-   Gutenberg Block Editor über Custom Actions
-   Frontend Ausgabe Component
