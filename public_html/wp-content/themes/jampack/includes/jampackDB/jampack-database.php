<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Database Connection for Jampack Theme
 *
 * This file initializes a custom database connection for the Jampack theme.
 * It uses the wpdb class to connect to a custom database defined in the configuration.
 */
function init_jampack_db_connection() {
    global $jampack_db;

    if (!isset($jampack_db)) {
        $jampack_db = new wpdb(JPCK_DB_USER, JPCK_DB_PASSWORD, JPCK_DB_NAME, JPCK_DB_HOST);
    }
}

add_action('init', 'init_jampack_db_connection');
