<?php
/**
 * Module : redaction des annonces en francais, anglais et arabe.
 *
 * Le texte d'une annonce n'est pas de la prose libre : il est compose a
 * partir de champs (type, surface, pieces, ville, prix, options). On peut
 * donc le REDIGER dans chaque langue au lieu de le traduire — le resultat
 * est naturel, gratuit et instantane.
 *
 * Chaque langue produit son propre titre, sa description et sa meta
 * description : trois pages distinctes, chacune avec son SEO.
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Partikulier_Listing_I18n {

	/**
	 * Langues prises en charge.
	 *
	 * @return string[]
	 */
	public static function languages() {
		return array( 'fr', 'en', 'ar' );
	}

	/**
	 * Lexique par langue.
	 *
	 * @param string $lang Code langue.
	 * @return array
	 */
	private static function lex( $lang ) {
		$all = array(
			'fr' => array(
				'owner_sells'   => 'Propriétaire vend',
				'owner_rents'   => 'Propriétaire loue',
				'the_owner'     => 'le propriétaire',
				'owner'         => 'propriétaire',
				'for_sale'      => 'à vendre',
				'for_rent'      => 'à louer',
				'sale_noun'     => 'à la vente',
				'rent_noun'     => 'à la location',
				'studio'        => 'Studio',
				'studio_low'    => 'studio',
				'of_area'       => 'de %d m²',
				'in_place'      => 'à %s',
				'with'          => 'avec',
				'and'           => 'et',
				'main_room'     => 'une pièce principale',
				'bedroom'       => '%d chambre',
				'bedrooms'      => '%d chambres',
				'plus_living'   => ' + salon',
				'bed_3plus'     => '3 chambres + salon ou plus',
				'bathroom'      => '%d salle de bains',
				'bathrooms'     => '%d salles de bains',
				'terrace'       => 'avec terrasse',
				'terrace_area'  => 'terrasse de %d m²',
				'with_terrace_area' => 'avec terrasse de %d m²',
				'floors'        => array(),
				'no_facing'     => 'sans vis-à-vis',
				'area_of'       => 'd’une superficie de %d m²',
				'price_at'      => 'au prix de %s MAD',
				'located'       => 'situé %s',
				'ground_floor'  => 'au rdc',
				'at_floor'      => 'au %s',
				'with_garage'   => 'avec garage ou sous-sol',
				'no_garage'     => 'sans garage',
				'with_lift'     => 'avec ascenseur',
				'no_lift'       => 'sans ascenseur',
				'p2p'           => 'Bien de particulier à particulier proposé %1$s à %2$s, en contact direct avec %3$s, sans commission ni intermédiaire.',
				'contact'       => 'Contact direct, sans commission.',
				'no_commission' => 'Sans commission.',
				'garage'        => 'garage',
				'lift'          => 'ascenseur',
				'terrace_short' => 'Terrasse',
				'photo_of'      => 'annonce de %s, sans commission',
				'photo_n'       => '(photo %d)',
				'angles'        => array( 'vue intérieure', 'pièce de vie', 'espace intérieur', 'vue depuis le séjour', 'détail du logement', 'seconde perspective' ),
				'at_ground'     => 'au rez-de-chaussée',
				'sun'           => array(
					'Ensoleillé le matin'      => 'ensoleillé le matin',
					'Ensoleillé l’après-midi'  => 'ensoleillé l’après-midi',
					"Ensoleillé l'après-midi"  => 'ensoleillé l’après-midi',
					'Toute la journée'         => 'ensoleillé toute la journée',
					'Très peu'                 => 'peu ensoleillé',
				),
				'types'         => array(),
			),
			'en' => array(
				'owner_sells'   => 'Owner selling',
				'owner_rents'   => 'Owner renting',
				'the_owner'     => 'the owner',
				'owner'         => 'owner',
				'for_sale'      => 'for sale',
				'for_rent'      => 'for rent',
				'sale_noun'     => 'for sale',
				'rent_noun'     => 'for rent',
				'studio'        => 'Studio',
				'studio_low'    => 'studio',
				'of_area'       => 'of %d sqm',
				'in_place'      => 'in %s',
				'with'          => 'with',
				'and'           => 'and',
				'main_room'     => 'one main room',
				'bedroom'       => '%d bedroom',
				'bedrooms'      => '%d bedrooms',
				'plus_living'   => ' + living room',
				'bed_3plus'     => '3 bedrooms + living room or more',
				'bathroom'      => '%d bathroom',
				'bathrooms'     => '%d bathrooms',
				'terrace'       => 'with a terrace',
				'terrace_area'  => '%d sqm terrace',
				'with_terrace_area' => 'with a %d sqm terrace',
				'floors'        => array( 'RDC' => 'ground floor', 'Dernier étage' => 'top floor' ),
				'no_facing'     => 'no facing neighbours',
				'area_of'       => 'with a surface of %d sqm',
				'price_at'      => 'priced at %s MAD',
				'located'       => 'located %s',
				'ground_floor'  => 'on the ground floor',
				'at_floor'      => 'on the %s',
				'with_garage'   => 'with garage or basement',
				'no_garage'     => 'no garage',
				'with_lift'     => 'with a lift',
				'no_lift'       => 'no lift',
				'p2p'           => 'Property offered directly by a private owner %1$s in %2$s, in direct contact with %3$s, with no agency fees.',
				'contact'       => 'Direct contact, no commission.',
				'no_commission' => 'No commission.',
				'garage'        => 'garage',
				'lift'          => 'lift',
				'terrace_short' => 'Terrace',
				'photo_of'      => 'listed by the %s, no commission',
				'photo_n'       => '(photo %d)',
				'angles'        => array( 'interior view', 'living area', 'indoor space', 'view from the living room', 'property detail', 'second perspective' ),
				'at_ground'     => 'on the ground floor',
				'sun'           => array(
					'Ensoleillé le matin'      => 'sunny in the morning',
					'Ensoleillé l’après-midi'  => 'sunny in the afternoon',
					"Ensoleillé l'après-midi"  => 'sunny in the afternoon',
					'Toute la journée'         => 'sunny all day',
					'Très peu'                 => 'little sunlight',
				),
				'types'         => array(
					'appartement' => 'Apartment',
					'maison'      => 'House',
					'studio'      => 'Studio',
					'villa'       => 'Villa',
					'terrain'     => 'Land',
					'loft'        => 'Loft',
					'duplex'      => 'Duplex',
					'chalet'      => 'Chalet',
					'bureau'      => 'Office',
					'local commercial' => 'Commercial premises',
					'riad'        => 'Riad',
					'ferme'       => 'Farm',
				),
			),
			'ar' => array(
				'owner_sells'   => 'المالك يبيع',
				'owner_rents'   => 'المالك يكري',
				'the_owner'     => 'المالك',
				'owner'         => 'المالك',
				'for_sale'      => 'للبيع',
				'for_rent'      => 'للكراء',
				'sale_noun'     => 'للبيع',
				'rent_noun'     => 'للكراء',
				'studio'        => 'استوديو',
				'studio_low'    => 'استوديو',
				'of_area'       => 'بمساحة %d م²',
				'in_place'      => 'في %s',
				'with'          => 'يتوفر على',
				'and'           => 'و',
				'main_room'     => 'غرفة رئيسية',
				'bedroom'       => 'غرفة نوم واحدة',
				'bedrooms'      => '%d غرف نوم',
				'plus_living'   => ' وصالون',
				'bed_3plus'     => '3 غرف نوم وصالون أو أكثر',
				'bathroom'      => 'حمام واحد',
				'bathrooms'     => '%d حمامات',
				'terrace'       => 'مع تراس',
				'terrace_area'  => 'تراس بمساحة %d م²',
				'with_terrace_area' => 'مع تراس بمساحة %d م²',
				'floors'        => array( 'RDC' => 'الطابق الأرضي', 'Dernier étage' => 'الطابق الأخير' ),
				'no_facing'     => 'بدون مقابل',
				'area_of'       => 'بمساحة %d م²',
				'price_at'      => 'بثمن %s درهم',
				'located'       => 'يقع %s',
				'ground_floor'  => 'في الطابق الأرضي',
				'at_floor'      => 'في %s',
				'with_garage'   => 'مع مرآب أو قبو',
				'no_garage'     => 'بدون مرآب',
				'with_lift'     => 'مع مصعد',
				'no_lift'       => 'بدون مصعد',
				'p2p'           => 'عقار معروض من مالك خاص %1$s في %2$s، اتصال مباشر مع %3$s، بدون عمولة ولا وسيط.',
				'contact'       => 'اتصال مباشر، بدون عمولة.',
				'no_commission' => 'بدون عمولة.',
				'garage'        => 'مرآب',
				'lift'          => 'مصعد',
				'terrace_short' => 'تراس',
				'photo_of'      => 'إعلان من %s، بدون عمولة',
				'photo_n'       => '(صورة %d)',
				'angles'        => array( 'منظر داخلي', 'فضاء المعيشة', 'فضاء داخلي', 'منظر من الصالون', 'تفصيل من العقار', 'منظر ثانٍ' ),
				'at_ground'     => 'في الطابق الأرضي',
				'sun'           => array(
					'Ensoleillé le matin'      => 'مشمس صباحاً',
					'Ensoleillé l’après-midi'  => 'مشمس بعد الزوال',
					"Ensoleillé l'après-midi"  => 'مشمس بعد الزوال',
					'Toute la journée'         => 'مشمس طوال اليوم',
					'Très peu'                 => 'قليل الشمس',
				),
				'types'         => array(
					'appartement' => 'شقة',
					'appartements' => 'شقة',
					'maison'      => 'منزل',
					'maisons'     => 'منزل',
					'studio'      => 'استوديو',
					'villa'       => 'فيلا',
					'terrain'     => 'أرض',
					'loft'        => 'لوفت',
					'duplex'      => 'دوبلكس',
					'chalet'      => 'شاليه',
					'bureau'      => 'مكتب',
					'local commercial' => 'محل تجاري',
					'riad'        => 'رياض',
						'ferme'       => 'ضيعة',
						'bien'        => 'عقار',
						'property'    => 'عقار',
					),
			),
		);

		return isset( $all[ $lang ] ) ? $all[ $lang ] : $all['fr'];
	}

	/**
	 * Noms de villes en arabe. Un nom de ville translittere en caracteres
	 * latins au milieu d'une phrase arabe casse la lecture et le referencement
	 * local : « في أكادير » est la forme que tapent les internautes marocains.
	 *
	 * @return array
	 */
	private static function arabic_places() {
		return array(
			'casablanca' => 'الدار البيضاء',
			'rabat'      => 'الرباط',
			'marrakech'  => 'مراكش',
			'tanger'     => 'طنجة',
			'fes'        => 'فاس',
			'fès'        => 'فاس',
			'agadir'     => 'أكادير',
			'saidia'     => 'السعيدية',
			'saïdia'     => 'السعيدية',
			'meknes'     => 'مكناس',
			'meknès'     => 'مكناس',
			'oujda'      => 'وجدة',
			'kenitra'    => 'القنيطرة',
			'kénitra'    => 'القنيطرة',
			'tetouan'    => 'تطوان',
			'tétouan'    => 'تطوان',
			'sale'       => 'سلا',
			'salé'       => 'سلا',
			'mohammedia' => 'المحمدية',
			'el jadida'  => 'الجديدة',
			'essaouira'  => 'الصويرة',
			'beni mellal' => 'بني ملال',
			'nador'      => 'الناظور',
			'ifrane'     => 'إفران',
			'ouarzazate' => 'ورزازات',
			'safi'       => 'آسفي',
			'dakhla'     => 'الداخلة',
			'laayoune'   => 'العيون',
			'laâyoune'   => 'العيون',
			'berrechid'  => 'برشيد',
			'settat'     => 'سطات',
			'khouribga'  => 'خريبكة',
			'taza'       => 'تازة',
			'larache'    => 'العرائش',
			'al hoceima' => 'الحسيمة',
			'chefchaouen' => 'شفشاون',
			'bouznika'   => 'بوزنيقة',
			'skhirat'    => 'الصخيرات',
			'temara'     => 'تمارة',
			'témara'     => 'تمارة',
			'berkane'    => 'بركان',
		);
	}

	/**
	 * Traduit un lieu (« Quartier, Ville ») dans la langue voulue.
	 * Un quartier inconnu reste tel quel : c'est un nom propre.
	 *
	 * @param string $place Lieu en francais.
	 * @param string $lang  Langue cible.
	 * @return string
	 */
	private static function place_in( $place, $lang ) {
		if ( 'ar' !== $lang || '' === $place ) {
			return $place;
		}

		$map    = self::arabic_places();
		$pieces = array_map( 'trim', explode( ',', $place ) );
		$out    = array();

		foreach ( $pieces as $piece ) {
			$key   = function_exists( 'mb_strtolower' ) ? mb_strtolower( $piece ) : strtolower( $piece );
			$key   = function_exists( 'remove_accents' ) ? remove_accents( $key ) : $key;
			$found = '';
			foreach ( $map as $needle => $arabic ) {
				$needle_key = function_exists( 'remove_accents' ) ? remove_accents( $needle ) : $needle;
				if ( $needle_key === $key ) {
					$found = $arabic;
					break;
				}
			}
			$out[] = $found ? $found : $piece;
		}

		return implode( '، ', $out );
	}

	/**
	 * Traduit le libelle d'un type de bien.
	 *
	 * @param string $type Libelle francais.
	 * @param string $lang Langue cible.
	 * @return string
	 */
	private static function type_label( $type, $lang ) {
		$lex = self::lex( $lang );
		$key = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $type ) ) : strtolower( trim( $type ) );

		return isset( $lex['types'][ $key ] ) ? $lex['types'][ $key ] : $type;
	}

	/**
	 * Nombre formate selon la langue (chiffres arabes occidentaux partout,
	 * separateur adapte : l'arabe marocain lit tres bien 124 098).
	 *
	 * @param int $number Nombre.
	 * @return string
	 */
	private static function number( $number ) {
		return number_format( (int) $number, 0, ',', ' ' );
	}

	/**
	 * Garantit les clés consommées par les générateurs, y compris pour les
	 * anciennes annonces dont certains champs optionnels n'existent pas.
	 *
	 * @param array $values Données partiellement normalisées.
	 * @return array
	 */
	private static function normalize_values( $values ) {
		$defaults = array(
			'action'          => '',
			'role'            => '',
			'type'            => '',
			'city'            => '',
			'district'        => '',
			'surface'         => 0,
			'price'           => 0,
			'bedrooms'        => '',
			'living_rooms'    => '',
			'bathrooms'       => '',
			'floor'           => '',
			'garage'          => 'Non',
			'elevator'         => 'Non',
			'vis_a_vis'       => 'Non',
			'terrace'         => 'Non',
			'terrace_surface' => 0,
			'sunshine'        => '',
		);

		return wp_parse_args( is_array( $values ) ? $values : array(), $defaults );
	}

	/**
	 * Libelle du couchage dans la langue voulue.
	 *
	 * @param array  $v    Donnees normalisees.
	 * @param string $lang Langue.
	 * @return string
	 */
	private static function rooms( $v, $lang ) {
		$lex = self::lex( $lang );

		if ( self::is_studio( $v ) ) {
			return $lex['studio'];
		}
		if ( '' === (string) $v['bedrooms'] ) {
			return '';
		}
		if ( '3+' === (string) $v['bedrooms'] ) {
			return $lex['bed_3plus'];
		}

		$count = (int) $v['bedrooms'];
		$label = 1 === $count ? sprintf( $lex['bedroom'], $count ) : sprintf( $lex['bedrooms'], $count );

		if ( '' !== (string) $v['living_rooms'] && '0' !== (string) $v['living_rooms'] ) {
			$label .= $lex['plus_living'];
		}

		return $label;
	}

	/**
	 * Le bien est-il un studio ?
	 *
	 * @param array $v Donnees normalisees.
	 * @return bool
	 */
	private static function is_studio( $v ) {
		return 'studio' === strtolower( $v['type'] ) || '0' === (string) $v['bedrooms'];
	}

	/**
	 * Titre de l'annonce dans une langue donnee.
	 *
	 * @param array  $v    Donnees normalisees.
	 * @param string $lang Langue.
	 * @return string
	 */
	public static function title( $v, $lang ) {
		$v     = self::normalize_values( $v );
		$lex   = self::lex( $lang );
		$type  = self::is_studio( $v ) ? $lex['studio'] : self::type_label( $v['type'], $lang );
		$title = $type;

		if ( $v['surface'] ) {
			$title .= ' ' . sprintf( $lex['of_area'], $v['surface'] );
		}
		if ( 'Oui' === $v['vis_a_vis'] ) {
			$title .= ' ' . $lex['no_facing'];
		}
		if ( 'Oui' === $v['terrace'] ) {
			$title .= ' ' . ( $v['terrace_surface']
				? sprintf( $lex['with_terrace_area'], $v['terrace_surface'] )
				: $lex['terrace'] );
		}

		$title .= ' ' . ( 'louer' === $v['action'] ? $lex['for_rent'] : $lex['for_sale'] );

		$place = self::place_in( Partikulier_Listing_Preview::place_label( $v ), $lang );
		if ( '' !== $place ) {
			$title .= ' ' . sprintf( $lex['in_place'], $place );
		}

		return self::squash( $title );
	}

	/**
	 * Description complete dans une langue donnee.
	 *
	 * @param array  $v    Donnees normalisees.
	 * @param string $lang Langue.
	 * @return string
	 */
	public static function description( $v, $lang ) {
		$v     = self::normalize_values( $v );
		$lex   = self::lex( $lang );
		$type  = self::is_studio( $v ) ? $lex['studio'] : self::type_label( $v['type'], $lang );
		$place = self::place_in( Partikulier_Listing_Preview::place_label( $v ), $lang );

		// 1. Accroche : « Propriétaire vend studio de 72 m² à Hay Riad, Rabat. »
		$opener = '';
		if ( '' !== $place ) {
			$opener = ( 'louer' === $v['action'] ? $lex['owner_rents'] : $lex['owner_sells'] )
				. ' ' . self::lower( $type )
				. ( $v['surface'] ? ' ' . sprintf( $lex['of_area'], $v['surface'] ) : '' )
				. ' ' . sprintf( $lex['in_place'], $place ) . '.';
		}

		// 2. Phrase factuelle.
		$sentence = $type;
		$rooms    = array();
		if ( self::is_studio( $v ) ) {
			$rooms[] = $lex['main_room'];
		} else {
			$label = self::rooms( $v, $lang );
			if ( '' !== $label ) {
				$rooms[] = self::lower( $label );
			}
		}
		if ( '' !== (string) $v['bathrooms'] ) {
			$count   = '3+' === (string) $v['bathrooms'] ? 3 : (int) $v['bathrooms'];
			$rooms[] = 1 === $count ? sprintf( $lex['bathroom'], $count ) : sprintf( $lex['bathrooms'], $count );
		}
		if ( $rooms ) {
			$sentence .= ' ' . $lex['with'] . ' ' . implode( ' ' . $lex['and'] . ' ', $rooms );
		}
		if ( 'Oui' === $v['terrace'] ) {
			// « avec terrasse de 15 m² » : la preposition evite « ...2 salles de bains terrasse ».
			$sentence .= ' ' . ( $v['terrace_surface']
				? sprintf( $lex['with_terrace_area'], $v['terrace_surface'] )
				: $lex['terrace'] );
		}
		$sentence .= ' ' . ( 'louer' === $v['action'] ? $lex['for_rent'] : $lex['for_sale'] );
		if ( '' !== $place ) {
			$sentence .= ' ' . sprintf( $lex['in_place'], $place );
		}

		// 3. Details.
		$details = array();
		if ( $v['surface'] ) {
			$details[] = sprintf( $lex['area_of'], $v['surface'] );
		}
		if ( $v['price'] ) {
			$details[] = sprintf( $lex['price_at'], self::number( $v['price'] ) );
		}
		if ( '' !== $v['floor'] ) {
			$floor     = 'RDC' === $v['floor'] ? $lex['ground_floor'] : sprintf( $lex['at_floor'], self::floor_label( $v['floor'], $lang ) );
			$details[] = sprintf( $lex['located'], $floor );
		}
		$details[] = 'Oui' === $v['garage'] ? $lex['with_garage'] : $lex['no_garage'];
		$details[] = 'Oui' === $v['elevator'] ? $lex['with_lift'] : $lex['no_lift'];
		if ( 'Oui' === $v['vis_a_vis'] ) {
			$details[] = $lex['no_facing'];
		}
		if ( '' !== $v['sunshine'] ) {
			$details[] = isset( $lex['sun'][ $v['sunshine'] ] ) ? $lex['sun'][ $v['sunshine'] ] : self::lower( $v['sunshine'] );
		}

		$text = ( '' !== $opener ? $opener . ' ' : '' ) . $sentence;
		if ( $details ) {
			$text .= '، ' === '' ? '' : ( 'ar' === $lang ? '، ' : ', ' );
			$text .= implode( 'ar' === $lang ? '، ' : ', ', $details );
		}
		$text .= '.';

		// 4. Cloture « particulier a particulier ».
		if ( '' !== $place ) {
			$text .= ' ' . sprintf(
				$lex['p2p'],
				'louer' === $v['action'] ? $lex['rent_noun'] : $lex['sale_noun'],
				$place,
				$lex['the_owner']
			);
		}

		return self::squash( $text );
	}

	/**
	 * Meta description calibree (155 desktop, essentiel dans les 120 premiers).
	 *
	 * @param array  $v    Donnees normalisees.
	 * @param string $lang Langue.
	 * @return string
	 */
	public static function meta_description( $v, $lang ) {
		$v     = self::normalize_values( $v );
		$lex   = self::lex( $lang );
		$type  = self::is_studio( $v ) ? $lex['studio'] : self::type_label( $v['type'], $lang );
		$place = self::place_in( Partikulier_Listing_Preview::place_label( $v ), $lang );
		$sep   = 'ar' === $lang ? '، ' : ', ';

		$core = ( 'louer' === $v['action'] ? $lex['owner_rents'] : $lex['owner_sells'] ) . ' ' . self::lower( $type );
		if ( $v['surface'] ) {
			$core .= ' ' . sprintf( $lex['of_area'], $v['surface'] );
		}
		$rooms = self::rooms( $v, $lang );
		if ( '' !== $rooms && ! self::is_studio( $v ) ) {
			$core .= $sep . self::lower( $rooms );
		}
		if ( '' !== $place ) {
			$core .= ' ' . sprintf( $lex['in_place'], $place );
		}
		if ( $v['price'] ) {
			// Debut de phrase : la premiere lettre doit etre capitalisee
			// (sans effet en arabe, qui ignore la casse).
			$core .= '. ' . self::ucfirst_safe( sprintf( $lex['price_at'], self::number( $v['price'] ) ) );
		}
		$core .= '.';

		$extras = array();
		if ( 'Oui' === $v['terrace'] ) {
			$extras[] = $lex['terrace_short'];
		}
		if ( 'Oui' === $v['vis_a_vis'] ) {
			$extras[] = $lex['no_facing'];
		}
		if ( 'Oui' === $v['garage'] ) {
			$extras[] = $lex['garage'];
		}
		if ( 'Oui' === $v['elevator'] ) {
			$extras[] = $lex['lift'];
		}

		$meta  = $core;
		$limit = 155;

		if ( $extras ) {
			$candidate = $meta . ' ' . implode( $sep, $extras ) . '.';
			if ( mb_strlen( $candidate ) <= $limit ) {
				$meta = $candidate;
			}
		}
		if ( mb_strlen( $meta . ' ' . $lex['contact'] ) <= $limit ) {
			$meta .= ' ' . $lex['contact'];
		} elseif ( mb_strlen( $meta . ' ' . $lex['no_commission'] ) <= $limit ) {
			$meta .= ' ' . $lex['no_commission'];
		}

		if ( mb_strlen( $meta ) > $limit + 3 ) {
			$meta = mb_substr( $meta, 0, $limit );
			$cut  = mb_strrpos( $meta, ' ' );
			if ( false !== $cut ) {
				$meta = mb_substr( $meta, 0, $cut );
			}
			$meta = rtrim( $meta, " ,.;:،" ) . '…';
		}

		return self::squash( $meta );
	}

	/**
	 * Texte alternatif d'une photo, dans la langue voulue.
	 *
	 * @param array  $v     Donnees normalisees.
	 * @param string $lang  Langue.
	 * @param int    $index Rang de la photo.
	 * @return string
	 */
	public static function image_alt( $v, $lang, $index = 0 ) {
		$lex   = self::lex( $lang );
		$type  = self::is_studio( $v ) ? $lex['studio'] : self::type_label( $v['type'], $lang );
		$place = self::place_in( Partikulier_Listing_Preview::place_label( $v ), $lang );
		$sep   = 'ar' === $lang ? '، ' : ', ';

		if ( 0 === $index ) {
			$alt = $type;
			if ( $v['surface'] ) {
				$alt .= ' ' . sprintf( $lex['of_area'], $v['surface'] );
			}
			$rooms = self::rooms( $v, $lang );
			if ( '' !== $rooms && ! self::is_studio( $v ) ) {
				$alt .= $sep . self::lower( $rooms );
			}
			$alt .= ' ' . ( 'louer' === $v['action'] ? $lex['for_rent'] : $lex['for_sale'] );
			if ( '' !== $place ) {
				$alt .= ' ' . sprintf( $lex['in_place'], $place );
			}
			$alt .= ' — ' . sprintf( $lex['photo_of'], $lex['owner'] );

			return self::trim_to( self::squash( $alt ), 125 );
		}

		$angles = $lex['angles'];
		$alt    = $type;
		if ( '' !== $place ) {
			$alt .= ' ' . sprintf( $lex['in_place'], $place );
		}
		$alt .= ' — ' . $angles[ ( $index - 1 ) % count( $angles ) ];

		$features = array();
		if ( 'Oui' === $v['terrace'] ) {
			$features[] = $v['terrace_surface'] ? sprintf( $lex['terrace_area'], $v['terrace_surface'] ) : $lex['terrace'];
		}
		if ( 'Oui' === $v['vis_a_vis'] ) {
			$features[] = $lex['no_facing'];
		}
		if ( '' !== $v['sunshine'] ) {
			$features[] = isset( $lex['sun'][ $v['sunshine'] ] ) ? $lex['sun'][ $v['sunshine'] ] : self::lower( $v['sunshine'] );
		}
		if ( '' !== $v['floor'] ) {
			$features[] = 'RDC' === $v['floor'] ? $lex['at_ground'] : self::lower( $v['floor'] );
		}
		if ( $features ) {
			$alt .= $sep . $features[ ( $index - 1 ) % count( $features ) ];
		}
		$alt .= ' ' . sprintf( $lex['photo_n'], $index + 1 );

		return self::trim_to( self::squash( $alt ), 125 );
	}

	/**
	 * Traduit un libelle d'etage (« 5e étage » -> « 5th floor » / « الطابق 5 »).
	 *
	 * @param string $floor Libelle francais.
	 * @param string $lang  Langue cible.
	 * @return string
	 */
	private static function floor_label( $floor, $lang ) {
		$lex = self::lex( $lang );
		if ( isset( $lex['floors'][ $floor ] ) ) {
			return $lex['floors'][ $floor ];
		}
		if ( 'fr' === $lang ) {
			return self::lower( $floor );
		}

		// « 5e étage » -> on isole le nombre, seul element porteur de sens.
		if ( preg_match( '/(\d+)/', $floor, $m ) ) {
			$n = (int) $m[1];
			if ( 'en' === $lang ) {
				$suffix = 'th';
				if ( 1 === $n % 10 && 11 !== $n ) {
					$suffix = 'st';
				} elseif ( 2 === $n % 10 && 12 !== $n ) {
					$suffix = 'nd';
				} elseif ( 3 === $n % 10 && 13 !== $n ) {
					$suffix = 'rd';
				}

				return $n . $suffix . ' floor';
			}

			return 'الطابق ' . $n;
		}

		return self::lower( $floor );
	}

	/**
	 * Traduit un libelle de type dans la langue demandee.
	 *
	 * @param string $type Type source.
	 * @param string $lang Langue cible.
	 * @return string
	 */
	public static function localized_type( $type, $lang = '' ) {
		$lang = $lang ? $lang : ( function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : 'fr' );
		return self::type_label( (string) $type, $lang );
	}

	/**
	 * Traduit un lieu libre en conservant les quartiers inconnus.
	 *
	 * @param string $place Lieu source.
	 * @param string $lang Langue cible.
	 * @return string
	 */
	public static function localized_place( $place, $lang = '' ) {
		$lang = $lang ? $lang : ( function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : 'fr' );
		return self::place_in( (string) $place, $lang );
	}

	/**
	 * Construit un titre localise pour une annonce legacy sans traduction liee.
	 * Un titre arabe manuel existant est toujours prioritaire.
	 *
	 * @param WP_Post|int $post Annonce.
	 * @param string      $lang Langue cible.
	 * @return string
	 */
	public static function title_from_post( $post, $lang = '' ) {
		$post = $post instanceof WP_Post ? $post : get_post( $post );
		if ( ! $post ) {
			return '';
		}
		$lang = $lang ? $lang : ( function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : 'fr' );
		if ( 'fr' === $lang ) {
			return get_the_title( $post );
		}

		$display_post = $post;
		if ( function_exists( 'pll_get_post' ) ) {
			$translated_id = (int) pll_get_post( $post->ID, $lang );
			if ( $translated_id && $translated_id !== (int) $post->ID ) {
				$translated_post = get_post( $translated_id );
				if ( $translated_post instanceof WP_Post ) {
					$display_post = $translated_post;
				}
			}
		}
		$candidate = trim( (string) get_the_title( $display_post ) );
			if ( 'ar' === $lang && preg_match( '/\p{Arabic}/u', $candidate ) && ! preg_match( '/[A-Za-zÀ-ÿ]/u', $candidate ) ) {
				return $candidate;
			}

		$source_id = (int) get_post_meta( $display_post->ID, '_pk_translation_source', true );
		$source    = $source_id ? get_post( $source_id ) : $post;
		$source    = $source instanceof WP_Post ? $source : $post;
		$type_terms = get_the_terms( $source->ID, PARTIKULIER_ESTATIK_TYPE_TAXONOMY );
		$type       = ( $type_terms && ! is_wp_error( $type_terms ) ) ? $type_terms[0]->name : 'Bien';
		$cat_terms  = get_the_terms( $source->ID, PARTIKULIER_ESTATIK_CATEGORY_TAXONOMY );
		$cat_name   = ( $cat_terms && ! is_wp_error( $cat_terms ) ) ? strtolower( remove_accents( $cat_terms[0]->name ) ) : '';
		$action     = ( false !== strpos( $cat_name, 'lou' ) || false !== strpos( $cat_name, 'rent' ) || false !== strpos( $cat_name, 'locat' ) ) ? 'louer' : 'vendre';
		$city       = '';
		$district   = '';
		$places     = get_the_terms( $source->ID, PARTIKULIER_ESTATIK_LOCATION_TAXONOMY );
		if ( $places && ! is_wp_error( $places ) ) {
			foreach ( $places as $term ) {
				if ( $term->parent ) {
					$district = $term->name;
					$parent   = get_term( $term->parent, PARTIKULIER_ESTATIK_LOCATION_TAXONOMY );
					if ( $parent && ! is_wp_error( $parent ) ) {
						$city = $parent->name;
					}
				} elseif ( '' === $city ) {
					$city = $term->name;
				}
			}
		}
		$bedrooms = (string) get_post_meta( $source->ID, '_pk_bedrooms_label', true );
		if ( '' === $bedrooms ) {
			$bedrooms = (string) get_post_meta( $source->ID, 'es_property_bedrooms', true );
		}
		if ( '' === $bedrooms ) {
			$bedrooms = (string) get_post_meta( $source->ID, 'es_bedrooms', true );
		}
		$living_rooms = (string) get_post_meta( $source->ID, '_pk_living_rooms_label', true );
		if ( '' === $living_rooms ) {
			$living_rooms = (string) get_post_meta( $source->ID, '_pk_living_rooms', true );
		}
		$values = array(
				'action'          => $action,
				'type'            => $type,
				'city'            => $city,
				'district'        => $district,
				'surface'         => (int) get_post_meta( $source->ID, 'es_property_area', true ),
				'bedrooms'        => $bedrooms,
				'living_rooms'    => $living_rooms,
				'bathrooms'       => (string) get_post_meta( $source->ID, '_pk_bathrooms_label', true ),
			'floor'           => (string) get_post_meta( $source->ID, '_pk_floor', true ),
			'garage'          => (string) get_post_meta( $source->ID, '_pk_garage', true ),
			'elevator'        => (string) get_post_meta( $source->ID, '_pk_elevator', true ),
			'vis_a_vis'       => (string) get_post_meta( $source->ID, '_pk_vis_a_vis', true ),
			'terrace'         => (string) get_post_meta( $source->ID, '_pk_terrace', true ),
			'terrace_surface' => (string) get_post_meta( $source->ID, '_pk_terrace_surface', true ),
			'sunshine'        => (string) get_post_meta( $source->ID, '_pk_sunshine', true ),
		);
		return self::title( $values, $lang );
	}

	/**
	 * Retourne la composition lisible des chambres/salons pour une carte.
	 * Les annonces anciennes peuvent ne pas avoir les labels maison : on
	 * reprend alors les metas Estatik standard copiees par le rattrapage.
	 *
	 * @param WP_Post|int $post Annonce.
	 * @param string      $lang Langue cible.
	 * @return string
	 */
	public static function rooms_label_from_post( $post, $lang = '' ) {
		$post = $post instanceof WP_Post ? $post : get_post( $post );
		if ( ! $post ) {
			return '';
		}
		$lang = $lang ? $lang : ( function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : 'fr' );
		$bedrooms = (string) get_post_meta( $post->ID, '_pk_bedrooms_label', true );
		if ( '' === $bedrooms ) {
			$bedrooms = (string) get_post_meta( $post->ID, 'es_property_bedrooms', true );
		}
		if ( '' === $bedrooms ) {
			$bedrooms = (string) get_post_meta( $post->ID, 'es_bedrooms', true );
		}
		$living_rooms = (string) get_post_meta( $post->ID, '_pk_living_rooms_label', true );
		if ( '' === $living_rooms ) {
			$living_rooms = (string) get_post_meta( $post->ID, '_pk_living_rooms', true );
		}
		if ( '' === $bedrooms ) {
			return '';
		}
		$types = get_the_terms( $post->ID, PARTIKULIER_ESTATIK_TYPE_TAXONOMY );
		$type  = ( $types && ! is_wp_error( $types ) ) ? $types[0]->name : '';
		return self::rooms(
			array(
				'type'         => $type,
				'bedrooms'     => $bedrooms,
				'living_rooms' => $living_rooms,
			),
			$lang
		);
	}

	/**
	 * Majuscule initiale sure (sans effet sur l'arabe).
	 *
	 * @param string $text Texte.
	 * @return string
	 */
	private static function ucfirst_safe( $text ) {
		if ( '' === $text || preg_match( '/^\p{Arabic}/u', $text ) ) {
			return $text;
		}
		$first = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $text, 0, 1 ) ) : strtoupper( substr( $text, 0, 1 ) );

		return $first . mb_substr( $text, 1 );
	}

	/**
	 * Minuscule sure : l'arabe n'a pas de casse, on ne le touche pas.
	 *
	 * @param string $text Texte.
	 * @return string
	 */
	private static function lower( $text ) {
		if ( preg_match( '/\p{Arabic}/u', $text ) ) {
			return $text;
		}

		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text ) : strtolower( $text );
	}

	/**
	 * Normalise les espaces.
	 *
	 * @param string $text Texte.
	 * @return string
	 */
	private static function squash( $text ) {
		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Coupe au dernier mot entier.
	 *
	 * @param string $text  Texte.
	 * @param int    $limit Longueur maximale.
	 * @return string
	 */
	private static function trim_to( $text, $limit ) {
		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}
		$text = mb_substr( $text, 0, $limit );
		$cut  = mb_strrpos( $text, ' ' );
		if ( false !== $cut ) {
			$text = mb_substr( $text, 0, $cut );
		}

		return rtrim( $text, " ,—-،" );
	}
}
