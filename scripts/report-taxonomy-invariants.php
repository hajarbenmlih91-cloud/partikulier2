<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ids = get_posts( array( 'post_type' => 'properties', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
$missing_action = array();
$missing_location = array();
foreach ( $ids as $id ) {
    if ( ! get_the_terms( $id, 'es_category' ) ) { $missing_action[] = (int) $id; }
    if ( ! get_the_terms( $id, 'es_location' ) ) { $missing_location[] = (int) $id; }
}
$categories = get_terms( array( 'taxonomy' => 'es_category', 'hide_empty' => false, 'fields' => 'names' ) );
echo wp_json_encode( array( 'published_properties' => count( $ids ), 'missing_action_ids' => $missing_action, 'missing_location_ids' => $missing_location, 'es_category_terms' => is_wp_error( $categories ) ? array() : $categories, 'status' => empty( $missing_action ) && empty( $missing_location ) ? 'PASS' : 'FAIL' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
