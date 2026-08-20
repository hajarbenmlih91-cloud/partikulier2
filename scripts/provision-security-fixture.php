<?php
/** Fixture locale du test scripts/securite.mjs. Non destinée à la production. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$users = array();
foreach ( array( 'marc' => 'marc123', 'sofia' => 'sofia123' ) as $login => $password ) {
	$user = get_user_by( 'login', $login );
	if ( $user ) {
		$user_id = (int) $user->ID;
		wp_set_password( $password, $user_id );
	} else {
		$user_id = wp_create_user( $login, $password, $login . '@example.test' );
	}
	if ( is_wp_error( $user_id ) ) { fwrite( STDERR, $user_id->get_error_message() . "\n" ); exit( 1 ); }
	$u = new WP_User( $user_id );
	$u->set_role( 'author' );
	$users[ $login ] = (int) $user_id;
}
$posts = array();
foreach ( array( 'marc' => 'SECURITY MARC LISTING', 'sofia' => 'SECURITY SOFIA LISTING' ) as $login => $title ) {
	$old = get_posts( array( 'post_type' => PARTIKULIER_ESTATIK_POST_TYPE, 'post_status' => 'any', 'author' => $users[ $login ], 's' => $title, 'posts_per_page' => 1, 'fields' => 'ids' ) );
	$post_id = ! empty( $old ) ? (int) $old[0] : wp_insert_post( array( 'post_type' => PARTIKULIER_ESTATIK_POST_TYPE, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => 'Security fixture', 'post_author' => $users[ $login ] ), true );
	if ( is_wp_error( $post_id ) ) { fwrite( STDERR, $post_id->get_error_message() . "\n" ); exit( 1 ); }
	$posts[ $login ] = (int) $post_id;
}
file_put_contents( '/tmp/ids.txt', implode( ' ', array( $users['marc'], $users['sofia'], $posts['marc'], $posts['sofia'] ) ) . "\n" );
echo wp_json_encode( array( 'users' => $users, 'posts' => $posts, 'ids_file' => '/tmp/ids.txt' ), JSON_PRETTY_PRINT ) . PHP_EOL;
