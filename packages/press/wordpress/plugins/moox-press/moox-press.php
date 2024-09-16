<?php
/*
Plugin Name: Moox Press
Description: Plugin for integrating WordPress with Laravel
Author: Moox
Version: 0.1
*/

if (defined('ADMIN_SLUG')) {
    $adminSlug = ADMIN_SLUG;
}

if (defined('MOOX_HASH')) {
    $mooxHash = MOOX_HASH;
}

if (defined('LOCK_WP')) {
    $lockWp = LOCK_WP;
}

if (defined('AUTH_WP')) {
    $authWp = AUTH_WP;
}

if (defined('REDIRECT_EDITOR')) {
    $redirectEditor = REDIRECT_EDITOR;
}

if (defined('REDIRECT_LOGIN')) {
    $redirectLogin = REDIRECT_LOGIN;
}

if (defined('REDIRECT_LOGOUT')) {
    $redirectLogout = REDIRECT_LOGOUT;
}

if (defined('REDIRECT_AFTER_LOGIN')) {
    $redirectAfterLogin = REDIRECT_AFTER_LOGIN;
}

function moox_lock_wp_frontend()
{
    global $lockWp;

    if ($lockWp === 'true') {
        if (! is_user_logged_in() && $GLOBALS['pagenow'] !== 'wp-login.php') {
            auth_redirect();
        }
    }
}
add_action('template_redirect', 'moox_lock_wp_frontend');

/*
function moox_auth_token()
{
    global $authWp;
    global $adminSlug;
    global $redirectAfterLogin;

    if ($authWp === 'true') {
        if (isset($_GET['auth_token'])) {
            $token = $_GET['auth_token'];
            $parts = explode('.', $token);

            if (count($parts) === 2) {
                $payload = $parts[0];
                $signature = $parts[1];
                $expected_signature = hash_hmac('sha256', $payload, MOOX_HASH);

                if (hash_equals($expected_signature, $signature)) {
                    $user_id = base64_decode($payload);

                    wp_clear_auth_cookie();
                    wp_set_auth_cookie($user_id);

                    $redirectTarget = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : $redirectAfterLogin;

                    if ($redirectTarget === 'frontend') {
                        wp_redirect(home_url());
                    } elseif ($redirectTarget === 'wpadmin') {
                        wp_redirect(admin_url());
                    } else {
                        wp_redirect('https://'.$_SERVER['SERVER_NAME'].$adminSlug);
                    }
                    exit;
                }
            }
        }
    }
}
add_action('init', 'moox_auth_token');
*/

function moox_redirect_logout()
{
    global $redirectLogout;

    if ($redirectLogout === 'true') {
        $url = strtok($_SERVER['REQUEST_URI'], '?');

        if (str_ends_with($url, 'wp-login.php') && isset($_GET['action']) && $_GET['action'] === 'logout') {
            wp_logout();
            wp_redirect('https://'.$_SERVER['SERVER_NAME'].'/moox/logout');
            exit;
        }
    }
}
add_action('init', 'moox_redirect_logout');

/* Uses the Moox (Laravel) login page */

function moox_redirect_login()
{
    global $redirectLogin;
    global $adminSlug;

    if ($redirectLogin === 'true') {
        $url = strtok($_SERVER['REQUEST_URI'], '?');

        if (str_ends_with($url, 'wp-login.php')) {
            wp_redirect('https://'.$_SERVER['SERVER_NAME'].$adminSlug.'/login');
            exit;
        }
    }
}
add_action('init', 'moox_redirect_login');

