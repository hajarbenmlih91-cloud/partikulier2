<?php
/**
 * Mesure senior du nombre de requêtes SQL avec optimisations.
 */
$_SERVER['HTTP_HOST'] = 'localhost:8092';
$_SERVER['REQUEST_URI'] = '/annonces/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

define('WP_USE_THEMES', true);
require_once '/home/ubuntu/wp-6170-clean/wp-load.php';

global $wpdb, $wp_query;

// Forcer la requête principale avec optimisations
$wp_query->query(array(
    'post_type' => 'properties',
    'posts_per_page' => 24,
    'update_post_meta_cache' => true,
    'update_post_term_cache' => true,
    'cache_results' => true
));

global $wpdb;
$before = count($wpdb->queries);

// Simuler le rendu du template
$template = get_stylesheet_directory() . '/templates/archive.php';

if (file_exists($template)) {
    ob_start();
    include($template);
    ob_end_clean();
}

$after = count($wpdb->queries);
$total = $after - $before;

echo "SQL_QUERIES_TOTAL: $total\n";
$queries_during_render = array_slice($wpdb->queries, $before);
foreach ($queries_during_render as $i => $q) {
    echo "QUERY [$i]: " . substr($q[0], 0, 150) . "\n";
}
