<?php
/**
 * Mesure SQL Senior v6.17.5.
 */
$wp_dir = getenv('PK_WP_DIR');
define('SAVEQUERIES', true);
require_once $wp_dir . '/wp-load.php';

global $wpdb;
$wpdb->queries = array();

// Simuler le rendu de l'archive via le template réel
ob_start();
include $wp_dir . '/wp-content/themes/partikulier/templates/archive.php';
ob_end_clean();

$total = count($wpdb->queries);
echo "SQL_QUERIES_TOTAL: $total\n";
