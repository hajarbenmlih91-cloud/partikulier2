<?php
// A lancer par: wp --path=<wp> eval-file scripts/ci-check/diag-query.php /fr/annonces/page/2/
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST']      = wp_parse_url( get_option( 'home' ), PHP_URL_HOST );
$_SERVER['REQUEST_URI']    = isset( $GLOBALS['argv'][1] ) ? $GLOBALS['argv'][1] : '/fr/annonces/page/2/';
unset( $_GET );
global $wp, $wp_query;
$wp->parse_request();
$wp->query_posts();
echo 'vars=', json_encode( array_intersect_key( $wp->query_vars, array_flip( array( 'post_type', 'paged', 'page', 'lang', 'pagename', 'name' ) ) ) ),
	' found=', (int) $wp_query->found_posts, ' post_count=', (int) $wp_query->post_count,
	' is_404=', var_export( $wp_query->is_404, true ), "\n";
