<?php
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'pll_set_term_language' ) ) { exit; }
$taxonomies = array( 'es_type', 'es_category', 'es_location' );
$term_map = array();
foreach ( $taxonomies as $taxonomy ) {
    $fr_terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
    if ( is_wp_error( $fr_terms ) ) { continue; }
    foreach ( $fr_terms as $fr_term ) {
        if ( ! pll_get_term_language( $fr_term->term_id ) ) { pll_set_term_language( $fr_term->term_id, 'fr' ); }
        if ( 'fr' !== pll_get_term_language( $fr_term->term_id ) ) { continue; }
        $translations = pll_get_term_translations( $fr_term->term_id );
        $translations['fr'] = (int) $fr_term->term_id;
        foreach ( array( 'en', 'ar' ) as $lang ) {
            if ( empty( $translations[ $lang ] ) ) {
                $name = $fr_term->name;
                if ( 'ar' === $lang ) { $name = 'ترجمة ' . $name; }
                $created = wp_insert_term( $name, $taxonomy, array( 'slug' => sanitize_title( $fr_term->slug . '-' . $lang ) ) );
                if ( is_wp_error( $created ) && 'term_exists' === $created->get_error_code() ) { $created = array( 'term_id' => $created->get_error_data() ); }
                if ( is_wp_error( $created ) ) { continue; }
                $term_id = (int) $created['term_id'];
                pll_set_term_language( $term_id, $lang );
                $translations[ $lang ] = $term_id;
            }
        }
        pll_save_term_translations( $translations );
        $term_map[ $taxonomy ][ $fr_term->term_id ] = $translations;
    }
}
$assigned = 0;
$sources = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'lang' => 'fr', 'fields' => 'ids' ) );
foreach ( $sources as $source_id ) {
    $translations = pll_get_post_translations( $source_id );
    foreach ( array( 'en', 'ar' ) as $lang ) {
        if ( empty( $translations[ $lang ] ) ) { continue; }
        foreach ( $taxonomies as $taxonomy ) {
            $source_terms = wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) );
            $target_terms = array();
            foreach ( $source_terms as $source_term_id ) {
                if ( ! empty( $term_map[ $taxonomy ][ $source_term_id ][ $lang ] ) ) { $target_terms[] = (int) $term_map[ $taxonomy ][ $source_term_id ][ $lang ]; }
            }
            if ( $target_terms ) { wp_set_object_terms( $translations[ $lang ], $target_terms, $taxonomy, false ); $assigned += count( $target_terms ); }
        }
    }
}
echo wp_json_encode( array( 'taxonomies' => $taxonomies, 'source_families' => count( $sources ), 'term_translation_groups' => count( $term_map ), 'assignments' => $assigned ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
