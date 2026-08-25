<?php
/**
 * Formulaire de recherche immobilier (barre hero).
 *
 * Strategie : POST natif vers l'archive estate_property avec les query vars
 * qu'Estatik comprend (es_action, es_type, es_city, es_price_min, es_price_max, es_size_min).
 * Si le shortcode de recherche Estatik est disponible, on l'utilise ; sinon notre barre native.
 *
 * @package Partikulier
 * @var string $variant 'hero'|'compact'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$variant  = isset( $variant ) ? $variant : 'hero';
$archive  = pk_properties_archive_url();
if ( ! $archive ) {
	$archive = function_exists( 'pk_localized_home_url' ) ? pk_localized_home_url() : home_url( '/' );
}

// Options pour les selects.
$types   = get_terms( array( 'taxonomy' => PARTIKULIER_ESTATIK_TYPE_TAXONOMY, 'hide_empty' => true ) );
$actions = get_terms( array( 'taxonomy' => PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY, 'hide_empty' => true ) );
$cities  = get_terms( array( 'taxonomy' => PARTIKULIER_ESTATIK_LOCATION_TAXONOMY, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 40 ) );
$types   = is_wp_error( $types ) ? array() : $types;
$actions = is_wp_error( $actions ) ? array() : $actions;
$cities  = is_wp_error( $cities ) ? array() : $cities;
?>
<form class="pk-search pk-search-<?php echo esc_attr( $variant ); ?>" action="<?php echo esc_url( $archive ); ?>" method="get" role="search" aria-label="<?php esc_attr_e( 'Rechercher un bien immobilier', 'partikulier' ); ?>">
	<div class="pk-search-field pk-search-type">
		<label class="pk-search-label" for="pk-s-action"><?php esc_html_e( 'Achat ou location', 'partikulier' ); ?></label>
		<select name="es_action" id="pk-s-action">
			<option value=""><?php echo esc_html( Partikulier_Localization::translate_polylang_string( 'Tout', 'Tout', 'partikulier' ) ); ?></option>
				<?php foreach ( (array) $actions as $term ) : ?>
					<?php if ( ! $term instanceof WP_Term ) { continue; } ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_taxonomy_label( $term->name ) : $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="pk-search-field pk-search-type">
		<label class="pk-search-label" for="pk-s-type"><?php esc_html_e( 'Type de bien', 'partikulier' ); ?></label>
		<select name="es_type" id="pk-s-type">
			<option value=""><?php echo esc_html( Partikulier_Localization::translate_polylang_string( 'Tous', 'Tous', 'partikulier' ) ); ?></option>
				<?php foreach ( (array) $types as $term ) : ?>
					<?php if ( ! $term instanceof WP_Term ) { continue; } ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_taxonomy_label( $term->name ) : $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="pk-search-field pk-search-city">
		<label class="pk-search-label" for="pk-s-city"><?php esc_html_e( 'Ville', 'partikulier' ); ?></label>
		<select name="es_city" id="pk-s-city">
			<option value=""><?php echo esc_html( Partikulier_Localization::translate_polylang_string( 'Toutes les villes', 'Toutes les villes', 'partikulier' ) ); ?></option>
				<?php foreach ( (array) $cities as $term ) : ?>
					<?php if ( ! $term instanceof WP_Term ) { continue; } ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_taxonomy_label( $term->name ) : $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="pk-search-field pk-search-budget">
		<label class="pk-search-label" for="pk-s-budget"><?php esc_html_e( 'Budget max', 'partikulier' ); ?></label>
		<select name="es_price_max" id="pk-s-budget">
			<option value=""><?php echo esc_html( Partikulier_Localization::translate_polylang_string( 'Illimité', 'Illimité', 'partikulier' ) ); ?></option>
				<?php foreach ( array( 100000, 200000, 300000, 400000, 500000, 750000, 1000000, 1500000 ) as $b ) : ?>
					<option value="<?php echo esc_attr( $b ); ?>"><?php echo esc_html( number_format_i18n( $b ) ) . ' MAD'; ?></option>
				<?php endforeach; ?>
		</select>
	</div>

	<div class="pk-search-action">
		<button type="submit" class="pk-btn pk-btn-search">
			<?php esc_html_e( 'Rechercher', 'partikulier' ); ?>
		</button>
	</div>
</form>