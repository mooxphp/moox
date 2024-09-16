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
        'APP_KEY' => getenv('APP_KEY'),
        'WP_SLUG' => getenv('WP_SLUG'),
        'WP_AUTH_KEY' => getenv('WP_AUTH_KEY'),
        'WP_SECURE_AUTH_KEY' => getenv('WP_SECURE_AUTH_KEY'),
        'WP_LOGGED_IN_KEY' => getenv('WP_LOGGED_IN_KEY'),
        'WP_NONCE_KEY' => getenv('WP_NONCE_KEY'),
        'WP_AUTH_SALT' => getenv('WP_AUTH_SALT'),
        'WP_SECURE_AUTH_SALT' => getenv('WP_SECURE_AUTH_SALT'),
        'WP_LOGGED_IN_SALT' => getenv('WP_LOGGED_IN_SALT'),
        'WP_NONCE_SALT' => getenv('WP_NONCE_SALT'),
        'TABLE_PREFIX' => getenv('WP_PREFIX'),
        'MOOX_HASH' => getenv('APP_KEY'),
        'ADMIN_SLUG' => getenv('ADMIN_SLUG'),
        'LOCK_WP' => getenv('LOCK_WP'),
        'AUTH_WP' => getenv('AUTH_WP'),
        'REDIRECT_AFTER_LOGIN' => getenv('REDIRECT_AFTER_LOGIN'),
        'REDIRECT_INDEX' => getenv('REDIRECT_INDEX'),
        'REDIRECT_TO_WP' => getenv('REDIRECT_TO_WP'),
        'REDIRECT_LOGIN' => getenv('REDIRECT_LOGIN'),
        'REDIRECT_LOGOUT' => getenv('REDIRECT_LOGOUT'),
        'FORGOT_PASSWORD' => getenv('FORGOT_PASSWORD'),
        'REDIRECT_EDITOR' => getenv('REDIRECT_EDITOR'),
        'REGISTRATION' => getenv('REGISTRATION'),
        'ENABLE_MFA' => getenv('ENABLE_MFA'),
        'WP_DEBUG' => getenv('WP_DEBUG'),
        'WP_DEBUG_LOG' => getenv('WP_DEBUG_LOG'),
        'WP_DEBUG_DISPLAY' => getenv('WP_DEBUG_DISPLAY'),
        'WP_MEMORY_LIMIT' => getenv('WP_MEMORY_LIMIT'),
    ];
} else {
    $env = [
        'DB_DATABASE' => $_ENV['DB_DATABASE'],
        'DB_USERNAME' => $_ENV['DB_USERNAME'],
        'DB_PASSWORD' => $_ENV['DB_PASSWORD'],
        'DB_HOST' => $_ENV['DB_HOST'],
        'APP_URL' => $_ENV['APP_URL'],
        'APP_KEY' => $_ENV['APP_KEY'],
        'WP_SLUG' => $_ENV['WP_SLUG'],
        'WP_AUTH_KEY' => $_ENV['WP_AUTH_KEY'],
        'WP_SECURE_AUTH_KEY' => $_ENV['WP_SECURE_AUTH_KEY'],
        'WP_LOGGED_IN_KEY' => $_ENV['WP_LOGGED_IN_KEY'],
        'WP_NONCE_KEY' => $_ENV['WP_NONCE_KEY'],
        'WP_AUTH_SALT' => $_ENV['WP_AUTH_SALT'],
        'WP_SECURE_AUTH_SALT' => $_ENV['WP_SECURE_AUTH_SALT'],
        'WP_LOGGED_IN_SALT' => $_ENV['WP_LOGGED_IN_SALT'],
        'WP_NONCE_SALT' => $_ENV['WP_NONCE_SALT'],
        'TABLE_PREFIX' => $_ENV['WP_PREFIX'],
        'MOOX_HASH' => $_ENV['APP_KEY'],
        'ADMIN_SLUG' => $_ENV['ADMIN_SLUG'],
        'LOCK_WP' => $_ENV['LOCK_WP'],
        'AUTH_WP' => $_ENV['AUTH_WP'],
        'REDIRECT_AFTER_LOGIN' => $_ENV['REDIRECT_AFTER_LOGIN'],
        'REDIRECT_INDEX' => $_ENV['REDIRECT_INDEX'],
        'REDIRECT_TO_WP' => $_ENV['REDIRECT_TO_WP'],
        'REDIRECT_LOGIN' => $_ENV['REDIRECT_LOGIN'],
        'REDIRECT_LOGOUT' => $_ENV['REDIRECT_LOGOUT'],
        'FORGOT_PASSWORD' => $_ENV['FORGOT_PASSWORD'],
        'REDIRECT_EDITOR' => $_ENV['REDIRECT_EDITOR'],
        'REGISTRATION' => $_ENV['REGISTRATION'],
        'ENABLE_MFA' => $_ENV['ENABLE_MFA'],
        'WP_DEBUG' => $_ENV['WP_DEBUG'],
        'WP_DEBUG_LOG' => $_ENV['WP_DEBUG_LOG'],
        'WP_DEBUG_DISPLAY' => $_ENV['WP_DEBUG_DISPLAY'],
        'WP_MEMORY_LIMIT' => $_ENV['WP_MEMORY_LIMIT'],
    ];
}

