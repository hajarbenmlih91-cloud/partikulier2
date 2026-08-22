<?php
/**
 * Nettoie les slugs et titres des annonces traduites (Version Agressive).
 */
require_once '/home/ubuntu/wp-6170-clean/wp-load.php';

$posts = get_posts(array(
    'post_type' => 'properties',
    'posts_per_page' => -1,
    'post_status' => 'any'
));

foreach ($posts as $post) {
    $new_title = $post->post_title;
    $new_slug = $post->post_name;

    // Arabe
    $new_title = str_replace('إعلان مترجم: ', '', $new_title);
    $decoded_slug = rawurldecode($new_slug);
    if (0 === strpos($decoded_slug, 'إعلان-مترجم-')) {
        $new_slug = substr($decoded_slug, strlen('إعلان-مترجم-'));
    }

    // Anglais
    $new_title = str_ireplace('Translated listing: ', '', $new_title);
    if (0 === strpos($new_slug, 'translated-listing-')) {
        $new_slug = substr($new_slug, strlen('translated-listing-'));
    }

    if ($new_title !== $post->post_title || $new_slug !== $post->post_name) {
        wp_update_post(array(
            'ID' => $post->ID,
            'post_title' => $new_title,
            'post_name' => $new_slug
        ));
        echo "Updated ID {$post->ID}: {$new_title} ({$new_slug})\n";
    }
}
