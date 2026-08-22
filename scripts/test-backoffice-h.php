<?php
/** Dynamic lot H checks. Run with: wp --path=wp eval-file scripts/test-backoffice-h.php */
$result = array( 'status' => 'PASS', 'checks' => array() );
$result['checks']['defaults'] = method_exists( 'Partikulier_Customization', 'defaults' ) && 6 === count( Partikulier_Customization::defaults() );
$result['checks']['cache_add_custom'] = false !== has_action( 'add_option_pk_customization_options' );
$result['checks']['cache_update_custom'] = false !== has_action( 'update_option_pk_customization_options' );
$result['checks']['cache_add_theme'] = false !== has_action( 'add_option_pk_theme_options' );
$result['checks']['cache_update_theme'] = false !== has_action( 'update_option_pk_theme_options' );
$old = get_option( 'pk_customization_options', null );
update_option( 'pk_customization_options', array( 'editorial' => array( 'home_title' => array( 'fr' => 'FR test', 'en' => 'EN test', 'ar' => '' ) ) ) );
$result['fallback_fr'] = Partikulier_Customization::editorial( 'home_title', 'fallback' );
$result['checks']['fallback_fr'] = 'FR test' === $result['fallback_fr'];
$result['checks']['route_class'] = class_exists( 'Partikulier_Listing_Approval' );
$routes = rest_get_server()->get_routes();
$result['checks']['resend_route'] = isset( $routes['/partikulier/v1/credentials-resend-accepted'] );
if ( null === $old ) {
	delete_option( 'pk_customization_options' );
} else {
	update_option( 'pk_customization_options', $old );
}
foreach ( $result['checks'] as $check ) {
	if ( ! $check ) {
		$result['status'] = 'FAIL';
	}
}
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
if ( 'FAIL' === $result['status'] ) {
	exit( 1 );
}
