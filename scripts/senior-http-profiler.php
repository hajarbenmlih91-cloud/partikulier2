<?php
/* Copied into wp-content/mu-plugins for the senior qualification run. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'shutdown', function () {
    global $wpdb;
    $queries = is_array( $wpdb->queries ) ? $wpdb->queries : array();
    $normalized = array();
    foreach ( $queries as $query ) {
        $sql = preg_replace( '/\s+/', ' ', trim( (string) $query[0] ) );
        $sql = preg_replace( '/\b\d+\b/', '?', $sql );
        if ( ! isset( $normalized[ $sql ] ) ) { $normalized[ $sql ] = array( 'count' => 0, 'caller' => $query[2] ?? '' ); }
        $normalized[ $sql ]['count']++;
    }
    $duplicates = array_filter( $normalized, static function ( $row ) { return $row['count'] > 1; } );
    $row = array( 'time' => gmdate( 'c' ), 'uri' => $_SERVER['REQUEST_URI'] ?? '', 'theme' => wp_get_theme()->get( 'Version' ), 'queries' => count( $queries ), 'sql_time_ms' => round( array_sum( array_map( static function ( $q ) { return isset( $q[1] ) ? (float) $q[1] * 1000 : 0; }, $queries ) ), 3 ), 'memory_mb' => round( memory_get_peak_usage( true ) / 1048576, 3 ), 'duplicate_patterns' => count( $duplicates ), 'top_duplicates' => array_slice( $duplicates, 0, 10, true ) );
    file_put_contents( '/tmp/partikulier-senior-profile.jsonl', wp_json_encode( $row, JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND | LOCK_EX );
}, PHP_INT_MAX );
