<?php
/**
 * Export CSV strict.
 * Run with: wp --path=wp eval-file scripts/test-backoffice-k-export.php > /tmp/pk-export.csv
 */
if ( ! defined( 'ABSPATH' ) ) { fwrite( STDERR, "WordPress not loaded\n" ); exit( 1 ); }
global $wpdb;
$marker    = 'kexp-' . substr( md5( wp_generate_uuid4() ), 0, 12 );
$leads     = $wpdb->prefix . 'pk_buyer_leads';
$interests = $wpdb->prefix . 'pk_interest_events';
$now       = current_time( 'mysql', true );
$lead_id   = 0;
$fail      = static function ( $message ) use ( $wpdb, $leads, $interests, &$lead_id ): void {
    if ( $lead_id ) {
        $wpdb->delete( $interests, array( 'lead_id' => $lead_id ), array( '%d' ) );
        $wpdb->delete( $leads, array( 'id' => $lead_id ), array( '%d' ) );
    }
    fwrite( STDERR, $message . "\n" );
    exit( 1 );
};
if ( ! $wpdb->insert( $leads, array( 'phone_hash' => hash( 'sha256', $marker ), 'phone_encrypted' => 'ciphertext', 'first_seen_at' => $now, 'last_seen_at' => $now ), array( '%s', '%s', '%s', '%s' ) ) ) { $fail( 'lead fixture insert failed' ); }
$lead_id = (int) $wpdb->insert_id;
if ( ! $wpdb->insert( $interests, array( 'lead_id' => $lead_id, 'property_id' => 0, 'reference_code' => $marker, 'property_snapshot' => wp_json_encode( array( 'title' => '=SUM(A1)', 'url' => home_url( '/' ) ) ), 'provider_message_id' => $marker, 'created_at' => $now ), array( '%d', '%d', '%s', '%s', '%s', '%s' ) ) ) { $fail( 'interest fixture insert failed' ); }
$users = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $users ) ) { $fail( 'administrator fixture missing' ); }
wp_set_current_user( $users[0]->ID );
wp_set_auth_cookie( $users[0]->ID, false );
$_POST = array( 'action' => 'pk_export_leads', '_wpnonce' => wp_create_nonce( 'pk_export_leads' ), 'lead_status' => '', 'consent' => '', 'q' => '' );
$_REQUEST = $_POST;
ob_start();
try {
    Partikulier_Leads_Admin::handle_export();
} catch ( Throwable $error ) {
    ob_end_clean();
    $fail( 'handler exception: ' . $error->getMessage() );
}
$output = ob_get_clean();
$wpdb->delete( $interests, array( 'lead_id' => $lead_id ), array( '%d' ) );
$wpdb->delete( $leads, array( 'id' => $lead_id ), array( '%d' ) );
if ( false === strpos( $output, "\xEF\xBB\xBF" ) ) { $fail( 'UTF-8 BOM missing' ); }
if ( false === strpos( $output, "'=SUM(A1)" ) ) { $fail( 'formula neutralization missing' ); }
if ( false !== strpos( $output, $marker ) && false === strpos( $output, "'=SUM(A1)" ) ) { $fail( 'hostile row not safely exported' ); }
if ( false !== strpos( $output, 'ciphertext' ) ) { $fail( 'encrypted phone leaked' ); }
echo $output;
