<?php
// Test isolé : fournit uniquement les primitives WordPress utilisées par outgoing_headers().
function add_action() {}
function get_option( $key, $default = array() ) {
	return 'pk_n8n_settings' === $key ? array( 'automation_api_secret' => base64_encode( str_repeat( 'K', 32 ), ) ) : $default;
}
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function __( $text ) { return $text; }
require __DIR__ . '/../theme/inc/class-n8n-security.php';
$body = '{"event":"listing_approved","account":{"send_credentials":true}}';
$url = 'https://n8n.example.test/webhook/approved?source=partikulier';
$headers = Partikulier_N8n_Security::outgoing_headers( 'POST', $url, $body );
if ( ! is_array( $headers ) ) { fwrite( STDERR, "headers_not_array\n" ); exit( 1 ); }
$timestamp = $headers['X-Partikulier-Timestamp'];
$path = '/webhook/approved?source=partikulier';
$key = str_repeat( 'K', 32 );
$canonical = "POST\n{$path}\n{$timestamp}\n{$body}";
$expected = 'sha256=' . hash_hmac( 'sha256', $canonical, $key );
if ( $headers['X-Partikulier-Signature'] !== $expected ) { fwrite( STDERR, "signature_mismatch\n" ); exit( 1 ); }
if ( 'N' !== $headers['X-Partikulier-Key-Id'] || $headers['Content-Type'] !== 'application/json' ) { fwrite( STDERR, "header_contract_mismatch\n" ); exit( 1 ); }
echo "OUTGOING_HMAC=PASS\n";
