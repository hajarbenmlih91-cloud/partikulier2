<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$taxonomies = array( 'es_category', 'es_location' );
$families = 0; $assignments = 0;
$sources = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'lang' => 'fr', 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
foreach ( $sources as $source_id ) {
    $translations = pll_get_post_translations( $source_id );
    if ( empty( $translations['en'] ) || empty( $translations['ar'] ) ) { continue; }
    $families++;
    foreach ( $taxonomies as $taxonomy ) {
        $source_terms = wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids', 'lang' => false, 'suppress_filter' => true ) );
        if ( is_wp_error( $source_terms ) || empty( $source_terms ) ) { continue; }
        foreach ( array( 'en', 'ar' ) as $lang ) {
            wp_set_object_terms( (int) $translations[ $lang ], array_map( 'intval', $source_terms ), $taxonomy, false );
            $assignments++;
        }
    }
}
echo wp_json_encode( array( 'families' => $families, 'assignments' => $assignments ), JSON_PRETTY_PRINT ) . PHP_EOL;
