<?php
/** Run with: wp --path=wp eval-file scripts/seed-backoffice-k-export-hostile.php */
global $wpdb;
$marker = 'CDC615HOSTILE' . gmdate( 'His' );
$leads = $wpdb->prefix . 'pk_buyer_leads';
$interests = $wpdb->prefix . 'pk_interest_events';
$now = current_time( 'mysql', true );
$wpdb->insert( $leads, array( 'phone_hash' => hash( 'sha256', $marker ), 'phone_encrypted' => 'ciphertext', 'first_seen_at' => $now, 'last_seen_at' => $now ), array( '%s', '%s', '%s', '%s' ) );
$lead_id = (int) $wpdb->insert_id;
$wpdb->insert( $interests, array( 'lead_id' => $lead_id, 'property_id' => 0, 'reference_code' => $marker, 'property_snapshot' => wp_json_encode( array( 'title' => '=SUM(A1)', 'url' => home_url( '/' ) ) ), 'provider_message_id' => $marker, 'created_at' => $now ), array( '%d', '%d', '%s', '%s', '%s', '%s' ) );
echo $marker . "\n";
