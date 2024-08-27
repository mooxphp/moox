<?php

use Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__.'/../../');
$dotenv->load();

$isCli = (php_sapi_name() == 'cli');

if ($isCli) {
    $env = [
        'DB_DATABASE' => getenv('DB_DATABASE'),
        'DB_USERNAME' => getenv('DB_USERNAME'),
        'DB_PASSWORD' => getenv('DB_PASSWORD'),
        'DB_HOST' => getenv('DB_HOST'),
        'APP_URL' => getenv('APP_URL'),
        'WP_SLUG' => getenv('WP_SLUG'),
        'WP_AUTH_KEY' => getenv('WP_AUTH_KEY'),
        'WP_SECURE_AUTH_KEY' => getenv('WP_SECURE_AUTH_KEY'),
        'WP_LOGGED_IN_KEY' => getenv('WP_LOGGED_IN_KEY'),
        'WP_NONCE_KEY' => getenv('WP_NONCE_KEY'),
        'WP_AUTH_SALT' => getenv('WP_AUTH_SALT'),
        'WP_SECURE_AUTH_SALT' => getenv('WP_SECURE_AUTH_SALT'),
        'WP_LOGGED_IN_SALT' => getenv('WP_LOGGED_IN_SALT'),
        'WP_NONCE_SALT' => getenv('WP_NONCE_SALT'),
    ];
} else {
    $env = [
        'DB_DATABASE' => $_ENV['DB_DATABASE'],
        'DB_USERNAME' => $_ENV['DB_USERNAME'],
        'DB_PASSWORD' => $_ENV['DB_PASSWORD'],
        'DB_HOST' => $_ENV['DB_HOST'],
        'APP_URL' => $_ENV['APP_URL'],
        'WP_SLUG' => $_ENV['WP_SLUG'],
        'WP_AUTH_KEY' => $_ENV['WP_AUTH_KEY'],
        'WP_SECURE_AUTH_KEY' => $_ENV['WP_SECURE_AUTH_KEY'],
        'WP_LOGGED_IN_KEY' => $_ENV['WP_LOGGED_IN_KEY'],
        'WP_NONCE_KEY' => $_ENV['WP_NONCE_KEY'],
        'WP_AUTH_SALT' => $_ENV['WP_AUTH_SALT'],
        'WP_SECURE_AUTH_SALT' => $_ENV['WP_SECURE_AUTH_SALT'],
        'WP_LOGGED_IN_SALT' => $_ENV['WP_LOGGED_IN_SALT'],
        'WP_NONCE_SALT' => $_ENV['WP_NONCE_SALT'],
    ];
}

define('DB_NAME', $env['DB_DATABASE']);
define('DB_USER', $env['DB_USERNAME']);
define('DB_PASSWORD', $env['DB_PASSWORD']);
define('DB_HOST', $env['DB_HOST']);
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

define('WP_SITEURL', $env['APP_URL'].$env['WP_SLUG']);
define('WP_HOME', $env['APP_URL'].$env['WP_SLUG']);

define('AUTH_KEY', $env['WP_AUTH_KEY']);
define('SECURE_AUTH_KEY', $env['WP_SECURE_AUTH_KEY']);
define('LOGGED_IN_KEY', $env['WP_LOGGED_IN_KEY']);
define('NONCE_KEY', $env['WP_NONCE_KEY']);
define('AUTH_SALT', $env['WP_AUTH_SALT']);
define('SECURE_AUTH_SALT', $env['WP_SECURE_AUTH_SALT']);
define('LOGGED_IN_SALT', $env['WP_LOGGED_IN_SALT']);
define('NONCE_SALT', $env['WP_NONCE_SALT']);

define('MOOX_HASH', $_ENV['APP_KEY']);
define('ADMIN_SLUG', isset($_ENV['ADMIN_SLUG']) ? $_ENV['ADMIN_SLUG'] : '/admin');

define('LOCK_WP', isset($_ENV['LOCK_WP']) ? $_ENV['LOCK_WP'] : false);
define('AUTH_WP', isset($_ENV['AUTH_WP']) ? $_ENV['AUTH_WP'] : false);
define('REDIRECT_INDEX', isset($_ENV['REDIRECT_INDEX']) ? $_ENV['REDIRECT_INDEX'] : false);
define('REDIRECT_TO_WP', isset($_ENV['REDIRECT_TO_WP']) ? $_ENV['REDIRECT_TO_WP'] : false);
define('REDIRECT_LOGIN', isset($_ENV['REDIRECT_LOGIN']) ? $_ENV['REDIRECT_LOGIN'] : false);
define('REDIRECT_LOGOUT', isset($_ENV['REDIRECT_LOGOUT']) ? $_ENV['REDIRECT_LOGOUT'] : false);
define('FORGOT_PASSWORD', isset($_ENV['FORGOT_PASSWORD']) ? $_ENV['FORGOT_PASSWORD'] : false);
define('REDIRECT_EDITOR', isset($_ENV['REDIRECT_EDITOR']) ? $_ENV['REDIRECT_EDITOR'] : false);
define('REGISTRATION', isset($_ENV['REGISTRATION']) ? $_ENV['REGISTRATION'] : false);
define('ENABLE_MFA', isset($_ENV['ENABLE_MFA']) ? $_ENV['ENABLE_MFA'] : false);

$table_prefix = isset($_ENV['WP_PREFIX']) ? $_ENV['WP_PREFIX'] : 'wp_';

define('WP_DEBUG', ($_ENV['WP_DEBUG'] === 'true' ? true : false));
define('WP_DEBUG_LOG', ($_ENV['WP_DEBUG_LOG'] === 'true' ? true : false));
define('WP_DEBUG_DISPLAY', ($_ENV['WP_DEBUG_DISPLAY'] === 'true' ? true : false));

define('WP_MEMORY_LIMIT', ($_ENV['WP_MEMORY_LIMIT'] ? $_ENV['WP_MEMORY_LIMIT'] : '512M'));

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__.'/');
}

require_once ABSPATH.'wp-settings.php';
