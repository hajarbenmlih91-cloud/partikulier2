<?php
/**
 * Contrat K v2.2 : méta n8n et tri sécurisé.
 * Exécution : wp --path=wp eval-file scripts/test-backoffice-k-contract.php
 */
global $wpdb;
$result = array( 'status' => 'PASS', 'checks' => array(), 'details' => array() );
$tables = array(
	'leads'      => $wpdb->prefix . 'pk_buyer_leads',
	'interests'  => $wpdb->prefix . 'pk_interest_events',
	'followups'  => $wpdb->prefix . 'pk_lead_followups',
);
$marker = 'k615-' . substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 10 );
$legacy_meta = '_pk_credentials_last_resent_at';
$new_meta = '_pk_credentials_last_resend_accepted_at';
$migration_option = 'pk_credentials_resend_meta_migrated_v1';
$created_posts = array();
$created_leads = array();

$cleanup = static function () use ( $wpdb, $tables, $marker, &$created_posts, &$created_leads, $migration_option ) {
	foreach ( $created_leads as $lead_id ) {
		foreach ( array( $tables['interests'], $tables['followups'] ) as $table ) {
			$wpdb->delete( $table, array( 'lead_id' => $lead_id ), array( '%d' ) );
		}
		$wpdb->delete( $tables['leads'], array( 'id' => $lead_id ), array( '%d' ) );
	}
	foreach ( $created_posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
	delete_option( $migration_option );
};

try {
	$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
	if ( empty( $admin ) ) {
		throw new RuntimeException( 'Administrator fixture unavailable.' );
	}
	wp_set_current_user( $admin[0]->ID );
	$_SERVER['HTTP_HOST'] = (string) ( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'localhost' );
	$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=' . rawurlencode( Partikulier_Leads_Admin::MENU_SLUG );

	$post_id = wp_insert_post( array( 'post_type' => 'properties', 'post_status' => 'draft', 'post_title' => $marker ), true );
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( $post_id->get_error_message() );
	}
	$created_posts[] = (int) $post_id;
	$legacy_value = '2026-08-21 00:00:00';
	update_post_meta( $post_id, $legacy_meta, $legacy_value );
	delete_post_meta( $post_id, $new_meta );
	delete_option( $migration_option );
	Partikulier_Listing_Approval::migrate_resend_meta();
	$result['checks']['legacy_meta_migrated'] = $legacy_value === get_post_meta( $post_id, $new_meta, true );
	$result['checks']['legacy_meta_preserved'] = $legacy_value === get_post_meta( $post_id, $legacy_meta, true );

	$ack_post = wp_insert_post( array( 'post_type' => 'properties', 'post_status' => 'draft', 'post_title' => $marker . '-ack' ), true );
	if ( is_wp_error( $ack_post ) ) {
		throw new RuntimeException( $ack_post->get_error_message() );
	}
	$created_posts[] = (int) $ack_post;
	$request_id = wp_generate_uuid4();
	update_post_meta( $ack_post, '_pk_credentials_resend_request_id', $request_id );
	delete_post_meta( $ack_post, $new_meta );
	delete_post_meta( $ack_post, $legacy_meta );
	$request = new WP_REST_Request( 'POST', '/partikulier/v1/credentials-resend-accepted' );
	$request->set_param( 'listing_id', $ack_post );
	$request->set_param( 'resend_request_id', $request_id );
	$first_response = Partikulier_Listing_Approval::rest_resend_accepted( $request );
	$second_response = Partikulier_Listing_Approval::rest_resend_accepted( $request );
	$result['checks']['ack_writes_new_meta'] = 200 === $first_response->get_status() && ! empty( get_post_meta( $ack_post, $new_meta, true ) );
	$result['checks']['ack_does_not_write_legacy_meta'] = '' === (string) get_post_meta( $ack_post, $legacy_meta, true );
	$result['checks']['ack_idempotent'] = 200 === $second_response->get_status() && true === $second_response->get_data()['idempotent'];

	$now = current_time( 'mysql', true );
	foreach ( array( 'old', 'new' ) as $which ) {
		$timestamp = 'old' === $which ? gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) : $now;
		$phone = '+2126' . str_pad( (string) ( hexdec( substr( md5( $marker . $which ), 0, 8 ) ) % 100000000 ), 8, '0', STR_PAD_LEFT );
		if ( false === $wpdb->insert( $tables['leads'], array( 'phone_hash' => hash( 'sha256', $phone ), 'phone_encrypted' => $phone, 'first_seen_at' => $timestamp, 'last_seen_at' => $timestamp ), array( '%s', '%s', '%s', '%s' ) ) ) {
			throw new RuntimeException( 'Lead fixture insert failed: ' . $wpdb->last_error );
		}
		$lead_id = (int) $wpdb->insert_id;
		$created_leads[] = $lead_id;
		if ( false === $wpdb->insert( $tables['interests'], array( 'lead_id' => $lead_id, 'property_id' => 0, 'reference_code' => $marker . '-' . $which, 'property_snapshot' => wp_json_encode( array( 'title' => $marker . '-' . $which, 'url' => home_url( '/' ) ) ), 'provider_message_id' => $marker . '-' . $which, 'created_at' => $timestamp ), array( '%d', '%d', '%s', '%s', '%s', '%s' ) ) ) {
			throw new RuntimeException( 'Interest fixture insert failed: ' . $wpdb->last_error );
		}
	}
	$reflection = new ReflectionMethod( 'Partikulier_Leads_Admin', 'lead_rows' );
	$reflection->setAccessible( true );
	$base = array( 'status' => '', 'consent' => '', 'search' => $marker, 'page' => 1 );
	$asc = $reflection->invoke( null, array_merge( $base, array( 'orderby' => 'first_seen_at', 'order' => 'ASC' ) ) );
	$desc = $reflection->invoke( null, array_merge( $base, array( 'orderby' => 'last_seen_at', 'order' => 'DESC' ) ) );
	$invalid = $reflection->invoke( null, array_merge( $base, array( 'orderby' => '(SELECT', 'order' => 'DROP' ) ) );
	$sortable = ( new Partikulier_Leads_List_Table( array(), 0, 20, 1, Partikulier_Leads_Admin::followup_statuses() ) )->get_sortable_columns();
	$result['checks']['sortable_columns_declared'] = isset( $sortable['lead'], $sortable['consent'], $sortable['followup'] );
	$result['checks']['sort_asc'] = ! empty( $asc['rows'] ) && strpos( $asc['rows'][0]->reference_code, '-old' ) !== false;
	$result['checks']['sort_desc'] = ! empty( $desc['rows'] ) && strpos( $desc['rows'][0]->reference_code, '-new' ) !== false;
	$result['checks']['invalid_sort_falls_back'] = ! empty( $invalid['rows'] ) && strpos( $invalid['rows'][0]->reference_code, '-new' ) !== false;
	$result['details']['rows'] = array( 'asc' => array_map( static function ( $row ) { return $row->reference_code; }, $asc['rows'] ), 'desc' => array_map( static function ( $row ) { return $row->reference_code; }, $desc['rows'] ), 'invalid_fallback' => array_map( static function ( $row ) { return $row->reference_code; }, $invalid['rows'] ) );
	foreach ( $result['checks'] as $check ) {
		if ( ! $check ) {
			$result['status'] = 'FAIL';
		}
	}
} catch ( Throwable $error ) {
	$result['status'] = 'FAIL';
	$result['checks']['exception'] = false;
	$result['details']['exception'] = $error->getMessage();
}
$cleanup();
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
if ( 'FAIL' === $result['status'] ) {
	exit( 1 );
}
