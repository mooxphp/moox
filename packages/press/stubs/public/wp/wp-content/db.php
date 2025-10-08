<?php

declare(strict_types=1);

require_once __DIR__.'/../../../vendor/moox/press/src/WPDBProxy.php';

global $wpdb;
$wpdb = new Moox\Press\WPDBProxy(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
