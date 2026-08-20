<?php
/**
 * Accueil Partikulier — structure éditoriale alignée sur le preview Manus.
 * Les annonces, villes et catégories restent issus d’Estatik ; les blocs vides
 * affichent un état éditorial honnête sans inventer de biens ou de témoignages.
 * @package Partikulier
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$recent = get_posts( array(
	'post_type' => PARTIKULIER_ESTATIK_POST_TYPE,
	'post_status' => 'publish',
	'posts_per_page' => 6,
	'no_found_rows' => true,
	'meta_query' => Partikulier_Dashboard::active_listing_meta_query(),
) );
$featured = get_posts( array(
	'post_type' => PARTIKULIER_ESTATIK_POST_TYPE,
	'post_status' => 'publish',
	'posts_per_page' => 1,
	'no_found_rows' => true,
	'meta_query' => Partikulier_Dashboard::active_listing_meta_query(),
) );
// Prefixe pk_home_ : card-property.php et search-form.php ecrasent $types
// (variables globales partagees par require). Bug constate au rendu :
// une seule tuile de type s'affichait, vide, apres la boucle des cartes.
$pk_home_types = get_terms( array( 'taxonomy' => PARTIKULIER_ESTATIK_TYPE_TAXONOMY, 'hide_empty' => false, 'number' => 6 ) );
$pk_home_cities = Partikulier_Geo::top_cities( 8 );
$regions = Partikulier_Geo::top_regions( 8 );
$hero_url = Partikulier_Customization::hero_url();
$hero_alt = Partikulier_Customization::hero_alt( 'Maison lumineuse à vendre entre particuliers' );
$home = home_url( '/' );
$archive = pk_properties_archive_url();
if ( is_wp_error( $archive ) || ! is_string( $archive ) ) { $archive = home_url( '/' ); }
$deposit = pk_page_url( 'deposer-une-annonce', '/deposer-une-annonce/' );
$tagline = Partikulier_Settings::get( 'site_tagline' );
$intro = Partikulier_Settings::get( 'site_intro' );
?>

<section class="pk-editorial-hero" aria-label="<?php esc_attr_e( 'Recherche principale', 'partikulier' ); ?>">
	<div class="pk-editorial-hero__media"><img src="<?php echo esc_url( $hero_url ); ?>" alt="<?php echo esc_attr( $hero_alt ); ?>" width="1600" height="686" fetchpriority="high" decoding="async"><div class="pk-editorial-hero__veil"></div></div>
	<div class="pk-container pk-editorial-hero__inner">
		<div class="pk-editorial-hero__copy">
			<p class="pk-editorial-kicker"><?php echo esc_html( Partikulier_Settings::get( 'topbar_text' ) ); ?></p>
			<h1><?php
			// Le preview React scinde l'accroche : 1re ligne blanche sans-serif,
			// 2e ligne en italique serif sable. On coupe sur le dernier segment
			// pour rester fidele sans imposer de balisage au texte administrable.
			$pk_parts = preg_split( '/\s+/', trim( (string) $tagline ) );
			if ( count( $pk_parts ) > 2 ) {
				$pk_tail = implode( ' ', array_slice( $pk_parts, -2 ) );
				$pk_head = implode( ' ', array_slice( $pk_parts, 0, -2 ) );
				echo esc_html( $pk_head ) . ' <span class="pk-hero-accent">' . esc_html( $pk_tail ) . '</span>';
			} else {
				echo esc_html( $tagline );
			}
			?></h1>
			<p class="pk-editorial-hero__intro"><?php echo esc_html( $intro ); ?></p>
			<div class="pk-editorial-actions"><a class="pk-btn pk-btn-primary" href="<?php echo esc_url( $deposit ); ?>"><?php echo esc_html( Partikulier_Settings::get( 'btn_deposit' ) ); ?><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a><a class="pk-btn pk-btn-light" href="<?php echo esc_url( $archive ); ?>"><?php echo esc_html( Partikulier_Settings::get( 'btn_listings' ) ); ?><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></a></div>
		</div>
		<div class="pk-editorial-search"><p class="pk-editorial-search__hint"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg><?php esc_html_e( 'Commencez par une ville, un quartier ou un code postal.', 'partikulier' ); ?></p><?php $variant = 'hero'; require PARTIKULIER_DIR . '/templates/parts/search-form.php'; ?><ul class="pk-hero-trust"><li><?php esc_html_e( 'Zéro commission', 'partikulier' ); ?></li><li><?php esc_html_e( 'Vendeur identifié', 'partikulier' ); ?></li><li><?php esc_html_e( 'Contact direct', 'partikulier' ); ?></li></ul></div>
	</div>
</section>

<section class="pk-editorial-section pk-editorial-section--featured"><div class="pk-container"><div class="pk-editorial-heading"><p class="pk-editorial-kicker"><?php esc_html_e( 'Annonce à la une', 'partikulier' ); ?></p><h2><?php esc_html_e( 'Des biens choisis pour leur lumière.', 'partikulier' ); ?></h2><p><?php esc_html_e( 'Un accès direct aux annonces publiées par leurs propriétaires.', 'partikulier' ); ?></p></div>
<?php if ( $featured ) : $property = $featured[0]; $img = Partikulier_AVIF::first_image( $property->ID ); ?><a class="pk-editorial-feature" href="<?php echo esc_url( get_permalink( $property ) ); ?>"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" width="1600" height="900" alt="<?php echo esc_attr( get_the_title( $property ) ); ?>" loading="lazy" decoding="async"><?php endif; ?><span class="pk-editorial-feature__veil"></span><span class="pk-editorial-feature__content"><strong><?php echo esc_html( get_the_title( $property ) ); ?></strong><small><?php echo esc_html( Partikulier_Geo::location_string( $property->ID ) ); ?></small></span></a><?php else : ?><div class="pk-editorial-empty"><strong><?php esc_html_e( 'Les premières annonces apparaîtront ici.', 'partikulier' ); ?></strong><span><?php esc_html_e( 'Déposez un bien gratuitement pour ouvrir cette sélection.', 'partikulier' ); ?></span><a class="pk-btn pk-btn-dark" href="<?php echo esc_url( $deposit ); ?>"><?php esc_html_e( 'Déposer une annonce', 'partikulier' ); ?></a></div><?php endif; ?></div></section>

<section class="pk-editorial-section pk-editorial-section--tint"><div class="pk-container"><div class="pk-editorial-heading pk-editorial-heading--row"><div><p class="pk-editorial-kicker"><?php esc_html_e( 'Fraîchement publiées', 'partikulier' ); ?></p><h2><?php esc_html_e( 'Les dernières annonces', 'partikulier' ); ?></h2><p><?php esc_html_e( 'Des biens ajoutés récemment par leurs propriétaires.', 'partikulier' ); ?></p></div><a class="pk-editorial-link" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Voir toutes les annonces', 'partikulier' ); ?> <span aria-hidden="true">→</span></a></div><?php if ( $recent ) : ?><div class="pk-editorial-cards"><?php foreach ( $recent as $property ) { require PARTIKULIER_DIR . '/templates/parts/card-property.php'; } wp_reset_postdata(); ?></div><?php else : ?><div class="pk-editorial-empty pk-editorial-empty--compact"><strong><?php esc_html_e( 'Aucune annonce publiée pour le moment.', 'partikulier' ); ?></strong><a class="pk-editorial-link" href="<?php echo esc_url( $deposit ); ?>"><?php esc_html_e( 'Publier gratuitement', 'partikulier' ); ?> <span aria-hidden="true">→</span></a></div><?php endif; ?></div></section>

<section class="pk-editorial-section"><div class="pk-container"><div class="pk-editorial-heading"><p class="pk-editorial-kicker"><?php esc_html_e( 'Trouvez votre bien', 'partikulier' ); ?></p><h2><?php esc_html_e( 'Une recherche qui commence par le bon lieu.', 'partikulier' ); ?></h2><p><?php esc_html_e( 'Parcourez les catégories sans bruit, puis laissez les détails vous guider.', 'partikulier' ); ?></p></div><div class="pk-editorial-types"><?php if ( ! is_wp_error( $pk_home_types ) && $pk_home_types ) : foreach ( $pk_home_types as $term ) : ?><a href="<?php echo esc_url( pk_term_url( $term, $archive ) ); ?>" class="pk-editorial-type"><span class="pk-editorial-type__icon"><?php echo pk_type_icon( $term->slug ); // phpcs:ignore ?></span><strong><?php echo esc_html( $term->name ); ?></strong><small><?php echo esc_html( number_format_i18n( $term->count ) ); ?> <?php esc_html_e( 'annonces', 'partikulier' ); ?></small></a><?php endforeach; else : foreach ( array( 'Appartement', 'Maison', 'Terrain', 'Parking', 'Immeuble', 'Local' ) as $label ) : ?><a href="<?php echo esc_url( $archive ); ?>" class="pk-editorial-type"><span class="pk-editorial-type__icon"><?php echo pk_type_icon( sanitize_title( $label ) ); // phpcs:ignore ?></span><strong><?php echo esc_html( $label ); ?></strong><small><?php esc_html_e( 'Explorer', 'partikulier' ); ?></small></a><?php endforeach; endif; ?></div></div></section>

<section class="pk-editorial-section pk-editorial-section--rental"><div class="pk-container"><div class="pk-editorial-rental"><div><p class="pk-editorial-kicker"><?php esc_html_e( 'À louer directement', 'partikulier' ); ?></p><h2><?php esc_html_e( 'Un appartement avec vue sur le large.', 'partikulier' ); ?></h2><p><?php esc_html_e( 'Découvrez les biens qui privilégient la lumière, l’espace et le contact direct.', 'partikulier' ); ?></p><a class="pk-btn pk-btn-dark" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Rechercher un bien', 'partikulier' ); ?> <span aria-hidden="true">→</span></a></div><div class="pk-editorial-rental__shape" aria-hidden="true"></div></div></div></section>

<section class="pk-editorial-section"><div class="pk-container pk-editorial-places"><div><p class="pk-editorial-kicker"><?php esc_html_e( 'Proche de chez vous', 'partikulier' ); ?></p><h2><?php esc_html_e( 'Trouvez un bien dans votre ville.', 'partikulier' ); ?></h2><p><?php esc_html_e( 'Les quartiers et villes sont indexés pour vous aider à trouver plus vite.', 'partikulier' ); ?></p><a class="pk-btn pk-btn-dark" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Explorer les annonces', 'partikulier' ); ?></a></div><div class="pk-editorial-place-list"><?php if ( $pk_home_cities ) : foreach ( $pk_home_cities as $city ) : ?><a href="<?php echo esc_url( pk_term_url( $city, $archive ) ); ?>"><span><?php echo esc_html( $city->name ); ?></span><small><?php echo esc_html( number_format_i18n( $city->count ) ); ?></small><span aria-hidden="true">→</span></a><?php endforeach; else : ?><p class="pk-editorial-empty-text"><?php esc_html_e( 'Les villes apparaîtront dès les premières annonces.', 'partikulier' ); ?></p><?php endif; ?></div></div></section>

<section class="pk-editorial-region-band"><div class="pk-container pk-editorial-region-band__inner"><div><p class="pk-editorial-kicker"><?php esc_html_e( 'Par région', 'partikulier' ); ?></p><h2><?php esc_html_e( 'Partout au Maroc.', 'partikulier' ); ?></h2></div><div class="pk-editorial-region-list"><?php if ( $regions ) : foreach ( $regions as $region ) : ?><a href="<?php echo esc_url( pk_term_url( $region, $archive ) ); ?>"><?php echo esc_html( $region->name ); ?> <span aria-hidden="true">→</span></a><?php endforeach; else : ?><a href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Toutes les annonces', 'partikulier' ); ?> <span aria-hidden="true">→</span></a><?php endif; ?></div></div></section>

<?php get_footer();