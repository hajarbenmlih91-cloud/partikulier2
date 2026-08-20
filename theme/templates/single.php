<?php
/**
 * Page annonce unique (fiche bien).
 *
 * - Galerie photos AVIF
 * - Prix, localisation, caractéristiques
 * - Description complete
 * - Bloc contact vendeur
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Si ESTATIK est actif et que c'est une property, on délègue à notre override.
// La constante couvre le type réel d’Estatik 4.3.4 : "properties".
if ( PARTIKULIER_ESTATIK_POST_TYPE === get_post_type() && Partikulier_Estatik::plugin_active() ) {
	get_header();
	$template = PARTIKULIER_DIR . '/estatik4/front/property/single.php';
	if ( file_exists( $template ) ) {
		include $template;
	}
	get_footer();
	return;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article class="pk-single">
		<div class="pk-container">
			<?php echo Partikulier_Geo::breadcrumbs_html(); // phpcs:ignore ?>

			<?php
			// Entete style single product : categorie, titre, prix gros, badge.
			$price        = get_post_meta( get_the_ID(), 'es_property_price', true ) ?: get_post_meta( get_the_ID(), 'es_price', true );
			$single_types = wp_get_object_terms( get_the_ID(), PARTIKULIER_ESTATIK_TYPE_TAXONOMY, array( 'number' => 1 ) );
			$single_actions = wp_get_object_terms( get_the_ID(), PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY, array( 'number' => 1 ) );
			?>
			<div class="pk-single-head">
				<div class="pk-single-title-block">
					<p class="pk-single-eyebrow">
						<?php if ( $single_types && ! is_wp_error( $single_types ) ) : ?>
							<span class="pk-single-cat"><?php echo esc_html( $single_types[0]->name ); ?></span>
						<?php endif; ?>
						<?php
						$pk_role = get_post_meta( get_the_ID(), '_pk_owner_role', true );
						$pk_role_label = __( 'Propriétaire', 'partikulier' );
						?>
						<span class="pk-single-role"><?php echo esc_html( $pk_role_label ); ?></span>
					</p>
					<h1 class="pk-single-title"><?php the_title(); ?></h1>
					<p class="pk-single-location">
						<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg>
						<?php echo esc_html( Partikulier_Geo::location_string( get_the_ID() ) ); ?>
					</p>
					<?php if ( $price ) : ?>
						<div class="pk-single-price-wrap">
							<p class="pk-single-price"><?php echo pk_price_html( $price ); // phpcs:ignore ?></p>
							<?php if ( $single_actions && ! is_wp_error( $single_actions ) && in_array( sanitize_title( $single_actions[0]->name ), array( 'a-louer', 'location', 'rent' ), true ) ) : ?>
								<span class="pk-single-price-note"><?php esc_html_e( '/ mois', 'partikulier' ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="pk-single-actions">
					<button type="button" class="pk-single-action pk-card-wishlist" data-post-id="<?php echo esc_attr( get_the_ID() ); ?>" aria-label="<?php esc_attr_e( 'Ajouter aux favoris', 'partikulier' ); ?>" aria-pressed="false">
						<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
					</button>
					<button type="button" class="pk-single-action pk-single-share" data-url="<?php echo esc_url( get_permalink() ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>" aria-label="<?php esc_attr_e( 'Partager cette annonce', 'partikulier' ); ?>">
						<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
					</button>
				</div>
				<?php if ( $single_actions && ! is_wp_error( $single_actions ) ) : ?>
					<span class="pk-single-badge pk-badge-<?php echo esc_attr( sanitize_title( $single_actions[0]->name ) ); ?>"><?php echo esc_html( $single_actions[0]->name ); ?></span>
				<?php endif; ?>
			</div>

			<?php
			// Galerie.
			$gallery = get_post_meta( get_the_ID(), 'es_property_gallery', true );
			$gallery = is_array( $gallery ) ? array_slice( array_unique( $gallery ), 0, 10 ) : array();
			$thumb   = get_post_thumbnail_id( get_the_ID() );
			if ( $thumb && ! in_array( $thumb, $gallery, true ) ) {
				array_unshift( $gallery, $thumb );
			}
			if ( $gallery ) :
				?>
				<div class="pk-single-gallery" data-count="<?php echo count( $gallery ); ?>">
					<?php foreach ( $gallery as $i => $id ) : ?>
						<?php
							$img  = wp_get_attachment_image_url( (int) $id, 'pk-hero' );
							// Le filtre global peut déjà retourner l’AVIF ; la balise picture doit conserver son JPEG de secours.
							if ( $img && str_ends_with( $img, '.avif' ) ) {
								$img = substr( $img, 0, -5 );
							}
							$avif = $img ? Partikulier_AVIF::avif_path_for_url( $img ) : false;
						$alt  = get_post_meta( (int) $id, '_wp_attachment_image_alt', true ) ?: get_the_title();
						?>
						<?php if ( $img ) : ?>
							<picture class="pk-single-gallery-item">
								<?php if ( $avif ) : ?>
									<source type="image/avif" srcset="<?php echo esc_attr( $avif ); ?>">
								<?php endif; ?>
								<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="1600" height="900" loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>" decoding="async" <?php echo 0 === $i ? 'fetchpriority="high"' : ''; ?>>
							</picture>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ( count( $gallery ) > 1 ) : ?>
						<span class="pk-single-gallery-count" aria-hidden="true">1 / <?php echo (int) count( $gallery ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="pk-single-grid">
				<div class="pk-single-main">
					<section class="pk-single-section" aria-label="<?php esc_attr_e( 'Caractéristiques', 'partikulier' ); ?>">
						<h2 class="pk-single-section-title"><?php esc_html_e( 'Caractéristiques', 'partikulier' ); ?></h2>
						<dl class="pk-features">
							<?php
							$surface  = get_post_meta( get_the_ID(), 'es_property_area', true ) ?: get_post_meta( get_the_ID(), 'es_size', true );
								$bedrooms = get_post_meta( get_the_ID(), '_pk_bedrooms_label', true ) ?: get_post_meta( get_the_ID(), 'es_bedrooms', true );
								$living_rooms = get_post_meta( get_the_ID(), '_pk_living_rooms_label', true ) ?: get_post_meta( get_the_ID(), '_pk_living_rooms', true );
								$bath = get_post_meta( get_the_ID(), '_pk_bathrooms_label', true ) ?: get_post_meta( get_the_ID(), 'es_bathrooms', true );
								$terrace = get_post_meta( get_the_ID(), '_pk_terrace', true );
								$terrace_surface = get_post_meta( get_the_ID(), '_pk_terrace_surface', true );
								$bedrooms = '0' === (string) $bedrooms ? 'Studio' : ( '3+' === (string) $bedrooms ? '3 chambres ou plus' : ( $bedrooms ? $bedrooms . ' chambre' . ( '1' === (string) $bedrooms ? '' : 's' ) : '' ) );
								$living_rooms = '0' === (string) $living_rooms ? 'Pièce principale' : ( '3+' === (string) $living_rooms ? '3 salons ou plus' : ( $living_rooms ? $living_rooms . ' salon' . ( '1' === (string) $living_rooms ? '' : 's' ) : '' ) );
								$bath = '3+' === (string) $bath ? '3 salles de bains ou plus' : ( $bath ? $bath . ' salle' . ( '1' === (string) $bath ? '' : 's' ) . ' de bains' : '' );
								$terrace_label = 'Oui' === $terrace ? 'Oui' . ( $terrace_surface ? ' · ' . $terrace_surface . ' m²' : '' ) : 'Non';
							$garage   = get_post_meta( get_the_ID(), 'es_garages', true );
							$floor    = get_post_meta( get_the_ID(), 'es_floor', true );
							$year     = get_post_meta( get_the_ID(), 'es_year_built', true );
							$energy   = get_post_meta( get_the_ID(), 'es_energy_class', true );
							$fields   = array(
								'es_size'        => array( $surface, __( 'Surface', 'partikulier' ), 'm²' ),
									'es_bedrooms'    => array( $bedrooms, __( 'Chambres', 'partikulier' ), '' ),
									'_pk_living_rooms' => array( $living_rooms, __( 'Salons', 'partikulier' ), '' ),
									'es_bathrooms'   => array( $bath, __( 'Salles de bains', 'partikulier' ), '' ),
									'_pk_terrace'     => array( $terrace_label, __( 'Terrasse', 'partikulier' ), '' ),
								'es_garages'     => array( $garage, __( 'Parkings', 'partikulier' ), '' ),
								'es_floor'       => array( $floor, __( 'Étage', 'partikulier' ), '' ),
								'es_year_built'  => array( $year, __( 'Année de construction', 'partikulier' ), '' ),
								'es_energy_class' => array( $energy, __( 'Classe énergie', 'partikulier' ), '' ),
							);
							foreach ( $fields as $key => $f ) {
								if ( '' !== $f[0] && null !== $f[0] ) {
									printf(
										'<div class="pk-feature"><dt>%s</dt><dd>%s %s</dd></div>',
										esc_html( $f[1] ),
											esc_html( is_numeric( $f[0] ) ? number_format_i18n( (string) $f[0] ) : (string) $f[0] ),
										esc_html( $f[2] )
									);
								}
							}
							?>
						</dl>
					</section>

					<section class="pk-single-section pk-single-description" aria-label="<?php esc_attr_e( 'Description', 'partikulier' ); ?>">
						<h2 class="pk-single-section-title"><?php esc_html_e( 'Description', 'partikulier' ); ?></h2>
						<div class="pk-content">
							<?php the_content(); ?>
						</div>
					</section>
				</div>

				<aside class="pk-single-sidebar">
					<div class="pk-contact-card pk-contact-card--dark">
						<p class="pk-contact-kicker"><?php esc_html_e( 'Contact sécurisé', 'partikulier' ); ?></p>
						<h2 class="pk-contact-title"><?php esc_html_e( 'Intéressé par ce bien ?', 'partikulier' ); ?></h2>
						<p class="pk-contact-note"><?php esc_html_e( 'Envoyez cette annonce sur WhatsApp. Après vérification de votre demande, nous vous transmettons les coordonnées du propriétaire.', 'partikulier' ); ?></p>
						<?php
						$author = get_userdata( get_the_author_meta( 'ID' ) );
						$pk_role      = get_post_meta( get_the_ID(), '_pk_owner_role', true );
						$pk_role_name = __( 'Propriétaire', 'partikulier' );
						if ( $author ) :
							?>
							<div class="pk-contact-owner">
								<span class="pk-contact-avatar" aria-hidden="true"><?php echo esc_html( mb_substr( $author->display_name, 0, 1 ) ); ?></span>
								<div>
									<strong><?php echo esc_html( $pk_role_name ); ?></strong>
									<span><?php esc_html_e( 'Contact transmis après contrôle', 'partikulier' ); ?></span>
								</div>
							</div>
						<?php endif; ?>
						<div class="pk-contact-actions">
							<?php
							$pk_wa_text = rawurlencode( sprintf( __( 'Bonjour, je suis intéressé par « %1$s » — %2$s', 'partikulier' ), get_the_title(), get_permalink() ) );
							?>
							<a class="pk-btn pk-btn-primary pk-btn-block pk-btn-whatsapp" href="https://wa.me/?text=<?php echo esc_attr( $pk_wa_text ); ?>" target="_blank" rel="noopener nofollow">
								<svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.5 14.95L2 22l5.2-1.5A9.9 9.9 0 1 0 12.04 2zm0 1.8a8.1 8.1 0 1 1-4.13 15.06l-.3-.18-3.08.89.9-3-.2-.31A8.1 8.1 0 0 1 12.04 3.8zm4.6 10.2c-.25-.13-1.46-.72-1.69-.8-.22-.09-.39-.13-.55.12s-.63.8-.77.96c-.14.17-.28.19-.53.06a6.6 6.6 0 0 1-3.3-2.88c-.25-.43.25-.4.71-1.33.08-.17.04-.31-.02-.44s-.55-1.33-.76-1.82c-.2-.48-.4-.41-.55-.42h-.47a.9.9 0 0 0-.65.3 2.75 2.75 0 0 0-.86 2.05c0 1.2.88 2.37 1 2.53.12.17 1.72 2.63 4.17 3.69 1.55.67 2.16.73 2.94.61.47-.07 1.46-.6 1.67-1.18.2-.58.2-1.07.14-1.18-.06-.1-.23-.17-.48-.29z"/></svg>
								<?php esc_html_e( 'Demander sur WhatsApp', 'partikulier' ); ?>
							</a>
						</div>
						<p class="pk-contact-legal">
							<?php esc_html_e( 'Vos critères sont enregistrés pour vérifier la demande. Les annonces similaires ne sont envoyées qu’avec votre accord explicite.', 'partikulier' ); ?>
						</p>
						<?php
						$city_link = Partikulier_Geo::city_link( get_the_ID() );
						if ( $city_link ) :
							?>
							<a class="pk-contact-city" href="<?php echo esc_url( $city_link ); ?>">
								<?php esc_html_e( 'Voir les autres annonces dans cette ville', 'partikulier' ); ?> →
							</a>
						<?php endif; ?>
					</div>
				</aside>
			</div>
		</div>
	</article>

	<?php
	// --- "Voir aussi a <ville>" : annonces de la meme ville (parite React) ---
	$pk_city_terms = wp_get_object_terms( get_the_ID(), PARTIKULIER_ESTATIK_LOCATION_TAXONOMY, array( 'fields' => 'ids' ) );
	$pk_city_name  = '';
	if ( ! is_wp_error( $pk_city_terms ) && $pk_city_terms ) {
		$pk_term_obj  = get_term( $pk_city_terms[0], PARTIKULIER_ESTATIK_LOCATION_TAXONOMY );
		$pk_city_name = ( $pk_term_obj && ! is_wp_error( $pk_term_obj ) ) ? $pk_term_obj->name : '';
	}
	$pk_related = array();
	if ( $pk_city_terms && ! is_wp_error( $pk_city_terms ) ) {
		$pk_related = get_posts( array(
			'post_type'      => PARTIKULIER_ESTATIK_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'post__not_in'   => array( get_the_ID() ),
			'no_found_rows'  => true,
			'tax_query'      => array( array(
				'taxonomy' => PARTIKULIER_ESTATIK_LOCATION_TAXONOMY,
				'field'    => 'term_id',
				'terms'    => $pk_city_terms,
			) ),
			'meta_query'     => Partikulier_Dashboard::active_listing_meta_query(),
		) );
	}
	if ( $pk_related ) :
		?>
		<section class="pk-editorial-section pk-editorial-section--tint pk-single-related">
			<div class="pk-container">
				<div class="pk-editorial-heading pk-editorial-heading--row">
					<div>
						<p class="pk-editorial-kicker"><?php esc_html_e( 'Dans la même ville', 'partikulier' ); ?></p>
						<h2><?php
							/* translators: %s: nom de la ville */
							printf( esc_html__( 'Voir aussi à %s', 'partikulier' ), esc_html( $pk_city_name ) );
						?></h2>
					</div>
					<a class="pk-editorial-link" href="<?php echo esc_url( Partikulier_Geo::city_link( get_the_ID() ) ?: pk_properties_archive_url() ); ?>">
						<?php esc_html_e( 'Tout voir', 'partikulier' ); ?> <span aria-hidden="true">→</span>
					</a>
				</div>
				<div class="pk-editorial-cards">
					<?php foreach ( $pk_related as $property ) { require PARTIKULIER_DIR . '/templates/parts/card-property.php'; } wp_reset_postdata(); ?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();