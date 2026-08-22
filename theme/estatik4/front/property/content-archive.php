<?php
/**
 * Override ESTATIK : carte d'annonce dans les archives/listes Estatik.
 *
 * Les listings Estatik (shortcodes, recherche ajax) utilisent ce fichier.
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post      = isset( $property ) ? $property : get_post();
	$price     = get_post_meta( $post->ID, 'es_property_price', true ) ?: get_post_meta( $post->ID, 'es_price', true );
	$surface   = get_post_meta( $post->ID, 'es_property_area', true ) ?: get_post_meta( $post->ID, 'es_size', true );
	$bedrooms  = get_post_meta( $post->ID, '_pk_bedrooms_label', true );
	$living_rooms = get_post_meta( $post->ID, '_pk_living_rooms_label', true );
	$bathrooms = get_post_meta( $post->ID, '_pk_bathrooms_label', true );
	$terrace   = get_post_meta( $post->ID, '_pk_terrace', true );
	$terrace_surface = get_post_meta( $post->ID, '_pk_terrace_surface', true );
					if ( '' !== $bedrooms ) {
				if ( '0' === (string) $bedrooms ) {
					$bedrooms = __( 'Studio', 'partikulier' );
				} elseif ( '3+' === (string) $bedrooms ) {
					$bedrooms = __( '3 chambres ou plus', 'partikulier' );
				} else {
					$label = ( 1 === (int) $bedrooms ) ? '1 chambre' : '2 chambres';
					$bedrooms = class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( $label, $label, 'partikulier' ) : esc_html__( $label, 'partikulier' );
				}
			}

			if ( '0' === (string) $living_rooms ) {
				$living_rooms = __( 'Pièce principale', 'partikulier' );
			} elseif ( '3+' === (string) $living_rooms ) {
				$living_rooms = __( '3 salons ou plus', 'partikulier' );
			} elseif ( $living_rooms ) {
				$label = ( 1 === (int) $living_rooms ) ? '1 salon' : '2 salons';
				$living_rooms = class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( $label, $label, 'partikulier' ) : esc_html__( $label, 'partikulier' );
			}

			if ( '3+' === (string) $bathrooms ) {
				$bathrooms = __( '3 salles de bains ou plus', 'partikulier' );
			} elseif ( $bathrooms ) {
				$label = ( 1 === (int) $bathrooms ) ? Partikulier_Localization::translate_polylang_string( '1 salle de bains', '1 salle de bains', 'partikulier' ) : Partikulier_Localization::translate_polylang_string( '2 salles de bains', '2 salles de bains', 'partikulier' );
				$bathrooms = class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( $label, $label, 'partikulier' ) : esc_html__( $label, 'partikulier' );
			}
		$composition = implode( ' · ', array_filter( array( $bedrooms, $living_rooms ) ) );
		$terrace_label = 'Oui' === $terrace ? Partikulier_Localization::translate_polylang_string( 'Terrasse', 'Terrasse', 'partikulier' ) . ( $terrace_surface ? ' · ' . $terrace_surface . ' ' . Partikulier_Localization::translate_polylang_string( 'm²', 'm²', 'partikulier' ) : '' ) : '';
	$location  = Partikulier_Geo::location_string( $post->ID );
	// Optimisation senior : utiliser get_the_terms() pour beneficier du cache de WP_Query.
	$actions   = get_the_terms( $post->ID, PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY );
	$action    = ( ! is_wp_error( $actions ) && $actions ) ? $actions[0]->name : '';

// Image : galerie Estatik en premier, puis featured.
$img_id = 0;
if ( function_exists( 'es_get_property_gallery' ) ) {
	$g = es_get_property_gallery( $post->ID );
	if ( is_array( $g ) ) {
		foreach ( $g as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry['id'] ) ) {
				$img_id = (int) $entry['id'];
				break;
			}
			if ( is_numeric( $entry ) ) {
				$img_id = (int) $entry;
				break;
			}
		}
	}
}
if ( ! $img_id ) {
	$img_id = get_post_thumbnail_id( $post );
}

$jpg  = $img_id ? wp_get_attachment_image_url( $img_id, 'pk-card' ) : '';
$avif = $jpg ? Partikulier_AVIF::avif_path_for_url( $jpg ) : false;
?>

<article class="pk-card pk-card-property pk-card-estatik">
	<a class="pk-card-media" href="<?php echo esc_url( get_permalink( $post ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( $jpg ) : ?>
			<picture>
				<?php if ( $avif ) : ?>
					<source type="image/avif" srcset="<?php echo esc_attr( $avif ); ?>">
				<?php endif; ?>
				<img src="<?php echo esc_url( $jpg ); ?>" width="640" height="480" alt="<?php echo esc_attr( get_the_title( $post ) ); ?>" loading="lazy" decoding="async">
			</picture>
		<?php else : ?>
			<div class="pk-card-placeholder" aria-hidden="true"><span>🏠</span></div>
		<?php endif; ?>
		<?php if ( $action ) : ?>
			<span class="pk-card-badge pk-badge-<?php echo esc_attr( sanitize_title( $action ) ); ?>"><?php echo esc_html( $action ); ?></span>
		<?php endif; ?>
	</a>

	<div class="pk-card-body">
		<div class="pk-card-price"><?php echo $price ? esc_html( is_numeric( $price ) ? number_format_i18n( (float) $price ) : $price ) : esc_html__( 'Prix sur demande', 'partikulier' ); ?></div>
		<h3 class="pk-card-title">
			<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
				<?php echo esc_html( get_the_title( $post ) ); ?>
			</a>
		</h3>
		<?php if ( $location ) : ?>
			<p class="pk-card-location"><?php echo esc_html( $location ); ?></p>
		<?php endif; ?>
		<dl class="pk-card-meta">
			<?php if ( $surface ) : ?>
				<div class="pk-card-meta-item"><dd><?php echo esc_html( number_format_i18n( (int) $surface ) ) . ' ' . esc_html( Partikulier_Localization::translate_polylang_string( 'm²', 'm²', 'partikulier' ) ); ?></dd></div>
			<?php endif; ?>
				<?php if ( $composition ) : ?>
					<div class="pk-card-meta-item"><dd><?php echo esc_html( $composition ); ?></dd></div>
				<?php endif; ?>
				<?php if ( $bathrooms ) : ?>
					<div class="pk-card-meta-item"><dd><?php echo esc_html( $bathrooms ); ?></dd></div>
				<?php endif; ?>
				<?php if ( $terrace_label ) : ?>
					<div class="pk-card-meta-item"><dd><?php echo esc_html( $terrace_label ); ?></dd></div>
				<?php endif; ?>
		</dl>
	</div>
</article>