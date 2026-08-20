<?php
/**
 * Module : URL des annonces incluant la ville et le quartier.
 *
 * Avant :  /property/studio-calme-proche-des-gares/
 * Apres :  /annonce/casablanca/maarif/studio-calme-proche-des-gares/
 *
 * La geographie est ce que les gens tapent dans Google (« appartement a
 * vendre Maarif Casablanca »). La faire figurer dans le chemin de l'URL
 * concentre le signal sur la page plutot que de le diluer.
 *
 * Les anciennes adresses sont redirigees en 301 : aucune position acquise
 * n'est perdue, et les liens deja partages continuent de fonctionner.
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Partikulier_Listing_URLs {

	/**
	 * Base du permalien. « annonce » parle francais, contrairement a
	 * « property » herite du plugin.
	 */
	const BASE = 'annonce';

	/**
	 * Version des regles : incrementer force une regeneration unique.
	 */
	const RULES_VERSION = '3';

	const META_CITY     = '_pk_url_city';
	const META_DISTRICT = '_pk_url_district';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rules' ), 20 );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_link' ), 10, 2 );
		add_action( 'parse_request', array( __CLASS__, 'redirect_legacy_early' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_legacy' ), 1 );

		// La geographie est figee a l'enregistrement : une URL ne doit pas
		// changer parce qu'un terme a ete renomme trois mois plus tard.
		add_action( 'save_post_' . PARTIKULIER_ESTATIK_POST_TYPE, array( __CLASS__, 'store_geo' ), 20, 3 );

		add_action( 'admin_init', array( __CLASS__, 'maybe_flush' ) );
		add_action( 'after_switch_theme', array( __CLASS__, 'flush' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Geographie figee sur l'annonce                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * Enregistre ville et quartier sous forme de slugs stables.
	 *
	 * @param int     $post_id Annonce.
	 * @param WP_Post $post    Objet.
	 * @param bool    $update  Mise a jour.
	 */
	public static function store_geo( $post_id, $post = null, $update = false ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$geo = self::resolve_geo( $post_id );

		if ( $geo['city'] ) {
			update_post_meta( $post_id, self::META_CITY, $geo['city'] );
		}
		if ( $geo['district'] ) {
			update_post_meta( $post_id, self::META_DISTRICT, $geo['district'] );
		} else {
			delete_post_meta( $post_id, self::META_DISTRICT );
		}
	}

	/**
	 * Determine ville et quartier d'une annonce.
	 *
	 * es_location est une taxonomie plate : un terme peut aussi bien etre
	 * « Casablanca » que « Maarif ». On s'appuie donc sur le referentiel
	 * pour savoir de quelle ville depend un quartier.
	 *
	 * @param int $post_id Annonce.
	 * @return array{city:string,district:string}
	 */
	public static function resolve_geo( $post_id ) {
		$city     = '';
		$district = '';

		// 1. Ce que le formulaire de depot a saisi fait foi.
		$meta_city     = (string) get_post_meta( $post_id, '_pk_city_name', true );
		$meta_district = (string) get_post_meta( $post_id, '_pk_district_name', true );

		if ( $meta_city ) {
			$city = $meta_city;
		}
		if ( $meta_district ) {
			$district = $meta_district;
		}

		// 2. Sinon, on lit les termes de localisation.
		if ( ! $city && ! $district ) {
			$terms = wp_get_post_terms( $post_id, PARTIKULIER_ESTATIK_LOCATION_TAXONOMY );
			if ( ! is_wp_error( $terms ) && $terms ) {
				$reference = class_exists( 'Partikulier_Morocco_Places' ) ? Partikulier_Morocco_Places::reference() : array();

				foreach ( $terms as $term ) {
					$parent_city = self::city_of_district( $term->name, $reference );
					if ( $parent_city ) {
						$district = $term->name;
						$city     = $parent_city;
						break;
					}
					if ( isset( $reference[ $term->name ] ) ) {
						$city = $term->name;
					}
				}

				// Terme inconnu du referentiel : il tient lieu de ville.
				if ( ! $city && ! $district ) {
					$city = $terms[0]->name;
				}
			}
		}

		// Un quartier sans ville identifiee remonte au rang de ville : mieux
		// vaut une URL courte qu'une URL bancale.
		if ( ! $city && $district ) {
			$city     = $district;
			$district = '';
		}

		return array(
			'city'     => $city ? sanitize_title( self::latinize( $city ) ) : '',
			'district' => $district ? sanitize_title( self::latinize( $district ) ) : '',
		);
	}

	/**
	 * Ville a laquelle appartient un quartier, selon le referentiel.
	 *
	 * @param string $name      Nom du quartier.
	 * @param array  $reference Referentiel ville => quartiers.
	 * @return string
	 */
	private static function city_of_district( $name, $reference ) {
		$want = class_exists( 'Partikulier_Morocco_Places' )
			? Partikulier_Morocco_Places::normalize( $name )
			: strtolower( $name );

		foreach ( $reference as $city => $districts ) {
			// Un nom de ville n'est pas un quartier.
			if ( class_exists( 'Partikulier_Morocco_Places' ) && Partikulier_Morocco_Places::normalize( $city ) === $want ) {
				return '';
			}
			foreach ( $districts as $district ) {
				$compare = class_exists( 'Partikulier_Morocco_Places' )
					? Partikulier_Morocco_Places::normalize( $district )
					: strtolower( $district );
				if ( $compare === $want ) {
					return $city;
				}
			}
		}

		return '';
	}

	/**
	 * Translittere les accents et l'arabe residuel.
	 *
	 * « Tétouan » doit donner « tetouan », pas « ttouan ».
	 *
	 * @param string $value Valeur.
	 * @return string
	 */
	private static function latinize( $value ) {
		$value = remove_accents( (string) $value );
		$value = str_replace( array( '’', "'", '`' ), '-', $value );

		return $value;
	}

	/* ------------------------------------------------------------------ */
	/* Construction du lien                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Remplace le permalien par sa version geographique.
	 *
	 * @param string  $link Lien.
	 * @param WP_Post $post Annonce.
	 * @return string
	 */
	public static function filter_link( $link, $post ) {
		if ( ! $post || PARTIKULIER_ESTATIK_POST_TYPE !== $post->post_type ) {
			return $link;
		}

		// Un brouillon n'a pas encore de slug definitif.
		if ( in_array( $post->post_status, array( 'draft', 'auto-draft', 'pending' ), true ) && ! $post->post_name ) {
			return $link;
		}

		$path = self::path_for( $post->ID );
		if ( ! $path ) {
			return $link;
		}

		return home_url( user_trailingslashit( $path . '/' . $post->post_name ) );
	}

	/**
	 * Chemin geographique d'une annonce, sans le slug final.
	 *
	 * @param int $post_id Annonce.
	 * @return string Ex. « annonce/casablanca/maarif », vide si inconnu.
	 */
	public static function path_for( $post_id ) {
		$city = (string) get_post_meta( $post_id, self::META_CITY, true );

		if ( ! $city ) {
			$geo  = self::resolve_geo( $post_id );
			$city = $geo['city'];
			if ( $city ) {
				update_post_meta( $post_id, self::META_CITY, $city );
				if ( $geo['district'] ) {
					update_post_meta( $post_id, self::META_DISTRICT, $geo['district'] );
				}
			}
		}

		if ( ! $city ) {
			return '';
		}

		$district = (string) get_post_meta( $post_id, self::META_DISTRICT, true );
		$path     = self::BASE . '/' . $city;

		if ( $district && $district !== $city ) {
			$path .= '/' . $district;
		}

		return $path;
	}

	/* ------------------------------------------------------------------ */
	/* Regles de reecriture                                                */
	/* ------------------------------------------------------------------ */

		public static function add_rules() {
			$base = self::BASE;
			$cpt  = PARTIKULIER_ESTATIK_POST_TYPE;

			// L’archive publique est francisée ; les anciennes URLs restent récupérables.
			add_rewrite_rule( '^property/page/([0-9]+)/?$', 'index.php?post_type=' . $cpt . '&paged=$matches[1]', 'top' );
			add_rewrite_rule( '^property/?$', 'index.php?post_type=' . $cpt, 'top' );

		// Ville + quartier + annonce.
		add_rewrite_rule(
			'^' . $base . '/[^/]+/[^/]+/([^/]+)/?$',
			'index.php?post_type=' . $cpt . '&name=$matches[1]',
			'top'
		);

		// Ville + annonce (quartier absent).
		add_rewrite_rule(
			'^' . $base . '/[^/]+/([^/]+)/?$',
			'index.php?post_type=' . $cpt . '&name=$matches[1]',
			'top'
		);
	}

	/**
	 * Regenere les regles une seule fois apres mise a jour.
	 */
	public static function maybe_flush() {
		if ( get_option( 'pk_url_rules_version' ) === self::RULES_VERSION ) {
			return;
		}

		self::flush();
	}

	public static function flush() {
		self::add_rules();
		flush_rewrite_rules( false );
		update_option( 'pk_url_rules_version', self::RULES_VERSION );
	}

	/* ------------------------------------------------------------------ */
	/* Redirections 301                                                    */
	/* ------------------------------------------------------------------ */

	/**
	 * Redirige toute ancienne adresse vers l'URL geographique.
	 *
	 * Sans cela, Google conserverait en index des adresses qui repondent
	 * encore 200 : deux URL pour un meme bien, donc un signal divise.
	 */
	/**
	 * Intercepte les anciennes archives avant qu'une page vide ne devienne 404.
	 *
	 * @param WP $wp Requête WordPress.
	 */
	public static function redirect_legacy_early( $wp ) {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
		if ( ! preg_match( '#/property(?:/page/([0-9]+))?/?$#', (string) $request_path, $legacy_match ) ) {
			return;
		}
		$paged = ! empty( $legacy_match[1] ) ? (int) $legacy_match[1] : 1;
		$target = pk_properties_archive_url();
		if ( $paged > 1 ) {
			$target = trailingslashit( $target ) . 'page/' . $paged . '/';
		}
		wp_safe_redirect( $target, 301 );
		exit;
	}

	public static function redirect_legacy() {
			if ( is_admin() || wp_doing_ajax() ) {
				return;
			}

			// L’ancien endpoint d’archive doit répondre en 301, y compris sa pagination.
			$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
			if ( preg_match( '#/property(?:/page/([0-9]+))?/?$#', (string) $request_path, $legacy_match ) ) {
				$paged = ! empty( $legacy_match[1] ) ? (int) $legacy_match[1] : max( 1, (int) get_query_var( 'paged' ) );
				$target = pk_properties_archive_url();
				if ( $paged > 1 ) {
					$target = trailingslashit( $target ) . 'page/' . $paged . '/';
				}
				wp_safe_redirect( $target, 301 );
				exit;
			}

			if ( ! is_singular( PARTIKULIER_ESTATIK_POST_TYPE ) ) {
				return;
			}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$target = get_permalink( $post_id );
		if ( ! $target ) {
			return;
		}

		$current = wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH );
		$wanted  = wp_parse_url( $target, PHP_URL_PATH );

		if ( ! $current || ! $wanted ) {
			return;
		}

		if ( untrailingslashit( $current ) === untrailingslashit( $wanted ) ) {
			return;
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}
}

Partikulier_Listing_URLs::init();
