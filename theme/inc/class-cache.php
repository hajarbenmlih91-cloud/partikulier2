<?php
/**
 * Module : cache de page fichier (pas LiteSpeed, pas de plugin cache).
 *
 * - Cache les pages publiques (GET) en fichiers HTML dans uploads/partikulier-cache/
 * - Sert le fichier directement au boot (avant le chargement complet de WP)
 * - Purge : toutes les pages cachees a la modification d'une annonce/terme/page
 * - Headers de compression : on sert le fichier tel quel si le serveur le compresse (mod_deflate/brotli),
 *   sinon on genere aussi une variante .gz
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Partikulier_Cache {

	const TTL        = 43200; // 12 h par defaut
	const DIR_NAME   = 'partikulier-cache';

	/**
	 * Hook lance tres tot : on intercepte avant meme le bootstrap complet.
	 */
	public static function init() {
			// Un thème est chargé après plugins_loaded : after_setup_theme est le
			// premier hook fiable disponible pour servir un cache de thème.
			add_action( 'after_setup_theme', array( __CLASS__, 'maybe_serve_cached' ), -999 );

		// Enregistrement du buffer de sortie pour generer le cache.
		add_action( 'template_redirect', array( __CLASS__, 'start_caching' ), -1 );

		// Hooks de purge.
		foreach ( array( 'save_post', 'delete_post', 'transition_post_status', 'edited_term', 'create_term', 'delete_term', 'switch_theme', 'wp_update_nav_menu', 'customize_save_after' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'purge_all' ) );
		}
			// Purge aussi après une mise à jour du site dans l’admin.
			add_action( 'wp_trash_post', array( __CLASS__, 'purge_all' ) );

			// Les options peuvent être créées via add_option() : le hook
			// update_option_* ne se déclenche alors pas. Les deux familles sont
			// donc obligatoires pour éviter de servir une ancienne home en cache.
			foreach ( array( 'pk_customization_options', 'pk_theme_options' ) as $option ) {
				add_action( 'add_option_' . $option, array( __CLASS__, 'purge_all' ) );
				add_action( 'update_option_' . $option, array( __CLASS__, 'purge_all' ) );
			}
	}

	/**
	 * Le plus tot possible : sert le fichier HTML cache si disponible.
	 */
		public static function maybe_serve_cached() {
			// La racine dépend de la langue/cookie/UA et ne doit jamais être servie
			// depuis le cache fichier partagé.
			if ( self::is_root_request() || ! self::is_cacheable_request() ) {
			return;
		}

		$cache_file = self::cache_path();
		$gz_file    = $cache_file . '.gz';

			if ( file_exists( $cache_file ) && ( time() - filemtime( $cache_file ) ) < self::TTL ) {
				// Le cache court-circuite send_headers : réémettre les défenses HTTP
				// publiques afin qu’une réponse HIT ne soit jamais moins protégée.
				if ( class_exists( 'Partikulier_Security' ) ) {
					Partikulier_Security::send_public_headers();
				}
				header( 'X-Partikulier-Cache: HIT' );
				header( 'Content-Type: text/html; charset=UTF-8' );
				header( 'Cache-Control: public, max-age=' . self::TTL );
				header( 'Vary: Accept-Encoding', false );
			// Compression si le navigateur l'accepte et que la variante existe.
			$accept = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
			if ( false !== strpos( $accept, 'br' ) && function_exists( 'brotli_uncompress' ) && file_exists( $cache_file . '.br' ) ) {
				header( 'Content-Encoding: br' );
				readfile( $cache_file . '.br' );
				exit;
			}
			if ( false !== strpos( $accept, 'gzip' ) && file_exists( $gz_file ) ) {
				header( 'Content-Encoding: gzip' );
				readfile( $gz_file );
				exit;
			}
			readfile( $cache_file );
			exit;
		}
	}

	/**
	 * Demarre la capture de sortie pour generer le fichier cache.
	 */
		public static function start_caching() {
			if ( self::is_root_request() || ! self::is_cacheable_request() ) {
				return;
			}
			// Les variantes localisees sont immuables par URL et restent publiques.
			header( 'Cache-Control: public, max-age=' . self::TTL );
			header( 'Vary: Accept-Encoding', false );
			ob_start( array( __CLASS__, 'store_cache' ) );
	}

	/**
	 * Stocke le HTML dans le fichier de cache (+ variantes compressees).
	 */
	public static function store_cache( $html ) {
		// Ne pas cacher une page avec la barre d'admin ou des erreurs.
		if ( is_admin_bar_showing() || http_response_code() >= 400 || self::response_sets_cookie() || '' === trim( (string) $html ) ) {
			return $html;
		}

		$cache_file = self::cache_path();
		$dir = dirname( $cache_file );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			// Bloquer l'acces direct par .htaccess.
			$htaccess = $dir . '/.htaccess';
			if ( ! file_exists( $htaccess ) ) {
					@file_put_contents( $htaccess, "Require all denied\nDeny from all\n" );
			}
			// Index vide anti-listing.
			@file_put_contents( $dir . '/index.html', '' );
		}

		if ( @file_put_contents( $cache_file, $html ) !== false ) {
			if ( function_exists( 'gzencode' ) ) {
				@file_put_contents( $cache_file . '.gz', gzencode( $html, 5 ) );
			}
			if ( function_exists( 'brotli_compress' ) ) {
				@file_put_contents( $cache_file . '.br', brotli_compress( $html, 5 ) );
			}
		}

		header( 'X-Partikulier-Cache: MISS' );
		return $html;
	}

	/**
	 * Purge tout le repertoire de cache.
	 */
	public static function purge_all() {
		$upload = wp_get_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . self::DIR_NAME;
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = glob( $dir . '/*.html' );
		if ( $files ) {
			foreach ( $files as $f ) {
				@unlink( $f );
				@unlink( $f . '.gz' );
				@unlink( $f . '.br' );
			}
		}
	}

	/**
	 * Exclut les parcours privés et les endpoints techniques dès
	 * after_setup_theme, moment où la requête WordPress n’est pas encore
	 * systématiquement disponible pour is_page().
	 */
	private static function is_private_path() {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		$path = trim( (string) $path, '/' );
		if ( in_array( $path, array( 'sitemap.xml', 'robots.txt', 'xmlrpc.php' ), true ) ) {
			return true;
		}
		// Les pages de dépôt et d’espace personnel peuvent être préfixées par
		// Polylang (`fr/`, `en/`, `ar/`) et ne doivent jamais devenir publiques.
		if ( preg_match( '#(?:^|/)(?:deposer(?:-une-annonce|-annonce|-en|-ar)?|mes-annonces(?:-en|-ar)?)(?:/|$)#', $path ) ) {
			return true;
		}
		return 0 === strpos( $path, 'wp-admin/' ) || 0 === strpos( $path, 'wp-json/' );
	}

	/**
	 * Chemin du fichier cache pour la requete courante.
	 */
	private static function cache_path() {
		$upload = wp_get_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . self::DIR_NAME;

		$host = isset( $_SERVER['HTTP_HOST'] ) ? preg_replace( '/[^a-z0-9.\-]/i', '', strtolower( $_SERVER['HTTP_HOST'] ) ) : 'default';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '/';
		$uri  = '/' === $uri ? 'index' : trim( str_replace( array( '..', '/' ), array( '', '_' ), $uri ), '_' );
		$lang = '';
		if ( defined( 'WPLANG' ) && WPLANG ) {
			$lang = WPLANG . '_';
		}
		return rtrim( $dir, '/' ) . '/' . $lang . $host . '_' . $uri . '.html';
	}

	/**
	 * La requete courante est-elle cachable ?
	 */
			private static function is_root_request() {
			$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
			return '/' === trailingslashit( (string) $path );
		}

		private static function is_cacheable_request() {

			if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) ) {
			return false;
		}
		if ( 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
			return false;
		}
		if ( ! empty( $_GET ) ) {
			return false;
		}
			if ( is_user_logged_in() || ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
				return false;
			}
			if ( self::is_private_path() || is_page( array( 'deposer', 'deposer-en', 'deposer-ar', 'deposer-une-annonce', 'deposer-annonce', 'mes-annonces', 'mes-annonces-en', 'mes-annonces-ar' ) ) ) {
				return false;
			}
		$cookie_header = isset( $_SERVER['HTTP_COOKIE'] ) ? (string) $_SERVER['HTTP_COOKIE'] : '';
		if ( $cookie_header && preg_match( '/(?:wordpress_logged_in|wordpress_sec|wp-postpass|comment_author|pk_v_)=/i', $cookie_header ) ) {
			return false;
		}
		// Ne pas cacher les flux RSS, sitemap deja servi a part.
		if ( is_feed() || is_robots() || is_favicon() ) {
			return false;
		}
		// Les pages de recherche avec parametres ne sont pas cachees (GET non vide deja exclu).
		return true;
	}

	/**
	 * Un HTML qui initialise une session ou un cookie de parcours ne doit jamais
	 * devenir une réponse publique partagée.
	 */
	private static function response_sets_cookie() {
		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'Set-Cookie:' ) ) {
				return true;
			}
		}
		return false;
	}
}

Partikulier_Cache::init();