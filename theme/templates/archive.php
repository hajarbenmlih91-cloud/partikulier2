<?php
/**
 * Archive : annonces, taxonomies (type, action, ville...), recherche.
 *
 * Layout calque sur la page Shop du kit "Woo Shop" :
 * sidebar gauche "Filtres" (prix, type, action, villes) + zone contenu
 * avec compteur "Affichage 1-X de N resultats" et tri a droite.
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

// Contexte geo pour le titre SEO.
$queried  = get_queried_object();
$is_geo   = $queried instanceof WP_Term && in_array( $queried->taxonomy, Partikulier_Setup::GEO_TAX, true );
$is_type  = $queried instanceof WP_Term && PARTIKULIER_ESTATIK_TYPE_TAXONOMY === $queried->taxonomy;
?>

<section class="pk-archive">
	<div class="pk-container">
		<?php echo Partikulier_Geo::breadcrumbs_html(); // phpcs:ignore ?>

		<header class="pk-archive-head">
			<p class="pk-editorial-kicker"><?php esc_html_e( 'Le catalogue direct', 'partikulier' ); ?></p>
			<h1 class="pk-archive-title">
				<?php
				if ( is_search() ) {
					printf(
						/* translators: %s: terme recherche */
						esc_html__( 'Résultats pour « %s »', 'partikulier' ),
						esc_html( get_search_query() )
					);
				} elseif ( $is_geo ) {
					printf(
						/* translators: %s: nom de la ville/region */
						esc_html__( 'Annonces immobilières à %s', 'partikulier' ),
						esc_html( $queried->name )
					);
				} elseif ( $is_type ) {
					printf(
						/* translators: %s: type de bien */
						esc_html__( '%s à vendre et à louer', 'partikulier' ),
						esc_html( $queried->name )
					);
				} elseif ( is_post_type_archive( PARTIKULIER_ESTATIK_POST_TYPE ) ) {
					esc_html_e( 'Toutes les annonces', 'partikulier' );
				} else {
					single_term_title();
				}
				?>
			</h1>
			<div class="pk-archive-toolbar">
				<?php
				if ( have_posts() ) {
					printf(
						/* translators: 1: index premier, 2: index dernier, 3: total */
							esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( 'Affichage %1$s–%2$s de %3$s résultats', 'Affichage %1$s–%2$s de %3$s résultats', 'partikulier' ) : __( 'Affichage %1$s–%2$s de %3$s résultats', 'partikulier' ) ),
						'<strong>' . esc_html( number_format_i18n( ( max( 1, (int) $wp_query->query_vars['paged'] ) - 1 ) * (int) $wp_query->query_vars['posts_per_page'] + 1 ) ) . '</strong>',
						'<strong>' . esc_html( number_format_i18n( ( max( 1, (int) $wp_query->query_vars['paged'] ) - 1 ) * (int) $wp_query->query_vars['posts_per_page'] + (int) $wp_query->post_count ) ) . '</strong>',
						'<strong>' . esc_html( number_format_i18n( (int) $wp_query->found_posts ) ) . '</strong>'
					);
				} else {
					printf(
						/* translators: %s: nombre de résultats */
						esc_html( _n( '%s annonce trouvée', '%s annonces trouvées', (int) $wp_query->found_posts, 'partikulier' ) ),
						'<strong>' . esc_html( number_format_i18n( (int) $wp_query->found_posts ) ) . '</strong>'
					);
				}
				?>
				<div class="pk-view-toggle" role="group" aria-label="<?php esc_attr_e( 'Affichage des annonces', 'partikulier' ); ?>">
					<button type="button" class="pk-view-btn is-active" data-view="grid" aria-pressed="true" aria-label="<?php esc_attr_e( 'Vue grille', 'partikulier' ); ?>">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
					</button>
					<button type="button" class="pk-view-btn" data-view="list" aria-pressed="false" aria-label="<?php esc_attr_e( 'Vue liste', 'partikulier' ); ?>">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
					</button>
				</div>
				<form class="pk-archive-order" action="" method="get" role="search" aria-label="<?php esc_attr_e( 'Trier les annonces', 'partikulier' ); ?>">
					<?php
					foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( 'pk_order' === $key ) {
							continue;
						}
						if ( is_array( $value ) ) {
							foreach ( $value as $v ) {
								echo '<input type="hidden" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( $v ) . '">';
							}
						} else {
							echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
						}
					}
					?>
					<select name="pk_order" onchange="this.form.submit()" aria-label="<?php esc_attr_e( 'Tri', 'partikulier' ); ?>">
							<option value="recent"<?php selected( empty( $_GET['pk_order'] ) || 'recent' === $_GET['pk_order'] ); ?>><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( 'Plus récentes', 'Plus récentes', 'partikulier' ) : __( 'Plus récentes', 'partikulier' ) ); ?></option>
							<option value="price-asc"<?php selected( 'price-asc' === ( $_GET['pk_order'] ?? '' ) ); ?>><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( 'Prix croissant', 'Prix croissant', 'partikulier' ) : __( 'Prix croissant', 'partikulier' ) ); ?></option>
							<option value="price-desc"<?php selected( 'price-desc' === ( $_GET['pk_order'] ?? '' ) ); ?>><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( 'Prix décroissant', 'Prix décroissant', 'partikulier' ) : __( 'Prix décroissant', 'partikulier' ) ); ?></option>
							<option value="surface-desc"<?php selected( 'surface-desc' === ( $_GET['pk_order'] ?? '' ) ); ?>><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( 'Surface décroissante', 'Surface décroissante', 'partikulier' ) : __( 'Surface décroissante', 'partikulier' ) ); ?></option>
					</select>
				</form>
			</div>
		</header>

		<div class="pk-archive-search">
			<p class="pk-archive-subtitle"><?php esc_html_e( 'Des biens publiés directement par leurs propriétaires.', 'partikulier' ); ?></p>
			<?php $variant = 'archive'; require PARTIKULIER_DIR . '/templates/parts/search-form.php'; ?>
			<ul class="pk-archive-trust"><li><?php esc_html_e( 'Localisation d’abord', 'partikulier' ); ?></li><li><?php esc_html_e( 'Vendeur identifié', 'partikulier' ); ?></li><li><?php esc_html_e( 'Contact direct', 'partikulier' ); ?></li></ul>
		</div>


		<div class="pk-archive-layout">
				<aside class="pk-filters" aria-label="<?php echo esc_attr( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( 'Filtres', 'Filtres', 'partikulier' ) : __( 'Filtres', 'partikulier' ) ); ?>">
					<h2 class="screen-reader-text"><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( 'Filtres', 'Filtres', 'partikulier' ) : __( 'Filtres', 'partikulier' ) ); ?></h2>

				<div class="pk-filter">
					<h3 class="pk-filter-title"><?php esc_html_e( 'Action', 'partikulier' ); ?></h3>
					<?php
					$actions = get_terms( array( 'taxonomy' => PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY, 'hide_empty' => true ) );
					if ( $actions && ! is_wp_error( $actions ) ) {
						echo '<ul class="pk-filter-list">';
						foreach ( $actions as $term ) {
							$active = $queried instanceof WP_Term && $queried->term_id === $term->term_id ? ' aria-current="true"' : '';
							printf(
								'<li><a href="%1$s"%3$s>%2$s <span class="pk-filter-count">(%4$s)</span></a></li>',
								esc_url( pk_term_url( $term ) ),
								esc_html( $term->name ),
								$active,
								esc_html( number_format_i18n( $term->count ) )
							);
						}
						echo '</ul>';
					}
					?>
				</div>

				<div class="pk-filter">
					<h3 class="pk-filter-title"><?php esc_html_e( 'Types de biens', 'partikulier' ); ?></h3>
					<?php
					$types = get_terms( array( 'taxonomy' => PARTIKULIER_ESTATIK_TYPE_TAXONOMY, 'hide_empty' => true ) );
					if ( $types && ! is_wp_error( $types ) ) {
						echo '<ul class="pk-filter-list">';
						foreach ( $types as $term ) {
							$active = $queried instanceof WP_Term && $queried->term_id === $term->term_id ? ' aria-current="true"' : '';
							printf(
								'<li><a href="%1$s"%3$s>%2$s <span class="pk-filter-count">(%4$s)</span></a></li>',
								esc_url( pk_term_url( $term ) ),
								esc_html( $term->name ),
								$active,
								esc_html( number_format_i18n( $term->count ) )
							);
						}
						echo '</ul>';
					}
					?>
				</div>

				<div class="pk-filter">
					<h3 class="pk-filter-title"><?php esc_html_e( 'Budget maximum', 'partikulier' ); ?></h3>
						<form action="<?php echo esc_url( pk_properties_archive_url() ); ?>" method="get">
						<div class="pk-filter-price">
							<input type="number" name="pk_price_max" placeholder="<?php esc_attr_e( '€ max', 'partikulier' ); ?>" min="0" aria-label="<?php esc_attr_e( 'Budget maximum en euros', 'partikulier' ); ?>">
						</div>
							<button type="submit" class="pk-filter-apply"><?php echo esc_html( class_exists( 'Partikulier_Localization' ) ? Partikulier_Localization::translate_polylang_string( 'APPLIQUER', 'APPLIQUER', 'partikulier' ) : __( 'APPLIQUER', 'partikulier' ) ); ?></button>
					</form>
				</div>

				<div class="pk-filter">
					<h3 class="pk-filter-title"><?php esc_html_e( 'Villes populaires', 'partikulier' ); ?></h3>
					<?php
					$cities = Partikulier_Geo::top_cities( 6 );
					if ( $cities ) {
						echo '<ul class="pk-filter-list">';
						foreach ( $cities as $city ) {
							printf(
								'<li><a href="%1$s">%2$s <span class="pk-filter-count">(%3$s)</span></a></li>',
								esc_url( pk_term_url( $city ) ),
								esc_html( $city->name ),
								esc_html( number_format_i18n( $city->count ) )
							);
						}
						echo '</ul>';
					}
					?>
				</div>
			</aside>

			<div class="pk-archive-content">
				<?php if ( have_posts() ) : ?>
					<div class="pk-grid pk-grid-cards">
						<?php
						while ( have_posts() ) :
							the_post();
							$property = get_post();
							require PARTIKULIER_DIR . '/templates/parts/card-property.php';
						endwhile;
						?>
					</div>
					<?php pk_pagination(); ?>
				<?php else : ?>
					<div class="pk-empty">
						<h2><?php esc_html_e( 'Aucune annonce ne correspond à votre recherche.', 'partikulier' ); ?></h2>
						<p><?php esc_html_e( 'Essayez d\'élargir vos critères ou consultez les villes populaires.', 'partikulier' ); ?></p>
							<a class="pk-btn pk-btn-outline" href="<?php echo esc_url( pk_properties_archive_url() ); ?>">
							<?php esc_html_e( 'Voir toutes les annonces', 'partikulier' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();