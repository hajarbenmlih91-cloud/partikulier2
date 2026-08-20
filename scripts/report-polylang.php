<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$language_objects = function_exists( 'pll_languages_list' ) && isset( $GLOBALS['polylang']->model->languages ) ? $GLOBALS['polylang']->model->languages->get_list() : array();
$language_slugs = array();
$language_locales = array();
foreach ( (array) $language_objects as $language ) {
    $language_slugs[] = (string) $language->slug;
    $language_locales[] = (string) $language->locale;
}
$out = array(
    'slugs' => $language_slugs,
    'locales' => $language_locales,
    'counts' => array(),
    'families' => array(),
);
foreach ( array( 'fr', 'en', 'ar' ) as $lang ) {
    $out['counts'][ $lang ] = count( get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'lang' => $lang, 'fields' => 'ids' ) ) );
}
foreach ( get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'lang' => 'fr', 'fields' => 'ids' ) ) as $id ) {
    $out['families'][ $id ] = pll_get_post_translations( $id );
}
echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
