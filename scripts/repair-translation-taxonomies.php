<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$taxonomies = array( 'es_category', 'es_location', 'es_type' );
$updated = 0;
$missing_before = 0;
$posts = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'lang' => 'fr', 'fields' => 'ids' ) );
foreach ( $posts as $source_id ) {
    $translations = pll_get_post_translations( $source_id );
    foreach ( array( 'en', 'ar' ) as $lang ) {
        if ( empty( $translations[ $lang ] ) ) { continue; }
        $target_id = (int) $translations[ $lang ];
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) );
            if ( is_wp_error( $terms ) ) { continue; }
            if ( empty( wp_get_object_terms( $target_id, $taxonomy, array( 'fields' => 'ids' ) ) ) && ! empty( $terms ) ) { $missing_before++; }
            if ( ! empty( $terms ) ) { wp_set_object_terms( $target_id, $terms, $taxonomy, false ); }
        }
        $updated++;
    }
}
echo wp_json_encode( array( 'source_families' => count( $posts ), 'translations_updated' => $updated, 'missing_taxonomy_assignments_repaired' => $missing_before ), JSON_PRETTY_PRINT ) . PHP_EOL;
