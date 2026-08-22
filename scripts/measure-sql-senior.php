<?php
/**
 * Mesure SQL Senior v6.17.6 avec SAVEQUERIES précoce.
 */
define('SAVEQUERIES', true);
$wp_dir = getenv('PK_WP_DIR') ?: '/home/ubuntu/wp-6172-final';
require_once $wp_dir . '/wp-load.php';

global $wpdb;

// Simulation du chargement de l'archive
ob_start();
include $wp_dir . '/wp-content/themes/partikulier/templates/archive.php';
ob_end_clean();

echo "SQL_QUERIES_TOTAL: " . count($wpdb->queries) . "\n";
