<?php
/** Run with: wp --path=wp eval-file scripts/complete-estatik-media-fixture.php */
$source_post = 13;
$target_post = 14;
$ids = get_posts( array( 'post_type' => 'attachment', 'post_parent' => $source_post, 'post_status' => 'inherit', 'numberposts' => 3, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
$ids = array_map( 'intval', $ids );
if ( count( $ids ) < 3 ) {
	echo wp_json_encode( array( 'status' => 'FAIL', 'reason' => 'source_media_missing', 'ids' => $ids ) ) . PHP_EOL;
	exit( 1 );
}
set_post_thumbnail( $target_post, $ids[0] );
update_post_meta( $target_post, 'es_property_gallery', wp_json_encode( $ids ) );
update_post_meta( $target_post, '_pk_status', 'actif' );
echo wp_json_encode( array( 'status' => 'PASS', 'post_id' => $target_post, 'attachment_ids' => $ids, 'thumbnail' => (int) get_post_thumbnail_id( $target_post ), 'gallery' => get_post_meta( $target_post, 'es_property_gallery', true ) ), JSON_PRETTY_PRINT ) . PHP_EOL;
