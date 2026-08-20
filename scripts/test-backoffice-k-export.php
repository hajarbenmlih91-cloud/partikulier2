<?php
/** Run with: wp --path=wp eval-file scripts/test-backoffice-k-export.php > /tmp/pk-export.csv */
global $wpdb;
$marker = 'cdc615-k-export-' . wp_generate_uuid4();
$leads = $wpdb->prefix . 'pk_buyer_leads';
$interests = $wpdb->prefix . 'pk_interest_events';
$now = current_time( 'mysql', true );
$wpdb->insert( $leads, array( 'phone_hash' => hash( 'sha256', $marker ), 'phone_encrypted' => 'ciphertext', 'first_seen_at' => $now, 'last_seen_at' => $now ), array( '%s', '%s', '%s', '%s' ) );
$lead_id = (int) $wpdb->insert_id;
$wpdb->insert( $interests, array( 'lead_id' => $lead_id, 'property_id' => 0, 'reference_code' => $marker, 'property_snapshot' => wp_json_encode( array( 'title' => '=SUM(A1)', 'url' => home_url( '/' ) ) ), 'provider_message_id' => $marker, 'created_at' => $now ), array( '%d', '%d', '%s', '%s', '%s', '%s' ) );
$user = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $user[0]->ID );
wp_set_auth_cookie( $user[0]->ID, false );
$_POST = array( 'action' => 'pk_export_leads', '_wpnonce' => wp_create_nonce( 'pk_export_leads' ), 'lead_status' => '', 'consent' => '', 'q' => '' );
Partikulier_Leads_Admin::handle_export();
