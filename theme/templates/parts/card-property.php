<?php
/**
 * Carte d'annonce immobiliere.
 *
 * Calquee sur la carte produit du kit "Woo Shop" : badge orange en haut
 * a droite, actions favoris/comparer a droite de l'image, prix au-dessus
 * du titre, bouton "Voir l'annonce" au survol.
 *
 * @package Partikulier
 * @var WP_Post $property Annonce.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$property = isset( $property ) ? $property : get_post();
if ( ! $property ) {
	return;
}

		$price    = get_post_meta( $property->ID, 'es_property_price', true );
		$surface  = get_post_meta( $property->ID, 'es_property_area', true );
	$bedrooms = get_post_meta( $property->ID, '_pk_bedrooms_label', true );
	$living_rooms = get_post_meta( $property->ID, '_pk_living_rooms_label', true );
	$bathrooms = get_post_meta( $property->ID, '_pk_bathrooms_label', true );
	$terrace = get_post_meta( $property->ID, '_pk_terrace', true );
	$terrace_surface = get_post_meta( $property->ID, '_pk_terrace_surface', true );
		$bedrooms = '' !== $bedrooms ? ( '0' === (string) $bedrooms ? __( 'Studio', 'partikulier' ) : ( '3+' === (string) $bedrooms ? __( '3 chambres ou plus', 'partikulier' ) : sprintf( _n( '%d chambre', '%d chambres', (int) $bedrooms, 'partikulier' ), (int) $bedrooms ) ) ) : '';
		$living_rooms = '0' === (string) $living_rooms ? __( 'Pièce principale', 'partikulier' ) : ( '3+' === (string) $living_rooms ? __( '3 salons ou plus', 'partikulier' ) : ( $living_rooms ? sprintf( _n( '%d salon', '%d salons', (int) $living_rooms, 'partikulier' ), (int) $living_rooms ) : '' ) );
		$bathrooms = '3+' === (string) $bathrooms ? __( '3 salles de bains ou plus', 'partikulier' ) : ( $bathrooms ? sprintf( _n( '%d salle de bains', '%d salles de bains', (int) $bathrooms, 'partikulier' ), (int) $bathrooms ) : '' );
		$composition = implode( ' · ', array_filter( array( $bedrooms, $living_rooms ) ) );
		$terrace_label = 'Oui' === $terrace ? __( 'Terrasse', 'partikulier' ) . ( $terrace_surface ? ' · ' . $terrace_surface . ' m²' : '' ) : '';
$location = Partikulier_Geo::location_string( $property->ID );

$types   = wp_get_object_terms( $property->ID, PARTIKULIER_ESTATIK_TYPE_TAXONOMY, array( 'number' => 1, 'fields' => 'names' ) );
$actions = wp_get_object_terms( $property->ID, PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY, array( 'number' => 1, 'fields' => 'names' ) );
$type    = ( ! is_wp_error( $types ) && $types ) ? $types[0] : __( 'Bien', 'partikulier' );
$action  = ( ! is_wp_error( $actions ) && $actions ) ? $actions[0] : '';

$thumb_id   = get_post_thumbnail_id( $property );
$gallery    = get_post_meta( $property->ID, 'es_property_gallery', true );
$gallery    = is_array( $gallery ) ? array_slice( array_unique( $gallery ), 0, 8 ) : array();
if ( $thumb_id && ! in_array( $thumb_id, $gallery, true ) ) {
	array_unshift( $gallery, $thumb_id );
}

/** Texte de prix avec unite mensuelle pour la location. */
$price_html = '';
if ( $price ) {
	$action_slug = ( $actions && ! is_wp_error( $actions ) ) ? sanitize_title( $actions[0] ) : '';
	$label       = ( 'a-louer' === $action_slug || 'location' === $action_slug || 'rent' === $action_slug ) ? sprintf( ' <span class="pk-card-price-note">%s</span>', esc_html__( '/ mois', 'partikulier' ) ) : '';
	$formatted  = is_numeric( $price ) ? number_format_i18n( (float) $price ) : $price;
	/** Devise affichee ; filtrable pour les sites hors Maroc. */
	$currency    = apply_filters( 'partikulier_currency', 'MAD' );
	$price_html  = esc_html( trim( $formatted . ' ' . $currency ) ) . $label;
} else {
	$price_html = esc_html__( 'Prix sur demande', 'partikulier' );
}
?>
<article class="pk-card pk-card-property" itemscope itemtype="https://schema.org/RealEstateListing">
	<a class="pk-card-media" href="<?php echo esc_url( get_permalink( $property ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( $gallery ) {
			$avif = Partikulier_AVIF::avif_path_for_url( wp_get_attachment_image_url( (int) $gallery[0], 'pk-card' ) );
			$jpg  = wp_get_attachment_image_url( (int) $gallery[0], 'pk-card' );
			if ( $jpg ) {
				?>
				<picture>
					<?php if ( $avif ) : ?>
						<source type="image/avif" srcset="<?php echo esc_attr( $avif ); ?>">
					<?php endif; ?>
					<img src="<?php echo esc_url( $jpg ); ?>" width="640" height="480" alt="<?php echo esc_attr( get_the_title( $property ) ); ?>" loading="lazy" decoding="async" fetchpriority="<?php echo $property === get_queried_object() ? 'high' : 'low'; ?>">
				</picture>
				<?php
			}
		} else {
			?>
			<div class="pk-card-placeholder" aria-hidden="true">
				<span><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.6V21h14V9.6"/><path d="M9.5 21v-6h5v6"/></svg></span>
			</div>
			<?php
		}
		?>
		<span class="pk-card-price" itemprop="price">
			<?php echo $price_html; ?>
		</span>
		<?php if ( $action ) : ?>
			<span class="pk-card-badge pk-badge-<?php echo esc_attr( sanitize_title( $action ) ); ?>"><?php echo esc_html( $action ); ?></span>
		<?php endif; ?>
	</a>

	<span class="pk-card-actions">
		<a class="pk-card-action pk-card-peek" href="<?php echo esc_url( get_permalink( $property ) ); ?>" aria-label="<?php esc_attr_e( 'Aperçu rapide', 'partikulier' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
		</a>
		<button type="button" class="pk-card-action pk-card-wishlist" data-post-id="<?php echo esc_attr( $property->ID ); ?>" aria-label="<?php esc_attr_e( 'Ajouter aux favoris', 'partikulier' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
		</button>
	</span>

	<div class="pk-card-body">
		<h3 class="pk-card-title">
			<a href="<?php echo esc_url( get_permalink( $property ) ); ?>" itemprop="url">
				<span itemprop="name"><?php echo esc_html( get_the_title( $property ) ); ?></span>
			</a>
		</h3>
		<?php
		$pk_role       = get_post_meta( $property->ID, '_pk_owner_role', true );
		$pk_role_label = __( 'Propriétaire', 'partikulier' );
		?>
		<p class="pk-card-topline">
			<?php if ( $location ) : ?>
				<span class="pk-card-location" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
					<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>
					<span itemprop="addressLocality"><?php echo esc_html( $location ); ?></span>
				</span>
			<?php endif; ?>
			<span class="pk-card-role"><?php echo esc_html( $pk_role_label ); ?></span>
		</p>
		<dl class="pk-card-meta">
			<?php if ( $surface ) : ?>
				<div class="pk-card-meta-item">
					<dt class="screen-reader-text"><?php esc_html_e( 'Surface', 'partikulier' ); ?></dt>
					<dd><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20 20 4"/><path d="M15 4h5v5"/><path d="M9 20H4v-5"/></svg><span><?php echo esc_html( number_format_i18n( (int) $surface ) ) . ' m²'; ?></span></dd>
				</div>
			<?php endif; ?>
			<?php if ( $composition ) : ?>
				<div class="pk-card-meta-item">
					<dt class="screen-reader-text"><?php esc_html_e( 'Composition', 'partikulier' ); ?></dt>
					<dd><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/><path d="M3 18h18"/><path d="M7 10V7a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v3"/></svg><span><?php echo esc_html( $composition ); ?></span></dd>
				</div>
			<?php endif; ?>
			<?php if ( $bathrooms ) : ?>
				<div class="pk-card-meta-item">
					<dt class="screen-reader-text"><?php esc_html_e( 'Salles de bains', 'partikulier' ); ?></dt>
					<dd><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/><path d="M7 12V6a2 2 0 0 1 4 0"/></svg><span><?php echo esc_html( $bathrooms ); ?></span></dd>
				</div>
			<?php endif; ?>
			<?php if ( $terrace_label ) : ?>
				<div class="pk-card-meta-item">
					<dt class="screen-reader-text"><?php esc_html_e( 'Terrasse', 'partikulier' ); ?></dt>
					<dd><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 20h18"/><path d="M5 20V9l7-5 7 5v11"/></svg><span><?php echo esc_html( $terrace_label ); ?></span></dd>
				</div>
			<?php endif; ?>
			<div class="pk-card-meta-item">
				<dt class="screen-reader-text"><?php esc_html_e( 'Type', 'partikulier' ); ?></dt>
				<dd><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 10h16"/></svg><span><?php echo esc_html( $type ); ?></span></dd>
			</div>
		</dl>
		<div class="pk-card-foot"><span class="pk-card-direct"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><?php esc_html_e( 'Contact direct', 'partikulier' ); ?></span><a class="pk-card-cta" href="<?php echo esc_url( get_permalink( $property ) ); ?>"><?php esc_html_e( 'Voir l\'annonce', 'partikulier' ); ?><span aria-hidden="true"> ↗</span></a></div>
	</div>
</article>