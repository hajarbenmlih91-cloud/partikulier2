<?php
define('SAVEQUERIES', true);
require_once '/home/ubuntu/wp-6172-final/wp-load.php';

global $wpdb;

// Simulation du rendu de l'archive
ob_start();
include '/home/ubuntu/wp-6172-final/wp-content/themes/partikulier/templates/archive.php';
ob_end_clean();

$count = count($wpdb->queries);
echo "SQL_QUERIES_TOTAL: " . $count . "\n";
