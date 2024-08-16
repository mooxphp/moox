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
