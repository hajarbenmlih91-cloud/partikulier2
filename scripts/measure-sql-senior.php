<?php
define('SAVEQUERIES', true);
$root = dirname(__DIR__);
$wp_load = file_exists($root . '/wp/wp-load.php') ? $root . '/wp/wp-load.php' : $root . '/wp-load.php';
require_once $wp_load;

global $wpdb;

// Simulation du rendu de l'archive
ob_start();
$archive_template = get_template_directory() . '/templates/archive.php';
if (file_exists($archive_template)) {
    include $archive_template;
}
ob_end_clean();

$count = count($wpdb->queries);
echo "SQL_QUERIES_TOTAL: " . $count . "\n";
