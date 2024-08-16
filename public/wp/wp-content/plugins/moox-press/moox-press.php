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

function moox_auth_token()
{
    global $authWp;

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

                    wp_redirect(admin_url());
                    exit;
                }
            }
        }
    }
}
add_action('init', 'moox_auth_token');

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
