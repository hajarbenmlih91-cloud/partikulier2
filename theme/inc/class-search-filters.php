<?php
/**
 * Module : application des filtres de recherche du hero.
 *
 * Le formulaire templates/parts/search-form.php envoyait es_action, es_type,
 * es_city et es_price_max en GET vers l'archive des annonces, mais AUCUN code
 * ne lisait ces parametres : le bouton "Rechercher" renvoyait donc l'archive
 * complete, non filtree.
 *
 * Ce module traduit ces parametres en tax_query / meta_query sur la requete
 * principale. Il accepte indifferemment un slug ou un ID de terme, car Estatik
 * attend des term IDs dans ses propres filtres alors que le formulaire du theme
 * envoie des slugs.
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Partikulier_Search_Filters {

	/**
	 * Correspondance parametre GET -> taxonomie.
	 */
	private static function taxonomy_map() {
		return array(
			'es_action' => PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY,
			'es_type'   => PARTIKULIER_ESTATIK_TYPE_TAXONOMY,
			'es_city'   => PARTIKULIER_ESTATIK_LOCATION_TAXONOMY,
		);
	}

	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_filters' ), 20 );
		add_filter( 'posts_orderby', array( __CLASS__, 'stable_property_order' ), 999, 2 );
	}

	/**
	 * Ajoute un départage SQL déterministe après les réécritures Estatik/Polylang.
	 *
	 * @param string   $orderby Clause ORDER BY courante.
	 * @param WP_Query $query   Requête courante.
	 * @return string
	 */
	public static function stable_property_order( $orderby, $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! self::is_property_query( $query ) ) {
			return $orderby;
		}
		// Les tris explicites du formulaire sont déjà traduits dans WP_Query.
		$requested_order = isset( $_GET['pk_order'] ) ? sanitize_key( wp_unslash( $_GET['pk_order'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $requested_order, array( 'price-asc', 'price-desc', 'surface-desc' ), true ) ) {
			return $orderby;
		}
		global $wpdb;
		$posts = $wpdb->posts;
		// Estatik peut remplacer ORDER BY plus tôt dans la chaîne de hooks.
		// Sans tri explicite, date puis ID fournissent un ordre déterministe.
		return $posts . '.post_date DESC, ' . $posts . '.ID DESC';
	}

	/**
	 * Applique les filtres sur la requete principale des annonces.
	 *
	 * @param WP_Query $query Requete WordPress.
	 */
	public static function apply_filters( $query ) {
		if ( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
			return;
		}

		if ( ! self::is_property_query( $query ) ) {
			return;
		}

		// Les tris explicites du formulaire sont traduits en meta_key/orderby
		// afin d’être réellement appliqués par WP_Query.
		$order = isset( $_GET['pk_order'] ) ? sanitize_key( wp_unslash( $_GET['pk_order'] ) ) : 'recent'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		switch ( $order ) {
			case 'price-asc':
				$query->set( 'meta_key', 'es_property_price' );
				$query->set( 'orderby', array( 'meta_value_num' => 'ASC', 'ID' => 'DESC' ) );
				break;
			case 'price-desc':
				$query->set( 'meta_key', 'es_property_price' );
				$query->set( 'orderby', array( 'meta_value_num' => 'DESC', 'ID' => 'DESC' ) );
				break;
			case 'surface-desc':
				$query->set( 'meta_key', 'es_property_area' );
				$query->set( 'orderby', array( 'meta_value_num' => 'DESC', 'ID' => 'DESC' ) );
				break;
			default:
				if ( ! $query->get( 'orderby' ) || 'date' === $query->get( 'orderby' ) ) {
					$query->set( 'orderby', array( 'date' => 'DESC', 'ID' => 'DESC' ) );
				}
				break;
		}

			// --- Filtres de taxonomie (achat/location, type de bien, ville) ---
			$existing_tax_query = $query->get( 'tax_query' );
			if ( $existing_tax_query instanceof WP_Tax_Query ) {
				$tax_query = $existing_tax_query->queries;
				if ( ! empty( $existing_tax_query->relation ) ) {
					$tax_query['relation'] = $existing_tax_query->relation;
				}
			} else {
				$tax_query = (array) $existing_tax_query;
			}

			$taxonomy_map = self::taxonomy_map();
			$taxonomy_map['location'] = PARTIKULIER_ESTATIK_LOCATION_TAXONOMY;
			foreach ( $taxonomy_map as $param => $taxonomy ) {
			if ( empty( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				continue;
			}
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$raw = sanitize_text_field( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' === $raw ) {
				continue;
			}

				// Le formulaire utilise des slugs stables pour les actions. Si Estatik a un
				// libelle different, retrouver son vrai terme avant de construire la tax_query.
				if ( 'es_action' === $param && in_array( $raw, array( 'a-vendre', 'a-louer' ), true ) ) {
					$needles = 'a-louer' === $raw ? array( 'louer', 'location', 'rent' ) : array( 'vend', 'vente', 'sale' );
					$action_terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
					if ( ! is_wp_error( $action_terms ) ) {
						foreach ( $action_terms as $action_term ) {
							$action_name = function_exists( 'remove_accents' ) ? remove_accents( $action_term->name ) : $action_term->name;
							$action_name = function_exists( 'mb_strtolower' ) ? mb_strtolower( $action_name ) : strtolower( $action_name );
							foreach ( $needles as $needle ) {
								if ( false !== strpos( $action_name, $needle ) ) {
									$raw = $action_term->slug;
									break 2;
								}
							}
						}
					}
				}

				// Le formulaire envoie un slug ; Estatik peut envoyer un term ID.
				$is_id = ctype_digit( $raw );
			$term  = $is_id
				? get_term( (int) $raw, $taxonomy )
				: get_term_by( 'slug', $raw, $taxonomy );

			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy'         => $taxonomy,
				'field'            => 'term_id',
				'terms'            => (int) $term->term_id,
				'include_children' => true,
			);
		}

			// Les routes /[lang]/location/{slug}/ transmettent une query var interne,
			// tandis que les liens de l’interface utilisent ?location={slug}.
			$city_slug = sanitize_title( (string) $query->get( 'pk_city_slug' ) );
			if ( '' === $city_slug && ! empty( $_GET['location'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$city_slug = sanitize_title( wp_unslash( $_GET['location'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			if ( '' !== $city_slug && taxonomy_exists( PARTIKULIER_ESTATIK_LOCATION_TAXONOMY ) ) {
				$city_term = get_term_by( 'slug', $city_slug, PARTIKULIER_ESTATIK_LOCATION_TAXONOMY );
				if ( $city_term && ! is_wp_error( $city_term ) ) {
					$tax_query[] = array(
						'taxonomy'         => PARTIKULIER_ESTATIK_LOCATION_TAXONOMY,
						'field'            => 'term_id',
						'terms'            => (int) $city_term->term_id,
						'include_children' => true,
					);
				}
			}

			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			if ( ! empty( $tax_query ) ) {
				$query->set( 'tax_query', $tax_query );
			}

		// --- Budget maximum ---
		$price_max = isset( $_GET['es_price_max'] ) ? (int) $_GET['es_price_max'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$price_min = isset( $_GET['es_price_min'] ) ? (int) $_GET['es_price_min'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $price_max > 0 || $price_min > 0 ) {
			$meta_query = (array) $query->get( 'meta_query' );

			if ( $price_max > 0 && $price_min > 0 ) {
				$meta_query[] = array(
					'key'     => 'es_property_price',
					'value'   => array( $price_min, $price_max ),
					'type'    => 'NUMERIC',
					'compare' => 'BETWEEN',
				);
			} elseif ( $price_max > 0 ) {
				$meta_query[] = array(
					'key'     => 'es_property_price',
					'value'   => $price_max,
					'type'    => 'NUMERIC',
					'compare' => '<=',
				);
			} else {
				$meta_query[] = array(
					'key'     => 'es_property_price',
					'value'   => $price_min,
					'type'    => 'NUMERIC',
					'compare' => '>=',
				);
			}

			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * La requete porte-t-elle sur les annonces ?
	 *
	 * @param WP_Query $query Requete.
	 * @return bool
	 */
	private static function is_property_query( $query ) {
		$post_type = $query->get( 'post_type' );

		if ( PARTIKULIER_ESTATIK_POST_TYPE === $post_type ) {
			return true;
		}
		if ( is_array( $post_type ) && in_array( PARTIKULIER_ESTATIK_POST_TYPE, $post_type, true ) ) {
			return true;
		}
		if ( $query->is_post_type_archive( PARTIKULIER_ESTATIK_POST_TYPE ) ) {
			return true;
		}
		foreach ( self::taxonomy_map() as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) && $query->is_tax( $taxonomy ) ) {
				return true;
			}
		}
		return false;
	}
}

Partikulier_Search_Filters::init();
