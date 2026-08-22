<?php
/**
 * Recette dynamique du dépôt depuis AR/EN.
 * Usage: wp eval-file scripts/test-submission-language.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pll_current_language' ) || ! function_exists( 'pll_set_post_language' ) || ! class_exists( 'Partikulier_Form' ) ) {
	echo wp_json_encode( array( 'passed' => false, 'failures' => array( 'Polylang ou formulaire indisponible' ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
	exit( 1 );
}

$types = get_terms( array( 'taxonomy' => PARTIKULIER_ESTATIK_TYPE_TAXONOMY, 'number' => 1, 'hide_empty' => false ) );
$cities = get_terms( array( 'taxonomy' => PARTIKULIER_ESTATIK_LOCATION_TAXONOMY, 'number' => 1, 'hide_empty' => false ) );
$type = ( $types && ! is_wp_error( $types ) ) ? $types[0] : null;
$city = ( $cities && ! is_wp_error( $cities ) ) ? $cities[0] : null;
$failures = array();
$cases = array();

$language_objects = function_exists( 'PLL' ) ? PLL()->model->get_languages_list() : array();
$language_by_slug = array();
foreach ( $language_objects as $language_object ) {
	$language_by_slug[ $language_object->slug ] = $language_object;
}

foreach ( array( 'ar', 'en' ) as $lang ) {
	if ( empty( $language_by_slug[ $lang ] ) ) {
		$failures[] = $lang . ': objet de langue Polylang absent';
		continue;
	}
	PLL()->curlang = $language_by_slug[ $lang ];
	$data = array(
		'pk_title' => '',
		'pk_description' => 'Description de test suffisamment longue pour vérifier la soumission multilingue et la synchronisation de la langue source dans Polylang.',
		'pk_price' => '850000',
		'pk_surface' => '72',
		'pk_type' => $type ? $type->term_id : 0,
		'pk_listing_action' => 'a-vendre',
		'pk_city' => $city ? $city->term_id : 0,
		'pk_city_name' => $city ? $city->name : 'Casablanca',
		'pk_district_name' => '',
		'pk_name' => 'Recette ' . strtoupper( $lang ),
		'pk_email' => 'qa-' . $lang . '-' . wp_rand( 1000, 9999 ) . '@example.com',
		'pk_phone' => '+212600000000',
		'pk_role' => 'proprietaire',
		'pk_action_mode' => 'vendre',
		'pk_bedrooms' => '2',
		'pk_living_rooms' => '1',
		'pk_bathrooms' => '1',
		'pk_terrace' => 'Non',
		'pk_vis_a_vis' => 'Non',
		'pk_sunshine' => '',
		'pk_floor' => '',
		'pk_garage' => 'Non',
		'pk_elevator' => 'Non',
	);
	$post_id = Partikulier_Form::process( $data, null );
	if ( is_wp_error( $post_id ) ) {
		$failures[] = $lang . ': ' . $post_id->get_error_message();
		continue;
	}
	$actual = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $post_id, 'slug' ) : '';
	$translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $post_id ) : array();
	$url = class_exists( 'Partikulier_WhatsApp_Verification' ) ? Partikulier_WhatsApp_Verification::verification_url( $post_id ) : '';
	$decoded = rawurldecode( (string) wp_parse_url( $url, PHP_URL_QUERY ) );
	$bad = preg_match( '/Bonjour|je souhaite valider|Appartement|Casablanca|ترجمة/u', $decoded );
	$cases[] = array( 'requested' => $lang, 'post_id' => $post_id, 'actual' => $actual, 'translations' => $translations, 'whatsapp_text_clean' => ! $bad );
	if ( $actual !== $lang ) {
		$failures[] = $lang . ': langue source=' . $actual;
	}
	if ( empty( $translations[ $lang ] ) ) {
		$failures[] = $lang . ': relation Polylang source absente';
	}
	if ( $bad ) {
		$failures[] = $lang . ': message WhatsApp par défaut non localisé';
	}
	wp_delete_post( $post_id, true );
	foreach ( $translations as $translated_id ) {
		if ( (int) $translated_id !== (int) $post_id ) {
			wp_delete_post( $translated_id, true );
		}
	}
}

$result = array( 'passed' => empty( $failures ), 'cases' => $cases, 'failures' => $failures );
echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
if ( $failures ) {
	exit( 1 );
}