function enqueue_moox_admin_script()
{
    global $redirectEditor;

    if ($redirectEditor === 'true') {
        wp_enqueue_script(
            'moox-admin-js',
            plugin_dir_url(__FILE__).'js/moox-admin.js',
            ['wp-element', 'wp-components', 'wp-edit-post'],
            filemtime(plugin_dir_path(__FILE__).'js/moox-admin.js'),
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'enqueue_moox_admin_script');

function decrypt_laravel_data($encryptedData)
{
    $key = defined('LARAVEL_KEY') ? LARAVEL_KEY : null;
    if (! $key) {
        error_log('Laravel encryption key not defined');

        return null;
    }

    // Decode the base64 encoded key
    $key = base64_decode(substr($key, 7));
    error_log('Decoded key length: '.strlen($key));

    $cipher = 'AES-256-CBC';
    $ivLength = openssl_cipher_iv_length($cipher);

    $data = base64_decode($encryptedData);
    error_log('Base64 decoded data length: '.strlen($data));

    $iv = substr($data, 0, $ivLength);
    $payload = substr($data, $ivLength);

    error_log('IV length: '.strlen($iv).', Payload length: '.strlen($payload));

    // Add OPENSSL_RAW_DATA flag
    $decrypted = openssl_decrypt($payload, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    if ($decrypted === false) {
        error_log('Decryption failed: '.openssl_error_string());
    } else {
        error_log('Decryption successful. Decrypted data length: '.strlen($decrypted));
    }

    return $decrypted !== false ? $decrypted : null;
}

function moox_check_laravel_db_session()
{
    static $already_checked = false;
    if ($already_checked || did_action('moox_session_checked')) {
        return;
    }
    $already_checked = true;
    do_action('moox_session_checked');

    error_log('Checking laravel db session');

    if (isset($_COOKIE['moox_session'])) {
        $encryptedSession = $_COOKIE['moox_session'];
        $sessionData = json_decode(base64_decode($encryptedSession), true);

        error_log('Found moox_session: '.$encryptedSession);

        if ($sessionData && isset($sessionData['value'])) {
            error_log('Attempting to decrypt session data');

            $decryptedValue = decrypt_laravel_data($sessionData['value']);

            if ($decryptedValue) {
                error_log('Successfully decrypted session data');
                error_log('Decrypted data: '.bin2hex($decryptedValue));

                // Split the decrypted data by the pipe character
                $parts = explode('|', $decryptedValue);
                if (count($parts) == 2) {
                    $sessionId = $parts[1]; // Use the second part as the session ID
                    error_log('Session ID: '.$sessionId);

                    // Here, we need to query the Laravel database to get the user ID associated with this session ID
                    $userId = get_user_id_from_laravel_session($sessionId);

                    if ($userId) {
                        error_log('Found session with user-id: '.$userId);

                        if (! is_user_logged_in()) {
                            wp_clear_auth_cookie();
                            wp_set_auth_cookie($userId, true);
                            error_log('Set WordPress auth cookie for user ID: '.$userId);
                        } else {
                            error_log('User already logged in to WordPress');
                        }
                    }
                } else {
                    error_log('Unexpected data format in decrypted session');
                }
            } else {
                error_log('Failed to decrypt session data');
            }
        } else {
            error_log('Invalid session data structure: '.print_r($sessionData, true));
        }
    } else {
        error_log('No moox_session cookie found');
    }
}

function get_user_id_from_laravel_session($sessionId)
{
    error_log('Attempting to get user ID for session: '.$sessionId);

    // Use WordPress database credentials for Laravel connection
    $host = DB_HOST;
    $db = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASSWORD;
    $charset = DB_CHARSET;

    error_log("Attempting to connect to Laravel database: host=$host, db=$db, user=$user");

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        error_log('Successfully connected to the Laravel database');

        // Query to get user ID from session ID
        $stmt = $pdo->prepare('SELECT payload FROM sessions WHERE id = :session_id');
        $stmt->execute(['session_id' => $sessionId]);
        $result = $stmt->fetch();

        if ($result) {
            $payload = unserialize(base64_decode($result['payload']));
            error_log('Session payload: '.print_r($payload, true));

            // Look for a key that starts with 'login_press_'
            $loginPressKey = null;
            foreach ($payload as $key => $value) {
                if (strpos($key, 'login_press_') === 0) {
                    $loginPressKey = $key;
                    break;
                }
            }

            if ($loginPressKey !== null) {
                $userId = $payload[$loginPressKey];
                error_log('Found user ID: '.$userId);

                return $userId;
            } else {
                error_log('User ID not found in session payload');

                return null;
            }
        } else {
            error_log('No session found for session ID: '.$sessionId);

            return null;
        }
    } catch (\PDOException $e) {
        error_log('Database connection error: '.$e->getMessage());
        error_log('DSN: '.$dsn);

        return null;
    }
}

// Ensure this function is only called once
remove_action('init', 'moox_check_laravel_db_session');
add_action('init', 'moox_check_laravel_db_session', 1);
