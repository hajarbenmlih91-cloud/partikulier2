<?php
/** Harness commun des preuves dynamiques n8n 6.16. */
if ( ! function_exists( 'pk_n8n_test_run' ) ) {
	function pk_n8n_test_run( $name, callable $callback ) {
		$report = array( 'test' => $name, 'started_at' => gmdate( 'c' ), 'passed' => false, 'checks' => array(), 'runtime_messages' => array() );
		$errors = array();
		set_error_handler( function ( $severity, $message, $file, $line ) use ( &$errors ) { if ( in_array( $severity, array( E_DEPRECATED, E_USER_DEPRECATED, E_WARNING, E_USER_WARNING ), true ) ) { $errors[] = $message . ' @ ' . basename( $file ) . ':' . $line; } return true; } );
		try {
			$result = $callback( $report );
			$report = is_array( $result ) ? $result : $report;
			$report['passed'] = true;
		} catch ( Throwable $e ) {
			$report['failure'] = $e->getMessage();
		}
		restore_error_handler();
		$report['runtime_messages'] = array_values( array_unique( array_merge( $report['runtime_messages'], $errors ) ) );
		$report['finished_at'] = gmdate( 'c' );
		$report['passed'] = $report['passed'] && empty( $report['runtime_messages'] );
		echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
		if ( ! $report['passed'] ) { exit( 1 ); }
	}
	function pk_n8n_assert( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
	function pk_n8n_settings_snapshot() { $value = get_option( Partikulier_N8n_Security::OPTION, null ); return is_array( $value ) ? $value : null; }
	function pk_n8n_restore_snapshot( $snapshot ) { if ( null === $snapshot ) { delete_option( Partikulier_N8n_Security::OPTION ); } else { update_option( Partikulier_N8n_Security::OPTION, $snapshot, false ); } }
	function pk_n8n_request( $method, $route, $body = array(), $headers = array() ) { $request = new WP_REST_Request( $method, $route ); if ( $body ) { $request->set_body( wp_json_encode( $body ) ); $request->set_body_params( $body ); } foreach ( $headers as $key => $value ) { $request->set_header( $key, $value ); } return $request; }
	function pk_n8n_hmac_headers( $method, $route, $body, $secret, $key_id, $timestamp = null ) { $timestamp = null === $timestamp ? time() : (int) $timestamp; $canonical = strtoupper( $method ) . "\n" . $route . "\n" . $timestamp . "\n" . wp_json_encode( $body ); return array( 'X-Partikulier-Automation' => $secret, 'X-Partikulier-Timestamp' => (string) $timestamp, 'X-Partikulier-Key-Id' => $key_id, 'X-Partikulier-Signature' => 'sha256=' . hash_hmac( 'sha256', $canonical, $secret ) ); }
}