define('DB_NAME', $env['DB_DATABASE']);
define('DB_USER', $env['DB_USERNAME']);
define('DB_PASSWORD', $env['DB_PASSWORD']);
define('DB_HOST', $env['DB_HOST']);
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

$table_prefix = $env['TABLE_PREFIX'];

define('WP_SITEURL', $env['APP_URL'].$env['WP_SLUG']);
define('WP_HOME', $env['APP_URL'].$env['WP_SLUG']);
define('LARAVEL_KEY', $env['APP_KEY']);

define('AUTH_KEY', $env['WP_AUTH_KEY']);
define('SECURE_AUTH_KEY', $env['WP_SECURE_AUTH_KEY']);
define('LOGGED_IN_KEY', $env['WP_LOGGED_IN_KEY']);
define('NONCE_KEY', $env['WP_NONCE_KEY']);
define('AUTH_SALT', $env['WP_AUTH_SALT']);
define('SECURE_AUTH_SALT', $env['WP_SECURE_AUTH_SALT']);
define('LOGGED_IN_SALT', $env['WP_LOGGED_IN_SALT']);
define('NONCE_SALT', $env['WP_NONCE_SALT']);

define('MOOX_HASH', $env['MOOX_HASH']);
define('ADMIN_SLUG', $env['ADMIN_SLUG']);

define('LOCK_WP', isset($env['LOCK_WP']) ? $env['LOCK_WP'] : false);
define('AUTH_WP', isset($env['AUTH_WP']) ? $env['AUTH_WP'] : false);
define('REDIRECT_AFTER_LOGIN', isset($env['REDIRECT_AFTER_LOGIN']) ? $env['REDIRECT_AFTER_LOGIN'] : 'wp-admin');
define('REDIRECT_INDEX', isset($env['REDIRECT_INDEX']) ? $env['REDIRECT_INDEX'] : false);
define('REDIRECT_TO_WP', isset($env['REDIRECT_TO_WP']) ? $env['REDIRECT_TO_WP'] : false);
define('REDIRECT_LOGIN', isset($env['REDIRECT_LOGIN']) ? $env['REDIRECT_LOGIN'] : false);
define('REDIRECT_LOGOUT', isset($env['REDIRECT_LOGOUT']) ? $env['REDIRECT_LOGOUT'] : false);
define('FORGOT_PASSWORD', isset($env['FORGOT_PASSWORD']) ? $env['FORGOT_PASSWORD'] : false);
define('REDIRECT_EDITOR', isset($env['REDIRECT_EDITOR']) ? $env['REDIRECT_EDITOR'] : false);
define('REGISTRATION', isset($env['REGISTRATION']) ? $env['REGISTRATION'] : false);
define('ENABLE_MFA', isset($env['ENABLE_MFA']) ? $env['ENABLE_MFA'] : false);

define('WP_DEBUG', ($env['WP_DEBUG'] === 'true' ? true : false));
define('WP_DEBUG_LOG', ($env['WP_DEBUG_LOG'] === 'true' ? true : false));
define('WP_DEBUG_DISPLAY', ($env['WP_DEBUG_DISPLAY'] === 'true' ? true : false));

define('WP_MEMORY_LIMIT', ($env['WP_MEMORY_LIMIT'] ? $env['WP_MEMORY_LIMIT'] : '512M'));

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__.'/');
}

require_once ABSPATH.'wp-settings.php';
