<?php
/**
 * E2E Lot D : le scénario critique passe par le vrai Partikulier_Listing_Translations::sync().
 *
 * Usage :
 *   wp --path=wp eval-file scripts/test-polylang-sync-e2e.php
 *
 * Le test crée une famille temporaire FR/EN/AR, vérifie qu'une auto seule reste
 * publiée, remplace l'EN par une annonce manuelle, puis vérifie que l'ancienne
 * auto est détectée et passe en brouillon avec --apply.
 */
if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "WordPress n'est pas chargé. Utilisez wp eval-file.\n" );
	exit( 1 );
}

if ( ! class_exists( 'Partikulier_Listing_Translations' ) || ! function_exists( 'pll_get_post_translations' ) ) {
	fwrite( STDERR, "Le module Partikulier et Polylang sont requis.\n" );
	exit( 1 );
}

$created = array();
$failures = array();
$assert = static function ( $condition, $message ) use ( &$failures ) {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

$source_id = wp_insert_post(
	array(
		'post_type'    => PARTIKULIER_ESTATIK_POST_TYPE,
		'post_status'  => 'publish',
		'post_title'   => 'E2E SYNC SOURCE ' . wp_generate_uuid4(),
		'post_content' => 'Source E2E Partikulier',
		'post_author'  => get_current_user_id() ?: 1,
	),
	true
);
if ( is_wp_error( $source_id ) ) {
	fwrite( STDERR, $source_id->get_error_message() . "\n" );
	exit( 1 );
}
$source_id = (int) $source_id;
$created[] = $source_id;
wp_set_object_terms( $source_id, 'Appartement', 'es_type' );
wp_set_object_terms( $source_id, 'A louer', 'es_category' );
wp_set_object_terms( $source_id, 'Rabat', 'es_location' );

$values = array(
	'type'            => 'Appartement',
	'action'          => 'louer',
	'surface'         => '72',
	'bedrooms'        => '2',
	'bathrooms'       => '1',
	'total_rooms'     => '3',
	'district'        => 'Hay Riad',
	'city'            => 'Rabat',
	'vis_a_vis'       => 'Non',
	'terrace'         => 'Oui',
	'terrace_surface' => '12',
	'price'           => '12000',
	'floor'           => '3',
	'garage'          => 'Non',
	'elevator'         => 'Oui',
);

// Point de contrôle obligatoire : aucune méta de traduction n'est préparée à la main.
$map = Partikulier_Listing_Translations::sync( $source_id, $values, 'fr', '' );
foreach ( $map as $id ) {
	$created[] = (int) $id;
}

$assert( count( $map ) === 3, 'sync() ne retourne pas exactement FR/EN/AR.' );
$assert( ! empty( $map['fr'] ) && (int) $map['fr'] === $source_id, 'La source FR n’est pas dans la map sync().' );
$assert( ! empty( $map['en'] ) && ! empty( $map['ar'] ), 'sync() n’a pas créé EN et AR.' );

$metadata = array();
foreach ( array( 'en', 'ar' ) as $lang ) {
	$id = ! empty( $map[ $lang ] ) ? (int) $map[ $lang ] : 0;
	$metadata[ $lang ] = array(
		'id'         => $id,
		'source_raw' => (string) get_post_meta( $id, Partikulier_Listing_Translations::META_SOURCE, true ),
		'source_lang'=> (string) get_post_meta( $id, Partikulier_Listing_Translations::META_SOURCE_LANG, true ),
		'status'     => get_post_status( $id ),
	);
	$assert( ctype_digit( $metadata[ $lang ]['source_raw'] ) && (int) $metadata[ $lang ]['source_raw'] === $source_id, "sync() écrit un source ID invalide pour {$lang}." );
	$assert( 'fr' === $metadata[ $lang ]['source_lang'], "sync() n’écrit pas _pk_source_lang=fr pour {$lang}." );
}

$auto_id = (int) $map['en'];
$auto_only_report = Partikulier_Listing_Translations::orphan_report();
$auto_only_rows = array_filter( $auto_only_report, static function ( $row ) use ( $auto_id ) {
	return isset( $row['auto_id'] ) && (int) $row['auto_id'] === $auto_id;
} );
$assert( 0 === count( $auto_only_rows ), 'Une auto EN seule est considérée à tort comme orpheline.' );
$assert( 'publish' === get_post_status( $auto_id ), 'Une auto EN seule n’est plus publiée.' );

$invalid_id = wp_insert_post(
	array(
		'post_type'    => PARTIKULIER_ESTATIK_POST_TYPE,
		'post_status'  => 'publish',
		'post_title'   => 'E2E INVALID META ' . $source_id,
		'post_content' => 'Legacy metadata E2E',
		'post_author'  => get_current_user_id() ?: 1,
	),
	true
);
if ( ! is_wp_error( $invalid_id ) ) {
	$invalid_id = (int) $invalid_id;
	$created[] = $invalid_id;
	pll_set_post_language( $invalid_id, 'en' );
	update_post_meta( $invalid_id, Partikulier_Listing_Translations::META_GENERATED, '1' );
	update_post_meta( $invalid_id, Partikulier_Listing_Translations::META_SOURCE, 'fr' );
}
if ( is_wp_error( $invalid_id ) ) {
	$invalid_id = 0;
}
$invalid_report = Partikulier_Listing_Translations::orphan_report();
$invalid_rows = array_values( array_filter( $invalid_report, static function ( $row ) use ( $invalid_id ) {
	return isset( $row['auto_id'] ) && (int) $row['auto_id'] === (int) $invalid_id && 'invalid_source_meta' === ( $row['action'] ?? '' );
} ) );
$assert( ! empty( $invalid_rows ), 'Une ancienne méta de langue n’est pas signalée comme invalid_source_meta.' );

$manual_id = wp_insert_post(
	array(
		'post_type'    => PARTIKULIER_ESTATIK_POST_TYPE,
		'post_status'  => 'publish',
		'post_title'   => 'E2E MANUAL EN ' . $source_id,
		'post_content' => 'Manual replacement E2E',
		'post_author'  => get_current_user_id() ?: 1,
	),
	true
);
if ( is_wp_error( $manual_id ) ) {
	$failures[] = $manual_id->get_error_message();
} else {
	$manual_id = (int) $manual_id;
	$created[] = $manual_id;
	pll_set_post_language( $manual_id, 'en' );
	pll_save_post_translations( array( 'fr' => $source_id, 'en' => $manual_id, 'ar' => (int) $map['ar'] ) );
}

$after_manual = pll_get_post_translations( $source_id );
$dry_run = Partikulier_Listing_Translations::orphan_report();
$orphan_rows = array_values( array_filter( $dry_run, static function ( $row ) use ( $auto_id ) {
	return isset( $row['auto_id'] ) && (int) $row['auto_id'] === $auto_id && 'draft' === ( $row['action'] ?? '' );
} ) );
$assert( ! empty( $orphan_rows ), 'Le vrai flux de remplacement ne détecte pas l’auto EN orpheline.' );

$apply_rows = Partikulier_Listing_Translations::reconcile_orphans( true );
$applied_rows = array_values( array_filter( $apply_rows, static function ( $row ) use ( $auto_id ) {
	return isset( $row['auto_id'] ) && (int) $row['auto_id'] === $auto_id && ! empty( $row['applied'] );
} ) );
$assert( ! empty( $applied_rows ), 'La réconciliation --apply n’a pas appliqué le brouillon.' );
$assert( 'draft' === get_post_status( $auto_id ), 'L’auto orpheline n’est pas passée en brouillon.' );
$assert( 'publish' === get_post_status( $manual_id ), 'La traduction manuelle EN n’est pas restée publiée.' );

$result = array(
	'passed'             => empty( $failures ),
	'failures'           => $failures,
	'source_id'          => $source_id,
	'auto_id'            => $auto_id,
	'invalid_id'          => (int) $invalid_id,
	'invalid_report'      => $invalid_report,
	'manual_id'          => (int) $manual_id,
	'group_after_manual' => $after_manual,
	'metadata'           => $metadata,
	'auto_only_report'   => $auto_only_report,
	'dry_run'            => $dry_run,
	'apply'              => $apply_rows,
	'final_status'       => array(
		'auto_en'   => get_post_status( $auto_id ),
		'manual_en' => get_post_status( $manual_id ),
	),
	'cleanup_ids'        => $created,
);

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . PHP_EOL;

foreach ( array_unique( array_filter( $created ) ) as $id ) {
	wp_delete_post( (int) $id, true );
}

if ( ! empty( $failures ) ) {
	exit( 1 );
}
