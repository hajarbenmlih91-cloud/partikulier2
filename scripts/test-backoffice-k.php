<?php
/** Dynamic lot K checks. Run with: wp --path=wp eval-file scripts/test-backoffice-k.php */
global $wpdb;
$result = array( 'status' => 'PASS', 'checks' => array(), 'measurements' => array() );
$tables = array(
	'leads' => $wpdb->prefix . 'pk_buyer_leads',
	'interests' => $wpdb->prefix . 'pk_interest_events',
	'preferences' => $wpdb->prefix . 'pk_buyer_preferences',
	'consents' => $wpdb->prefix . 'pk_whatsapp_consents',
	'limits' => $wpdb->prefix . 'pk_contact_limits',
	'followups' => $wpdb->prefix . 'pk_lead_followups',
);
$marker = 'cdc615-k-fixture';
foreach ( $tables as $table ) {
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE provider_message_id LIKE %s", $marker . '%' ) );
}
$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['leads']} WHERE phone_hash LIKE %s", hash( 'sha256', $marker ) . '%' ) );
$user = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $user ) ) { $result['status'] = 'FAIL'; $result['checks']['administrator_fixture'] = false; echo wp_json_encode( $result, JSON_PRETTY_PRINT ) . "\n"; exit( 1 ); }
wp_set_current_user( $user[0]->ID );
$measure = static function ( $count ) use ( &$wpdb, $tables, $marker ) {
	foreach ( $tables as $table ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE provider_message_id LIKE %s", $marker . '%' ) );
	}
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['leads']} WHERE phone_hash LIKE %s", hash( 'sha256', $marker ) . '%' ) );
	$now = current_time( 'mysql', true );
	for ( $i = 0; $i < $count; $i++ ) {
		$phone = sprintf( '+212600%06d', $i );
		$hash = hash( 'sha256', $phone );
		$wpdb->insert( $tables['leads'], array( 'phone_hash' => $hash, 'phone_encrypted' => $phone, 'first_seen_at' => $now, 'last_seen_at' => $now ), array( '%s', '%s', '%s', '%s' ) );
		$lead_id = (int) $wpdb->insert_id;
		$wpdb->insert( $tables['interests'], array( 'lead_id' => $lead_id, 'property_id' => 0, 'reference_code' => 'K' . $i, 'property_snapshot' => wp_json_encode( array( 'title' => 'Lead ' . $i, 'url' => home_url( '/' ) ) ), 'provider_message_id' => $marker . '-' . $i, 'created_at' => $now ), array( '%d', '%d', '%s', '%s', '%s', '%s' ) );
		$wpdb->insert( $tables['preferences'], array( 'lead_id' => $lead_id, 'budget_max' => 1000000, 'areas' => wp_json_encode( array( 'Casablanca' ) ), 'layout_value' => 'apartment', 'transaction_value' => 'buy', 'source' => 'test', 'updated_at' => $now ) );
		$wpdb->insert( $tables['consents'], array( 'lead_id' => $lead_id, 'scope' => 'similar_listings', 'granted_at' => $now, 'policy_version' => 'test', 'proof_message_id' => $marker . '-consent-' . $i ) );
		$wpdb->insert( $tables['limits'], array( 'lead_id' => $lead_id, 'day_key' => current_time( 'Y-m-d' ), 'contacts_count' => 0 ) );
		$wpdb->insert( $tables['followups'], array( 'lead_id' => $lead_id, 'status' => 'new', 'updated_at' => $now ) );
	}
	$before = $wpdb->num_queries;
	ob_start();
	Partikulier_Leads_Admin::render_page();
	$html = ob_get_clean();
	$queries = $wpdb->num_queries - $before;
	return array( 'rows' => $count, 'queries' => $queries, 'html_bytes' => strlen( $html ), 'has_table' => false !== strpos( $html, 'pk-leads-table' ), 'has_export' => false !== strpos( $html, 'pk_export_leads' ) );
};
foreach ( array( 1, 20, 100 ) as $count ) { $result['measurements'][] = $measure( $count ); }
$result['checks']['table_rendered'] = ! empty( $result['measurements'][2]['has_table'] );
$result['checks']['export_button'] = ! empty( $result['measurements'][2]['has_export'] );
$result['checks']['sql_budget_100'] = (int) $result['measurements'][2]['queries'] <= 15;
$reflection = new ReflectionMethod( 'Partikulier_Leads_Admin', 'csv_safe_value' );
$reflection->setAccessible( true );
$result['checks']['csv_formula_neutralized'] = "'=SUM(A1)" === $reflection->invoke( null, '=SUM(A1)' );
$result['checks']['csv_plain_value_untouched'] = 'Normal' === $reflection->invoke( null, 'Normal' );
foreach ( $result['checks'] as $check ) { if ( ! $check ) { $result['status'] = 'FAIL'; } }
foreach ( $tables as $table ) { $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE provider_message_id LIKE %s", $marker . '%' ) ); }
$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['leads']} WHERE phone_hash LIKE %s", hash( 'sha256', $marker ) . '%' ) );
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
if ( 'FAIL' === $result['status'] ) { exit( 1 ); }
