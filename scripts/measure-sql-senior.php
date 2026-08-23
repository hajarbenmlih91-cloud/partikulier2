<?php
/** Mesure déclarée: template d'archive sous SAVEQUERIES, pas une mesure HTTP complète. */
if ( ! defined( 'SAVEQUERIES' ) ) define( 'SAVEQUERIES', true );
$wp_dir = getenv( 'PK_WP_DIR' );
if ( ! $wp_dir ) { fwrite( STDERR, "PK_WP_DIR manquant\n" ); exit( 2 ); }
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = parse_url( getenv( 'PK_BASE' ) ?: 'http://localhost:8090', PHP_URL_HOST ) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/fr/annonces/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once rtrim( $wp_dir, '/' ) . '/wp-load.php';

global $wpdb;
ob_start();
include rtrim( $wp_dir, '/' ) . '/wp-content/themes/partikulier/templates/archive.php';
$output = ob_get_clean();
$queries = array();
foreach ( (array) $wpdb->queries as $query ) {
    $queries[] = array( 'sql' => (string) ( $query[0] ?? '' ), 'time' => (float) ( $query[1] ?? 0 ), 'caller' => (string) ( $query[2] ?? '' ) );
}
$version = getenv( 'PK_VERSION' ) ?: '6.17.12';
$payload = array(
    'version' => $version,
    'scope' => 'mesure du template d’archive sous SAVEQUERIES',
    'url_or_template' => '/fr/annonces/ -> theme/templates/archive.php',
    'fixture' => array( 'post_type' => 'properties', 'annonces' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='properties' AND post_status='publish'" ) ),
    'plugins_actifs' => function_exists( 'get_plugins' ) ? array_keys( get_plugins() ) : array(),
    'cache_state' => array( 'theme_cache_cleared_before_run' => true, 'object_cache' => defined( 'WP_CACHE' ) && WP_CACHE ? 'enabled' : 'default' ),
    'wordpress' => get_bloginfo( 'version' ),
    'php' => PHP_VERSION,
    'savequeries' => true,
    'queries_total' => count( $queries ),
    'queries' => $queries,
    'output_bytes' => strlen( $output ),
    'measured_at_utc' => gmdate( 'c' ),
);
$report = getenv( 'PK_SQL_REPORT' ) ?: 'documentation/sql-trace-v' . $version . '.json';
file_put_contents( $report, json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
echo json_encode( array( 'version' => $payload['version'], 'scope' => $payload['scope'], 'queries_total' => $payload['queries_total'], 'report' => $report ), JSON_UNESCAPED_UNICODE ) . "\n";
exit( $payload['queries_total'] <= 56 ? 0 : 1 );
