<?php
/**
 * Build User Administration Menu.
 *
 * @since 3.1.0
 */
$menu[2] = [__('Dashboard'), 'exist', 'index.php', '', 'menu-top menu-top-first menu-icon-dashboard', 'menu-dashboard', 'dashicons-dashboard'];

$menu[4] = ['', 'exist', 'separator1', '', 'wp-menu-separator'];

$menu[70] = [__('Profile'), 'exist', 'profile.php', '', 'menu-top menu-icon-users', 'menu-users', 'dashicons-admin-users'];

$menu[99] = ['', 'exist', 'separator-last', '', 'wp-menu-separator'];

$_wp_real_parent_file['users.php'] = 'profile.php';
$compat = [];
$submenu = [];

require_once ABSPATH.'wp-admin/includes/menu.php';
