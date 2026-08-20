<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$out = array(
    'slugs' => function_exists( 'pll_languages_list' ) ? pll_languages_list( array( 'fields' => 'slug' ) ) : array(),
    'locales' => function_exists( 'pll_languages_list' ) ? pll_languages_list( array( 'fields' => 'locale' ) ) : array(),
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
