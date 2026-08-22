<?php
/**
 * Nettoyage des pages orphelines v6.17.6.
 */
if (!defined('ABSPATH')) exit;

$allowed_slugs = array(
    'deposer', 'deposer-en', 'deposer-ar',
    'mes-annonces', 'mes-annonces-en', 'mes-annonces-ar',
    'favoris', 'favoris-en', 'favoris-ar',
    'page-d-exemple', 'page-d-exemple-en', 'page-d-exemple-ar',
    'politique-de-confidentialite', 'politique-de-confidentialite-en', 'politique-de-confidentialite-ar'
);

$pages = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1));

foreach ($pages as $page) {
    if (!in_array($page->post_name, $allowed_slugs)) {
        echo "Suppression de la page: {$page->post_name} (ID: {$page->ID})\n";
        wp_delete_post($page->ID, true);
    }
}
