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

		// --- Filtres de taxonomie (achat/location, type de bien, ville) ---
		$tax_query = (array) $query->get( 'tax_query' );

		foreach ( self::taxonomy_map() as $param => $taxonomy ) {
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
