<?php
/**
 * Partikulier — Theme de portail immobilier.
 *
 * Zero jQuery. Cache de page integre. Conversion AVIF.
 * Schema.org JSON-LD. SEO/Geo/LLM-ready. Concu pour ESTATIK.
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PARTIKULIER_VERSION', '6.16.0' );
define( 'PARTIKULIER_DIR', get_template_directory() );
define( 'PARTIKULIER_URI', get_template_directory_uri() );
define( 'PARTIKULIER_ESTATIK_POST_TYPE', 'properties' );
define( 'PARTIKULIER_ESTATIK_TYPE_TAXONOMY', 'es_type' );
define( 'PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY', 'es_category' );
define( 'PARTIKULIER_ESTATIK_LOCATION_TAXONOMY', 'es_location' );

/**
 * URL des annonces Estatik v4, avec un repli lisible avant l'initialisation du plugin.
 *
 * @return string
 */
function pk_properties_archive_url() {
	$url = get_post_type_archive_link( PARTIKULIER_ESTATIK_POST_TYPE );
	if ( $url ) {
		$parsed_host = wp_parse_url( $url, PHP_URL_HOST );
		$invalid_host = in_array( $parsed_host, array( '0', '0.0.0.0', 'localhost' ), true );
		if ( ! $invalid_host ) {
			return $url;
		}
	}

	// Estatik peut exposer une archive invalide avant sa configuration publique.
	// La page WordPress /annonces/ reste le repli SEO et Polylang la traduira si liée.
	return pk_page_url( 'annonces', '/annonces/' );
}

/**
 * Force le slug public francisé de l’archive Estatik sans créer de page /annonces/.
 *
 * @param array  $args Arguments du type de contenu.
 * @param string $post_type Identifiant du type.
 * @return array
 */
function pk_properties_post_type_args( $args, $post_type ) {
	if ( PARTIKULIER_ESTATIK_POST_TYPE === $post_type ) {
		$args['has_archive'] = 'annonces';
	}
	return $args;
}
add_filter( 'register_post_type_args', 'pk_properties_post_type_args', 20, 2 );

/**
 * Résout l’URL d’une page structurelle dans la langue courante.
 * Polylang reçoit l’ID de la page source et renvoie sa traduction publiée.
 *
 * @param string $slug Slug de la page source.
 * @param string $fallback Chemin de secours uniquement si la page n’existe pas.
 * @return string
 */
function pk_page_url( $slug, $fallback = '/' ) {
	$page = get_page_by_path( trim( (string) $slug, '/' ), OBJECT, 'page' );
	if ( $page && function_exists( 'pll_get_post' ) ) {
		$translated_id = pll_get_post( $page->ID );
		if ( $translated_id ) {
			$page = get_post( $translated_id );
		}
	}
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}
	return home_url( $fallback );
}

/**
 * Chargement modulaire des modules du theme.
 */
$partikulier_modules = array(
	'/inc/class-theme-setup.php',
	'/inc/class-scripts.php',
	'/inc/class-optimization.php',
	'/inc/class-seo.php',
	'/inc/class-jsonld.php',
	'/inc/class-sitemap.php',
	'/inc/class-cache.php',
	'/inc/class-security.php',
	'/inc/class-avif.php',
	'/inc/class-geo.php',
	'/inc/class-search-filters.php',
	'/inc/class-form.php',
	'/inc/class-dashboard.php',
	'/inc/class-owner-insights.php',
	'/inc/class-estatik.php',
	'/inc/class-n8n-security.php',
	'/inc/class-settings.php',
	'/inc/class-customization.php',
	'/inc/class-whatsapp-verification.php',
	'/inc/class-buyer-qualification.php',
	'/inc/class-lead-retention.php',
	'/inc/class-leads-admin.php',
	'/inc/class-premium.php',
	'/inc/class-localization.php',
	'/inc/class-saved-alerts.php',
	'/inc/class-automation-bridge.php',
	'/inc/class-payment-foundation.php',
	'/inc/class-page-templates.php',
	'/inc/class-required-pages.php',
	'/inc/class-morocco-places.php',
	'/inc/class-place-requests.php',
	'/inc/class-listing-preview.php',
	'/inc/class-listing-i18n.php',
	'/inc/class-listing-translations.php',
	'/inc/class-upgrade-wizard.php',
	'/inc/class-page-doctor.php',
	'/inc/class-listing-approval.php',
	'/inc/class-listing-urls.php',
	'/templates/parts/menu.php',
	'/templates/parts/helpers.php',
);

foreach ( $partikulier_modules as $module ) {
	$file = PARTIKULIER_DIR . $module;
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}
