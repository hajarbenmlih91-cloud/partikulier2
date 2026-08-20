<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists( 'pll_languages_list' ) || ! isset( $GLOBALS['polylang']->model ) ) { fwrite( STDERR, "Polylang model unavailable\n" ); exit( 1 ); }
$language_model = $GLOBALS['polylang']->model;
if ( ! method_exists( $language_model, 'add_language' ) && class_exists( 'PLL_Admin_Model' ) ) {
    $polylang_options = get_option( 'polylang', array() );
    $language_model  = new PLL_Admin_Model( $polylang_options );
    update_option( 'polylang', $polylang_options );
}
if ( ! method_exists( $language_model, 'add_language' ) ) { fwrite( STDERR, "Polylang admin language model unavailable\n" ); exit( 1 ); }
$languages = array(
    array( 'locale' => 'fr_FR', 'name' => 'Français', 'slug' => 'fr', 'flag' => 'fr', 'term_group' => 0, 'rtl' => false ),
    array( 'locale' => 'en_US', 'name' => 'English', 'slug' => 'en', 'flag' => 'us', 'term_group' => 1, 'rtl' => false ),
    array( 'locale' => 'ar', 'name' => 'العربية', 'slug' => 'ar', 'flag' => 'ma', 'term_group' => 2, 'rtl' => true ),
);
$ids = array();
foreach ( $languages as $args ) {
    $existing = pll_languages_list( array( 'fields' => 'slug' ) );
    if ( ! in_array( $args['slug'], $existing, true ) ) {
        $result = $language_model->add_language( $args );
        if ( is_wp_error( $result ) ) { fwrite( STDERR, $result->get_error_message() . "\n" ); exit( 1 ); }
    }
    $lang = get_term_by( 'slug', $args['slug'], 'language' );
    $ids[ $args['slug'] ] = $lang ? (int) $lang->term_id : 0;
}
$option = get_option( 'polylang', array() );
$option['default_lang'] = 'fr';
$option['hide_default'] = true;
$option['force_lang'] = 1;
$option['rewrite'] = true;
$option['post_types'] = array_values( array_unique( array_merge( (array) ( $option['post_types'] ?? array() ), array( 'properties', 'page' ) ) ) );
$option['taxonomies'] = array_values( array_unique( array_merge( (array) ( $option['taxonomies'] ?? array() ), array( 'es_type', 'es_category', 'es_location' ) ) ) );
update_option( 'polylang', $option );
// Normalize every existing published listing to FR before querying language-filtered sources.
$all_listings = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
foreach ( $all_listings as $listing ) {
    if ( ! pll_get_post_language( $listing->ID ) ) {
        pll_set_post_language( $listing->ID, 'fr' );
    }
}
// Create one linked FR/EN/AR family for each published Estatik listing.
$listings = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'lang' => 'fr', 'orderby' => 'ID', 'order' => 'ASC' ) );
foreach ( $listings as $source ) {
    pll_set_post_language( $source->ID, 'fr' );
    $translations = array( 'fr' => $source->ID );
    foreach ( array( 'en', 'ar' ) as $slug ) {
        $existing_translation = pll_get_post_translations( $source->ID );
        if ( ! empty( $existing_translation[ $slug ] ) ) { $translations[ $slug ] = (int) $existing_translation[ $slug ]; continue; }
        $title = 'en' === $slug ? 'Translated listing: ' . $source->post_title : 'إعلان مترجم: ' . $source->post_title;
        $new_id = wp_insert_post( array( 'post_type' => 'properties', 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $source->post_content, 'post_excerpt' => $source->post_excerpt, 'post_author' => $source->post_author ), true );
        if ( is_wp_error( $new_id ) ) { fwrite( STDERR, $new_id->get_error_message() . "\n" ); exit( 1 ); }
        pll_set_post_language( $new_id, $slug );
        foreach ( get_post_meta( $source->ID ) as $key => $values ) { foreach ( $values as $value ) { add_post_meta( $new_id, $key, maybe_unserialize( $value ) ); } }
        foreach ( array( 'es_type', 'es_category', 'es_location' ) as $taxonomy ) {
            $term_ids = wp_get_object_terms( $source->ID, $taxonomy, array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $term_ids ) && ! empty( $term_ids ) ) { wp_set_object_terms( $new_id, $term_ids, $taxonomy, false ); }
        }
        $translations[ $slug ] = $new_id;
    }
    pll_save_post_translations( $translations );
}
flush_rewrite_rules( false );
require_once __DIR__ . '/provision-polylang-taxonomies.php';
echo wp_json_encode( array( 'languages' => pll_languages_list( array( 'fields' => 'all' ) ), 'post_types' => $option['post_types'], 'taxonomies' => $option['taxonomies'], 'published_properties' => count( $listings ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
