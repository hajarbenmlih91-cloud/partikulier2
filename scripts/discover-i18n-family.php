<?php
$family = null;
$posts = get_posts( array(
    'post_type'      => 'properties',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'ID',
    'order'          => 'ASC',
) );
foreach ( $posts as $post ) {
    if ( ! function_exists( 'pll_get_post_translations' ) ) {
        break;
    }
    $translations = pll_get_post_translations( $post->ID );
    if ( empty( $translations['fr'] ) || empty( $translations['en'] ) || empty( $translations['ar'] ) ) {
        continue;
    }
    $ids = array(
        'fr' => (int) $translations['fr'],
        'en' => (int) $translations['en'],
        'ar' => (int) $translations['ar'],
    );
    if ( 'publish' !== get_post_status( $ids['fr'] ) || 'publish' !== get_post_status( $ids['en'] ) || 'publish' !== get_post_status( $ids['ar'] ) ) {
        continue;
    }
    $urls = array();
    foreach ( $ids as $lang => $id ) {
        $urls[ $lang ] = wp_make_link_relative( get_permalink( $id ) );
    }
    $family = array( 'ids' => $ids, 'urls' => $urls );
    break;
}
echo wp_json_encode( $family, JSON_UNESCAPED_UNICODE );
