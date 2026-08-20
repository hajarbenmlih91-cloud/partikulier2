<?php
/**
 * Pont d’automatisation entrant.
 *
 * Ce point d’entrée reçoit seulement des événements normalisés par n8n après
 * vérification côté n8n du webhook fournisseur. Il n’appelle jamais Meta,
 * WhatsApp, un prestataire de paiement ou un autre service externe.
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class Partikulier_Automation_Bridge {

	const DB_VERSION = '1.0.0';
	const OPTION_DB_VERSION = 'pk_automation_bridge_db_version';
	const REST_NAMESPACE = 'partikulier/v1';
	const ROUTE = '/automation-event';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 8 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function maybe_install() {
		if ( self::DB_VERSION === get_option( self::OPTION_DB_VERSION ) ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$table = self::events_table();
		dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id varchar(191) NOT NULL,
			event_type varchar(64) NOT NULL,
			source varchar(32) NOT NULL,
			payload_hash char(64) NOT NULL,
			status varchar(16) NOT NULL DEFAULT 'received',
			received_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY event_id (event_id),
			KEY type_received (event_type,received_at)
		) {$charset};" );
		update_option( self::OPTION_DB_VERSION, self::DB_VERSION, false );
	}

	public static function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'pk_automation_events';
	}

	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'receive_event' ),
				'permission_callback' => array( __CLASS__, 'check_automation_secret' ),
			)
		);
	}

	/**
	 * Vérifie le secret partagé entre n8n et WordPress. L’hébergement injecte
	 * PARTIKULIER_AUTOMATION_API_SECRET ; il ne doit jamais être présent dans le
	 * JavaScript, dans une URL ni dans une capture de workflow.
	 */
	public static function check_automation_secret( WP_REST_Request $request ) {
		$secret = Partikulier_Settings::automation_api_secret();
		$provided = (string) $request->get_header( 'x_partikulier_automation' );
		if ( ! $provided ) {
			$authorization = (string) $request->get_header( 'authorization' );
			if ( 0 === stripos( $authorization, 'bearer ' ) ) {
				$provided = trim( substr( $authorization, 7 ) );
			}
		}
		return $secret && $provided && hash_equals( $secret, $provided );
	}

	/**
	 * Journalise de manière idempotente un accusé d’événement. Le payload est
	 * haché et n’est pas persisté : les numéros, messages et autres données
	 * personnelles restent dans les modules métier minimisés déjà existants.
	 */
	public static function receive_event( WP_REST_Request $request ) {
		$event_id = substr( sanitize_text_field( (string) $request->get_param( 'event_id' ) ), 0, 191 );
		$event_type = sanitize_key( (string) $request->get_param( 'event_type' ) );
		$source = sanitize_key( (string) $request->get_param( 'source' ) );
		$payload = $request->get_param( 'payload' );
		$allowed_types = array( 'whatsapp_inbound', 'whatsapp_status', 'payment_status' );
		if ( ! $event_id || ! in_array( $event_type, $allowed_types, true ) || ! in_array( $source, array( 'n8n', 'payment_provider' ), true ) ) {
			return new WP_Error( 'pk_automation_payload', __( 'Événement d’automatisation invalide.', 'partikulier' ), array( 'status' => 400 ) );
		}

		global $wpdb;
		$table = self::events_table();
		$existing = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE event_id = %s", $event_id ) );
		if ( $existing ) {
			return new WP_REST_Response( array( 'accepted' => true, 'duplicate' => true, 'processing' => 'disabled' ), 200 );
		}
		$encoded_payload = wp_json_encode( is_array( $payload ) || is_object( $payload ) ? $payload : array( 'value' => (string) $payload ) );
		$stored = $wpdb->insert(
			$table,
			array(
				'event_id' => $event_id,
				'event_type' => $event_type,
				'source' => $source,
				'payload_hash' => hash_hmac( 'sha256', $encoded_payload, wp_salt( 'auth' ) ),
				'status' => 'received',
				'received_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( false === $stored ) {
			return new WP_Error( 'pk_automation_storage', __( 'Impossible de journaliser l’événement.', 'partikulier' ), array( 'status' => 500 ) );
		}
		return new WP_REST_Response( array( 'accepted' => true, 'duplicate' => false, 'processing' => 'disabled' ), 202 );
	}
}

Partikulier_Automation_Bridge::init();