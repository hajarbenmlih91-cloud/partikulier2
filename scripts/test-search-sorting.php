<?php
/**
 * Test d’intégration des tris publics sur une requête principale de propriétés.
 * Usage: PK_WP_DIR=/path/to/wp php scripts/test-search-sorting.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	$wp_dir = getenv( 'PK_WP_DIR' );
	if ( ! $wp_dir ) {
		fwrite( STDERR, "PK_WP_DIR manquant\n" );
		exit( 2 );
	}
	require_once rtrim( $wp_dir, '/' ) . '/wp-load.php';
}

$version = getenv( 'PK_VERSION' ) ?: '6.17.14';
$orders  = array( 'price-asc', 'price-desc', 'surface-desc' );
$out     = array();
$errors  = array();

foreach ( $orders as $order ) {
	$_GET['pk_order'] = $order; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$query                   = new WP_Query();
	$GLOBALS['wp_the_query'] = $query;
	$query->query( array( 'post_type' => 'properties', 'post_status' => 'publish', 'posts_per_page' => 30, 'lang' => '', 'suppress_filters' => true ) );
	$rows = array();
	foreach ( $query->get_posts() as $post ) {
		$rows[] = array(
			'id'      => (int) $post->ID,
			'price'   => (int) get_post_meta( $post->ID, 'es_property_price', true ),
			'surface' => (int) get_post_meta( $post->ID, 'es_property_area', true ),
		);
	}
	$values = 'surface-desc' === $order ? array_column( $rows, 'surface' ) : array_column( $rows, 'price' );
	$expected = $values;
	sort( $expected, SORT_NUMERIC );
	if ( 'price-desc' === $order || 'surface-desc' === $order ) {
		$expected = array_reverse( $expected );
	}
	$passed       = count( $rows ) >= 6 && $values === $expected;
	$out[ $order ] = array( 'count' => count( $rows ), 'passed' => $passed, 'rows' => $rows );
	if ( ! $passed ) {
		$errors[] = $order;
	}
}
unset( $_GET['pk_order'] );

$report = getenv( 'PK_SORT_REPORT' ) ?: 'documentation/search-sorting-v' . $version . '.json';
$payload = array(
	'version' => $version,
	'passed'  => empty( $errors ),
	'errors'  => $errors,
	'orders'  => $out,
	'fixture_minimum_rows' => 6,
);
file_put_contents( $report, wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
echo wp_json_encode( array( 'version' => $version, 'passed' => empty( $errors ), 'report' => $report ), JSON_UNESCAPED_UNICODE ) . "\n";
exit( empty( $errors ) ? 0 : 1 );
