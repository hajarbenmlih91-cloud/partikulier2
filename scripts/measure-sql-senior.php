<?php
/**
 * Mesure SQL Senior - Calcul précis des requêtes sur l'archive.
 */
$wp_dir = getenv('PK_WP_DIR');
require_once $wp_dir . '/wp-load.php';

// Activer SAVEQUERIES pour le comptage
if (!defined('SAVEQUERIES')) define('SAVEQUERIES', true);

global $wpdb;
$initial_count = count($wpdb->queries ?? array());

// Simuler le chargement de l'archive
query_posts(array('post_type' => 'properties', 'posts_per_page' => 10));

if (have_posts()) {
    while (have_posts()) {
        the_post();
        // Simuler le rendu de la carte (qui peut déclencher des requêtes Estatik)
        get_post_meta(get_the_ID());
    }
}

$final_count = count($wpdb->queries ?? array());
$total = $final_count - $initial_count;

echo "SQL_QUERIES_TOTAL: $total\n";
