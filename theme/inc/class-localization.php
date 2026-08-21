<?php
/**
 * Registre de préparation des variantes localisées Partikulier.
 *
 * Ce module relie « une annonce métier × variantes SEO » à Polylang lorsqu’il
 * est actif. Le texte libre du propriétaire reste dans sa langue d’origine et
 * n’est jamais traité comme une traduction éditoriale officielle.
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class Partikulier_Localization {

	const DB_VERSION = '1.0.0';
	const OPTION_DB_VERSION = 'pk_localization_db_version';
	const OPTION_PUBLIC_ENABLED = 'pk_localization_public_enabled';
	const STATUS_PREPARED = 'prepared';
	const META_FREE_TEXT_LANGUAGE = '_pk_free_text_language';

	public static function init() {
			add_action( 'init', array( __CLASS__, 'load_textdomain' ), 5 );
			add_action( 'wp', array( __CLASS__, 'load_active_textdomain' ), 1 );
			add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_browser_language' ), 1 );
			add_action( 'init', array( __CLASS__, 'maybe_install' ), 6 );
			add_action( 'admin_init', array( __CLASS__, 'register_polylang_strings' ) );
		add_filter( 'gettext_partikulier', array( __CLASS__, 'translate_polylang_string' ), 10, 3 );
		add_filter( 'pll_get_post_types', array( __CLASS__, 'register_polylang_post_type' ), 10, 2 );
			add_filter( 'pll_get_taxonomies', array( __CLASS__, 'register_polylang_taxonomies' ), 10, 2 );
			add_filter( 'pll_preferred_language', array( __CLASS__, 'filter_robot_preferred_language' ), 10, 2 );
	}

			/**
		 * Charge les fichiers gettext du thème avant tout dictionnaire de repli.
		 */
		public static function load_textdomain() {
				load_theme_textdomain( 'partikulier', PARTIKULIER_DIR . '/languages' );
			}

			/**
			 * Polylang peut definir son slug actif apres le chargement initial de WP.
			 * On recharge alors le fichier theme correspondant au slug public.
			 */
			public static function load_active_textdomain() {
				$slug = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
				$locale = 'en' === $slug ? 'en_US' : ( 'ar' === $slug ? 'ar' : '' );
				if ( ! $locale ) {
					return;
				}
				$file = trailingslashit( PARTIKULIER_DIR ) . 'languages/' . $locale . '.mo';
				if ( is_readable( $file ) ) {
					load_textdomain( 'partikulier', $file );
				}
			}

		/**
		 * Redirige uniquement la première visite humaine de la racine selon
		 * Accept-Language lorsque Polylang browser detection est activée.
		 */
		public static function maybe_redirect_browser_language() {
			if ( ! function_exists( 'pll_home_url' ) || ! self::is_root_request() || is_user_logged_in() ) {
				return;
			}
			$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
			if ( '/' !== trailingslashit( (string) $path ) || self::is_robot_request() ) {
				return;
			}
			$browser_enabled = function_exists( 'pll_get_option' ) ? pll_get_option( 'browser' ) : false;
			if ( ! $browser_enabled || ! empty( $_COOKIE['pll_language'] ) ) {
				return;
			}
			$accept = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? strtolower( (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) : '';
			$lang = false;
			if ( preg_match( '/(?:^|,)\\s*ar(?:[-_][a-z]+)?(?:\\s*;|,|$)/i', $accept ) ) {
				$lang = 'ar';
			} elseif ( preg_match( '/(?:^|,)\\s*en(?:[-_][a-z]+)?(?:\\s*;|,|$)/i', $accept ) ) {
				$lang = 'en';
			}
			if ( ! $lang ) {
				return;
			}
			$languages = function_exists( 'pll_languages_list' ) ? pll_languages_list() : array();
			if ( ! in_array( $lang, array_map( 'sanitize_key', (array) $languages ), true ) ) {
				return;
			}
			wp_safe_redirect( pll_home_url( $lang ), 302 );
			exit;
		}

		private static function is_root_request() {
			$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
			return '/' === trailingslashit( (string) $path );
		}

		/**
		 * Polylang exécute sa redirection browser avant template_redirect.
		 * Les robots doivent donc être neutralisés sur son filtre officiel, sinon
		 * l’exemption locale arrive trop tard et produit un cloaking par langue.
		 *
		 * @param string|false $language Langue préférée détectée.
		 * @param bool         $cookie   Préférence issue d’un cookie.
		 * @return string|false
		 */
		public static function filter_robot_preferred_language( $language, $cookie ) {
			unset( $cookie );
			if ( ! self::is_robot_request() ) {
				return $language;
			}
			$default = function_exists( 'pll_default_language' ) ? pll_default_language( 'slug' ) : '';
			return $default ? sanitize_key( $default ) : $language;
		}

		private static function is_robot_request() {
			$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) $_SERVER['HTTP_USER_AGENT'] ) : '';
			return '' !== $ua && (bool) preg_match( '/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|linkedinbot|whatsapp/i', $ua );
		}

		/**
		 * Registre minimal du chrome commun. Les templates continuent d’utiliser

	 * gettext : Polylang gratuit fournit les traductions sans nouvelle UI.
	 *
	 * @return array<string,string>
	 */
	public static function public_chrome_strings() {
		$strings = array(
			'property'                 => 'Bien',
			'features'                 => 'Caractéristiques',
			'description'              => 'Description',
			'contact_seller'           => 'Contacter le vendeur',
			'free_private_listing'     => 'Annonce gratuite publiée par un particulier. Aucun frais d’agence.',
			'owner'                    => 'Propriétaire',
			'agent'                    => 'Propriétaire',
			'whatsapp_request'         => 'Demander le contact sur WhatsApp',
			'whatsapp_flow'            => 'Envoyez-nous cette annonce sur WhatsApp. Après contrôle de votre demande, nous vous transmettons les coordonnées du propriétaire.',
			'city_listings'            => 'Voir les autres annonces dans cette ville',
			'photos'                   => 'Photos du bien',
			'photo_navigation'         => 'Navigation des photos',
			'previous_photo'           => 'Photo précédente',
			'next_photo'               => 'Photo suivante',
			'surface'                  => 'Surface',
			'bedrooms'                 => 'Chambres',
			'living_rooms'             => 'Salons',
			'bathrooms'                => 'Salles de bains',
			'terrace'                  => 'Terrasse',
			'view'                     => 'Vue',
			'sunshine'                 => 'Ensoleillement',
			'parking'                  => 'Parkings',
			'floor'                    => 'Étage',
			'year_built'               => 'Année de construction',
			'energy_class'             => 'Classe énergie',
			'yes'                      => 'Oui',
			'no'                       => 'Non',
			'studio'                   => 'Studio',
			'main_room'                => 'Pièce principale',
			'three_bedrooms_plus'     => '3 chambres ou plus',
			'three_living_rooms_plus' => '3 salons ou plus',
			'three_bathrooms_plus'    => '3 salles de bains ou plus',
			'contact_unavailable'      => 'Le contact WhatsApp sera bientôt disponible pour cette annonce.',
			'closed_no_contact'        => 'Cette annonce ne reçoit plus de contacts.',
			'features'                 => 'Caractéristiques',
			'contact_via_whatsapp'     => 'Demander le contact sur WhatsApp',
			'direct_contact'           => 'Contact direct',
			'skip_to_content'          => 'Aller au contenu',
			'post_listing'             => 'Déposer une annonce',
			'all_listings'             => 'Toutes les annonces',
			'my_space'                 => 'Mon espace',
			'sign_in'                  => 'Se connecter',
			'quick_search'             => 'Recherche rapide',
			'property_type'            => 'Type de bien',
			'city_postcode_area'       => 'Ville, code postal, quartier…',
			'search_city'              => 'Rechercher une ville',
			'search'                   => 'Rechercher',
			'favorites'                => 'Favoris',
			'open_menu'                => 'Ouvrir le menu',
			'main_menu'                => 'Menu principal',
			'about'                    => 'À propos',
			'help'                     => 'Aide',
			'faq'                      => 'Questions fréquentes',
			'contact_us'               => 'Contactez-nous',
			'property_types'           => 'Types de biens',
			'contact'                  => 'Contact',
			'country'                  => 'Maroc',
			'all_rights_reserved'      => 'Tous droits réservés.',
			'legal_notices'            => 'Mentions légales',
			'bedroom'                  => 'chambre',
			'bedrooms_plural'          => 'chambres',
			'living_room'              => 'salon',
			'living_rooms_plural'      => 'salons',
			'bathroom'                 => 'salle de bains',
			'bathrooms_plural'         => 'salles de bains',
		);

		if ( class_exists( 'Partikulier_Settings' ) ) {
			foreach ( Partikulier_Settings::fields() as $group ) {
				foreach ( $group['fields'] as $key => $field ) {
					if ( empty( $field['type'] ) || 'password' !== $field['type'] ) {
						$strings[ 'setting_' . $key ] = $field['default'];
					}
				}
			}
		}

		return $strings;
	}

	/**
	 * Traduit une chaîne publique explicitement enregistrée, sinon conserve la
	 * valeur d’origine. Cela protège les textes libres et les réglages personnalisés.
	 */
	public static function translate_public_string( $string ) {
		if ( ! function_exists( 'pll__' ) || ! in_array( $string, self::public_chrome_strings(), true ) ) {
			return $string;
		}

		return pll__( $string );
	}

	/**
	 * Inscrit les chaînes dans l’outil natif de Polylang gratuit, côté
	 * administration uniquement, conformément à son API publique.
	 */
	public static function register_polylang_strings() {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		foreach ( self::public_chrome_strings() as $name => $string ) {
			pll_register_string( 'Partikulier · ' . $name, $string, 'Partikulier' );
		}
	}

	/**
	 * Traduit uniquement les chaînes explicitement enregistrées. Toute chaîne
	 * non préparée et tout contenu propriétaire conserve son texte d’origine.
	 */
	public static function translate_polylang_string( $translation, $text, $domain ) {
		if ( 'partikulier' !== $domain ) {
			return $translation;
		}

				// Une traduction gettext provenant d’un fichier .mo est canonique.
				// Les dictionnaires internes ne servent qu’en repli.
				if ( $translation !== $text && '' !== $translation ) {
					return $translation;
				}

				$form_translations = self::form_translations();
				if ( isset( $form_translations[ $text ] ) ) {
				$language = self::current_language();
				return isset( $form_translations[ $text ][ $language ] ) ? $form_translations[ $text ][ $language ] : $text;
			}

			$chrome_translations = self::chrome_translations();
			if ( isset( $chrome_translations[ $text ] ) ) {
				$language = self::current_language();
				return isset( $chrome_translations[ $text ][ $language ] ) ? $chrome_translations[ $text ][ $language ] : $text;
			}

			if ( ! function_exists( 'pll__' ) || ! in_array( $text, self::public_chrome_strings(), true ) ) {
			return $translation;
		}

		return self::translate_public_string( $text );
	}

		/**
		 * Repli déterministe des chaînes du shell public lorsque Polylang n’a pas
		 * encore reçu leurs traductions dans l’administration.
		 *
		 * @return array<string,array<string,string>>
		 */
		public static function chrome_translations() {
			return array(
				'Aller au contenu' => array( 'fr' => 'Aller au contenu', 'en' => 'Skip to content', 'ar' => 'انتقل إلى المحتوى' ),
				'Déposer une annonce' => array( 'fr' => 'Déposer une annonce', 'en' => 'Post an ad', 'ar' => 'أضف إعلانك' ),
				'Toutes les annonces' => array( 'fr' => 'Toutes les annonces', 'en' => 'All listings', 'ar' => 'كل الإعلانات' ),
				'Mon espace' => array( 'fr' => 'Mon espace', 'en' => 'My account', 'ar' => 'مساحتي' ),
				'Se connecter' => array( 'fr' => 'Se connecter', 'en' => 'Sign in', 'ar' => 'تسجيل الدخول' ),
				'Recherche rapide' => array( 'fr' => 'Recherche rapide', 'en' => 'Quick search', 'ar' => 'بحث سريع' ),
				'Type de bien' => array( 'fr' => 'Type de bien', 'en' => 'Property type', 'ar' => 'نوع العقار' ),
				'Ville, code postal, quartier…' => array( 'fr' => 'Ville, code postal, quartier…', 'en' => 'City, postcode, neighbourhood…', 'ar' => 'المدينة، الرمز البريدي، الحي…' ),
				'Rechercher une ville' => array( 'fr' => 'Rechercher une ville', 'en' => 'Search for a city', 'ar' => 'ابحث عن مدينة' ),
				'Rechercher' => array( 'fr' => 'Rechercher', 'en' => 'Search', 'ar' => 'بحث' ),
				'Favoris' => array( 'fr' => 'Favoris', 'en' => 'Favorites', 'ar' => 'المفضلة' ),
				'Ouvrir le menu' => array( 'fr' => 'Ouvrir le menu', 'en' => 'Open menu', 'ar' => 'فتح القائمة' ),
				'Menu principal' => array( 'fr' => 'Menu principal', 'en' => 'Main menu', 'ar' => 'القائمة الرئيسية' ),
				'Choisir la langue' => array( 'fr' => 'Choisir la langue', 'en' => 'Choose language', 'ar' => 'اختر اللغة' ),
				'À propos' => array( 'fr' => 'À propos', 'en' => 'About', 'ar' => 'عن الموقع' ),
				'Aide' => array( 'fr' => 'Aide', 'en' => 'Help', 'ar' => 'مساعدة' ),
				'Questions fréquentes' => array( 'fr' => 'Questions fréquentes', 'en' => 'Frequently asked questions', 'ar' => 'الأسئلة الشائعة' ),
				'Contactez-nous' => array( 'fr' => 'Contactez-nous', 'en' => 'Contact us', 'ar' => 'اتصل بنا' ),
				'Types de biens' => array( 'fr' => 'Types de biens', 'en' => 'Property types', 'ar' => 'أنواع العقارات' ),
				'Contact' => array( 'fr' => 'Contact', 'en' => 'Contact', 'ar' => 'اتصل بنا' ),
				'Maroc' => array( 'fr' => 'Maroc', 'en' => 'Morocco', 'ar' => 'المغرب' ),
				'Tous droits réservés.' => array( 'fr' => 'Tous droits réservés.', 'en' => 'All rights reserved.', 'ar' => 'جميع الحقوق محفوظة.' ),
				'Mentions légales' => array( 'fr' => 'Mentions légales', 'en' => 'Legal notices', 'ar' => 'الإشعارات القانونية' ),
			);
		}

		/**
		 * Libellés du formulaire de dépôt utilisés comme repli déterministe lorsque
		 * Polylang n’a pas encore de traduction enregistrée pour une chaîne.
		 * Le texte libre saisi par le propriétaire n’est jamais traduit ici.
		 *
		 * @return array<string,array<string,string>>
		 */
		public static function form_translations() {
		return array(
			'Titre de l’annonce *' => array( 'fr' => 'Titre de l’annonce *', 'en' => 'Listing title *', 'ar' => 'عنوان الإعلان *' ),
			'Ex : Appartement lumineux 3 pièces avec balcon' => array( 'fr' => 'Ex : Appartement lumineux 3 pièces avec balcon', 'en' => 'E.g. Bright 3-bedroom apartment with balcony', 'ar' => 'مثال: شقة مشرقة بثلاث غرف مع شرفة' ),
			'Vous souhaitez' => array( 'fr' => 'Vous souhaitez', 'en' => 'You want to', 'ar' => 'أرغب في' ),
			'Type de bien' => array( 'fr' => 'Type de bien', 'en' => 'Property type', 'ar' => 'نوع العقار' ),
			'Prix (€)' => array( 'fr' => 'Prix (€)', 'en' => 'Price (€)', 'ar' => 'السعر (€)' ),
			'Ex : 245000' => array( 'fr' => 'Ex : 245000', 'en' => 'E.g. 245000', 'ar' => 'مثال: 245000' ),
			'Ville' => array( 'fr' => 'Ville', 'en' => 'City', 'ar' => 'المدينة' ),
			'Choisir une ville…' => array( 'fr' => 'Choisir une ville…', 'en' => 'Choose a city…', 'ar' => 'اختر مدينة…' ),
			'Surface (m²)' => array( 'fr' => 'Surface (m²)', 'en' => 'Area (m²)', 'ar' => 'المساحة (م²)' ),
			'Nombre de chambres' => array( 'fr' => 'Nombre de chambres', 'en' => 'Bedrooms', 'ar' => 'عدد غرف النوم' ),
			'Choisir…' => array( 'fr' => 'Choisir…', 'en' => 'Choose…', 'ar' => 'اختر…' ),
			'Studio / 0 chambre' => array( 'fr' => 'Studio / 0 chambre', 'en' => 'Studio / 0 bedrooms', 'ar' => 'استوديو / دون غرفة نوم' ),
			'1 chambre' => array( 'fr' => '1 chambre', 'en' => '1 bedroom', 'ar' => 'غرفة نوم واحدة' ),
			'2 chambres' => array( 'fr' => '2 chambres', 'en' => '2 bedrooms', 'ar' => 'غرفتا نوم' ),
			'3 chambres ou plus' => array( 'fr' => '3 chambres ou plus', 'en' => '3 bedrooms or more', 'ar' => '3 غرف نوم أو أكثر' ),
			'Nombre de salons' => array( 'fr' => 'Nombre de salons', 'en' => 'Living rooms', 'ar' => 'عدد غرف الجلوس' ),
			'0 salon — pièce principale' => array( 'fr' => '0 salon — pièce principale', 'en' => '0 living rooms — main room', 'ar' => 'دون غرفة جلوس — الغرفة الرئيسية' ),
			'1 salon' => array( 'fr' => '1 salon', 'en' => '1 living room', 'ar' => 'غرفة جلوس واحدة' ),
			'2 salons' => array( 'fr' => '2 salons', 'en' => '2 living rooms', 'ar' => 'غرفتا جلوس' ),
			'3 salons ou plus' => array( 'fr' => '3 salons ou plus', 'en' => '3 living rooms or more', 'ar' => '3 غرف جلوس أو أكثر' ),
			'Nombre de salles de bains' => array( 'fr' => 'Nombre de salles de bains', 'en' => 'Bathrooms', 'ar' => 'عدد الحمامات' ),
			'1 salle de bains' => array( 'fr' => '1 salle de bains', 'en' => '1 bathroom', 'ar' => 'حمام واحد' ),
			'2 salles de bains' => array( 'fr' => '2 salles de bains', 'en' => '2 bathrooms', 'ar' => 'حمامان' ),
			'3 salles de bains ou plus' => array( 'fr' => '3 salles de bains ou plus', 'en' => '3 bathrooms or more', 'ar' => '3 حمامات أو أكثر' ),
			'Terrasse' => array( 'fr' => 'Terrasse', 'en' => 'Terrace', 'ar' => 'شرفة' ),
			'Non' => array( 'fr' => 'Non', 'en' => 'No', 'ar' => 'لا' ),
			'Oui' => array( 'fr' => 'Oui', 'en' => 'Yes', 'ar' => 'نعم' ),
			'Superficie de la terrasse (m²)' => array( 'fr' => 'Superficie de la terrasse (m²)', 'en' => 'Terrace area (m²)', 'ar' => 'مساحة الشرفة (م²)' ),
			'Sans vis-à-vis' => array( 'fr' => 'Sans vis-à-vis', 'en' => 'No overlooking neighbours', 'ar' => 'بدون إطلالة مقابلة' ),
			'Ensoleillement' => array( 'fr' => 'Ensoleillement', 'en' => 'Sunlight', 'ar' => 'التعرض للشمس' ),
			'Ensoleillé le matin' => array( 'fr' => 'Ensoleillé le matin', 'en' => 'Morning sun', 'ar' => 'مشمس صباحاً' ),
			'Ensoleillé l’après-midi' => array( 'fr' => 'Ensoleillé l’après-midi', 'en' => 'Afternoon sun', 'ar' => 'مشمس بعد الظهر' ),
			'Toute la journée' => array( 'fr' => 'Toute la journée', 'en' => 'All day', 'ar' => 'طوال اليوم' ),
			'Très peu' => array( 'fr' => 'Très peu', 'en' => 'Very little', 'ar' => 'قليل جداً' ),
			'Description *' => array( 'fr' => 'Description *', 'en' => 'Description *', 'ar' => 'الوصف *' ),
			'Décrivez votre bien : état, atouts, quartier, transports…' => array( 'fr' => 'Décrivez votre bien : état, atouts, quartier, transports…', 'en' => 'Describe your property: condition, features, neighbourhood, transport…', 'ar' => 'صف عقارك: حالته، مزاياه، الحي ووسائل النقل…' ),
			'Vos photos' => array( 'fr' => 'Vos photos', 'en' => 'Your photos', 'ar' => 'صورك' ),
			'Glissez vos photos ici' => array( 'fr' => 'Glissez vos photos ici', 'en' => 'Drag your photos here', 'ar' => 'اسحب صورك إلى هنا' ),
			'ou cliquez pour parcourir (15 photos maximum, JPG/PNG)' => array( 'fr' => 'ou cliquez pour parcourir (15 photos maximum, JPG/PNG)', 'en' => 'or click to browse (15 photos maximum, JPG/PNG)', 'ar' => 'أو اضغط للتصفح (15 صورة كحد أقصى، JPG/PNG)' ),
			'Vous (simple et rapide)' => array( 'fr' => 'Vous (simple et rapide)', 'en' => 'You (quick and easy)', 'ar' => 'بياناتك (بسهولة وسرعة)' ),
			'Votre nom *' => array( 'fr' => 'Votre nom *', 'en' => 'Your name *', 'ar' => 'اسمك *' ),
			'Jean Dupont' => array( 'fr' => 'Jean Dupont', 'en' => 'John Smith', 'ar' => 'محمد العلوي' ),
			'E-mail' => array( 'fr' => 'E-mail', 'en' => 'Email', 'ar' => 'البريد الإلكتروني' ),
			'vous@exemple.com' => array( 'fr' => 'vous@exemple.com', 'en' => 'you@example.com', 'ar' => 'you@example.com' ),
			'Téléphone' => array( 'fr' => 'Téléphone', 'en' => 'Phone', 'ar' => 'الهاتف' ),
			'06 12 34 56 78' => array( 'fr' => '06 12 34 56 78', 'en' => '+1 555 123 4567', 'ar' => '06 12 34 56 78' ),
			'Votre téléphone est requis pour demander la validation via WhatsApp. Votre e-mail reste facultatif.' => array( 'fr' => 'Votre téléphone est requis pour demander la validation via WhatsApp. Votre e-mail reste facultatif.', 'en' => 'Your phone number is required for WhatsApp validation. Email is optional.', 'ar' => 'رقم الهاتف مطلوب لطلب التحقق عبر واتساب. البريد الإلكتروني اختياري.' ),
			'Qui êtes-vous ?' => array( 'fr' => 'Qui êtes-vous ?', 'en' => 'Who are you?', 'ar' => 'من أنت؟' ),
			'Vous êtes' => array( 'fr' => 'Vous êtes', 'en' => 'You are', 'ar' => 'أنت' ),
			'Propriétaire' => array( 'fr' => 'Propriétaire', 'en' => 'Owner', 'ar' => 'مالك' ),
			'Je vends ou loue mon bien' => array( 'fr' => 'Je vends ou loue mon bien', 'en' => 'I am selling or renting my property', 'ar' => 'أبيع أو أؤجر عقاري' ),
			'Propriétaire' => array( 'fr' => 'Propriétaire', 'en' => 'Owner', 'ar' => 'المالك' ),
			'Je gère pour quelqu’un' => array( 'fr' => 'Je gère pour quelqu’un', 'en' => 'I manage a property for someone else', 'ar' => 'أدير عقاراً لشخص آخر' ),
			'Après l’enregistrement, vous enverrez un message WhatsApp prérempli. L’équipe vérifiera ce message avant de mettre l’annonce en ligne.' => array( 'fr' => 'Après l’enregistrement, vous enverrez un message WhatsApp prérempli. L’équipe vérifiera ce message avant de mettre l’annonce en ligne.', 'en' => 'After submission, you will send a pre-filled WhatsApp message. Our team will review it before publishing the listing.', 'ar' => 'بعد الإرسال، سترسل رسالة واتساب جاهزة. سيتحقق فريقنا منها قبل نشر الإعلان.' ),
			'Demander la validation WhatsApp' => array( 'fr' => 'Demander la validation WhatsApp', 'en' => 'Request WhatsApp validation', 'ar' => 'طلب التحقق عبر واتساب' ),
		);
	}

	public static function current_language() {
		if ( function_exists( 'pll_current_language' ) ) {
			$language = pll_current_language( 'slug' );
			if ( in_array( $language, self::supported_locales(), true ) ) {
				return $language;
			}
		}
		return 'fr';
	}

	/**
	 * Déclare le post type Estatik auprès de Polylang lorsqu’il est présent.
	 * Le second appel du filtre avec $is_settings à false le maintient actif sans
	 * dépendre d’un réglage administrateur mutable.
	 */
	public static function register_polylang_post_type( $post_types, $is_settings ) {
		if ( ! defined( 'POLYLANG_VERSION' ) ) {
			return $post_types;
		}

		$post_types[] = PARTIKULIER_ESTATIK_POST_TYPE;
		return array_values( array_unique( $post_types ) );
	}

	/**
	 * Déclare uniquement les taxonomies Estatik utilisées par le thème.
	 */
	public static function register_polylang_taxonomies( $taxonomies, $is_settings ) {
		if ( ! defined( 'POLYLANG_VERSION' ) ) {
			return $taxonomies;
		}

		$taxonomies[] = PARTIKULIER_ESTATIK_TYPE_TAXONOMY;
		$taxonomies[] = PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY;
		$taxonomies[] = PARTIKULIER_ESTATIK_LOCATION_TAXONOMY;
		return array_values( array_unique( $taxonomies ) );
	}

	public static function supported_locales() {
		return array( 'fr', 'ar', 'en' );
	}

	public static function is_public_enabled() {
		return '1' === (string) get_option( self::OPTION_PUBLIC_ENABLED, '0' );
	}

	public static function maybe_install() {
		if ( self::DB_VERSION === get_option( self::OPTION_DB_VERSION ) ) {
			return;
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = self::table_name();
		$charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_property_id bigint(20) unsigned NOT NULL,
			locale varchar(8) NOT NULL,
			variant_property_id bigint(20) unsigned NOT NULL DEFAULT 0,
			original_free_text_locale varchar(8) NOT NULL,
			status varchar(16) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY source_locale (source_property_id,locale),
			KEY variant_property (variant_property_id),
			KEY status_locale (status,locale)
		) {$charset};" );
		update_option( self::OPTION_DB_VERSION, self::DB_VERSION, false );
		if ( false === get_option( self::OPTION_PUBLIC_ENABLED, false ) ) {
			add_option( self::OPTION_PUBLIC_ENABLED, '0', '', false );
		}
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'pk_property_variants';
	}

	/**
	 * Enregistre un emplacement de variante sans dupliquer de contenu. Une future
	 * passerelle de traduction renseignera variant_property_id après création contrôlée.
	 *
	 * @return true|WP_Error
	 */
	public static function prepare_variant( $source_property_id, $locale, $original_free_text_locale ) {
		$source_property_id = absint( $source_property_id );
		$locale = self::sanitize_locale( $locale );
		$original_free_text_locale = self::sanitize_locale( $original_free_text_locale );
		if ( ! $source_property_id || PARTIKULIER_ESTATIK_POST_TYPE !== get_post_type( $source_property_id ) ) {
			return new WP_Error( 'pk_variant_property', __( 'Annonce source invalide.', 'partikulier' ) );
		}
		if ( ! $locale || ! $original_free_text_locale ) {
			return new WP_Error( 'pk_variant_locale', __( 'Langue de variante invalide.', 'partikulier' ) );
		}

		global $wpdb;
		$now = current_time( 'mysql', true );
		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . self::table_name() . ' (source_property_id, locale, original_free_text_locale, status, created_at, updated_at) VALUES (%d, %s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE original_free_text_locale = VALUES(original_free_text_locale), updated_at = VALUES(updated_at)',
				$source_property_id,
				$locale,
				$original_free_text_locale,
				self::STATUS_PREPARED,
				$now,
				$now
			)
		);
		if ( false === $result ) {
			return new WP_Error( 'pk_variant_storage', __( 'Impossible de préparer la variante localisée.', 'partikulier' ) );
		}
		update_post_meta( $source_property_id, self::META_FREE_TEXT_LANGUAGE, $original_free_text_locale );
		return true;
	}

	/**
	 * Attache une variante Estatik déjà créée au registre métier, sans produire
	 * aucun contenu et sans activer l’affichage public.
	 *
	 * @return true|WP_Error
	 */
	public static function link_variant( $source_property_id, $variant_property_id, $locale, $original_free_text_locale ) {
		$source_property_id  = absint( $source_property_id );
		$variant_property_id = absint( $variant_property_id );
		$locale              = self::sanitize_locale( $locale );

		if ( ! $source_property_id || ! $variant_property_id || ! $locale ) {
			return new WP_Error( 'pk_variant_link', __( 'Lien de variante invalide.', 'partikulier' ) );
		}
		if ( PARTIKULIER_ESTATIK_POST_TYPE !== get_post_type( $source_property_id ) || PARTIKULIER_ESTATIK_POST_TYPE !== get_post_type( $variant_property_id ) ) {
			return new WP_Error( 'pk_variant_link_property', __( 'Les variantes doivent être des annonces Estatik.', 'partikulier' ) );
		}

		$prepared = self::prepare_variant( $source_property_id, $locale, $original_free_text_locale );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		global $wpdb;
		$updated = $wpdb->update(
			self::table_name(),
			array(
				'variant_property_id' => $variant_property_id,
				'status'              => 'linked',
				'updated_at'          => current_time( 'mysql', true ),
			),
			array(
				'source_property_id' => $source_property_id,
				'locale'             => $locale,
			),
			array( '%d', '%s', '%s' ),
			array( '%d', '%s' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'pk_variant_link_storage', __( 'Impossible de lier la variante localisée.', 'partikulier' ) );
		}

		return true;
	}

	private static function sanitize_locale( $locale ) {
		$locale = strtolower( sanitize_key( $locale ) );
		return in_array( $locale, self::supported_locales(), true ) ? $locale : '';
	}
}

Partikulier_Localization::init();