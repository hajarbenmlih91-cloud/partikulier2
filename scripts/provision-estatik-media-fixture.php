<?php
/** Run with: wp --path=wp eval-file scripts/provision-estatik-media-fixture.php */
$posts = array( 13, 14 );
$files = array( '/tmp/imgs/h1.jpg', '/tmp/imgs/h2.jpg', '/tmp/imgs/h3.jpg' );
$out = array();
foreach ( $posts as $post_id ) {
	$ids = array();
	foreach ( $files as $index => $file ) {
		if ( ! file_exists( $file ) ) {
			$out[] = array( 'post_id' => $post_id, 'file' => $file, 'status' => 'missing' );
			continue;
		}
		$attachment_id = media_handle_sideload(
			array(
				'name'     => basename( $file ),
				'tmp_name' => $file,
			),
			$post_id,
			'Fixture Estatik 6.15.0'
		);
		if ( is_wp_error( $attachment_id ) ) {
			$out[] = array( 'post_id' => $post_id, 'file' => $file, 'status' => 'error', 'message' => $attachment_id->get_error_message() );
			continue;
		}
		$ids[] = (int) $attachment_id;
		if ( 0 === $index ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}
	}
	if ( ! empty( $ids ) ) {
		update_post_meta( $post_id, 'es_property_gallery', wp_json_encode( $ids ) );
		update_post_meta( $post_id, '_pk_status', 'actif' );
	}
	$out[] = array( 'post_id' => $post_id, 'attachment_ids' => $ids, 'thumbnail' => (int) get_post_thumbnail_id( $post_id ), 'gallery' => get_post_meta( $post_id, 'es_property_gallery', true ), 'status' => empty( $ids ) ? 'FAIL' : 'PASS' );
}
echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
