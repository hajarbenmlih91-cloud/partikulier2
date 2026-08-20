<?php
/**
 * Module : validation des depots d'annonces depuis l'administration.
 *
 * Un ecran unique liste les annonces en attente. A la validation :
 *   - l'annonce est publiee (et ses traductions avec elle) ;
 *   - un compte annonceur est cree si besoin ;
 *   - un mot de passe lisible est genere ;
 *   - l'evenement est envoye a n8n, qui envoie les identifiants sur WhatsApp.
 *
 * L'annonceur retrouve son identifiant et son mot de passe dans sa
 * messagerie WhatsApp : il peut se reconnecter quand il veut sans
 * dependre d'un lien expire. Le mot de passe n'est stocke nulle part en
 * clair cote site (WordPress ne conserve que son empreinte).
 *
 * @package Partikulier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Partikulier_Listing_Approval {

	const MENU_SLUG = 'pk-approvals';
	const ACTION    = 'pk_approve_listing';
	const ACTION_RESEND = 'pk_resend_credentials';

	/**
	 * Longueur du mot de passe genere et transmis a l'annonceur.
	 * Assez long pour resister, assez simple pour etre retape a la main
	 * depuis un telephone.
	 */
	const PASSWORD_LENGTH = 10;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 15 );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_' . self::ACTION_RESEND, array( __CLASS__, 'handle_resend' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Menu et liste                                                       */
	/* ------------------------------------------------------------------ */

	public static function add_menu() {
		$count = count( self::pending_listings() );
		$label = __( 'Valider les annonces', 'partikulier' );
		if ( $count ) {
			$label .= ' <span class="update-plugins count-' . (int) $count . '"><span class="plugin-count">' . (int) $count . '</span></span>';
		}

		add_submenu_page(
			'partikulier',
			__( 'Valider les annonces', 'partikulier' ),
			$label,
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Annonces en attente de validation, hors traductions generees.
	 *
	 * @return WP_Post[]
	 */
	public static function pending_listings() {
		$posts = get_posts( array(
			'post_type'        => PARTIKULIER_ESTATIK_POST_TYPE,
			'post_status'      => 'pending',
			'posts_per_page'   => 100,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
			'lang'             => '',
		) );

		return array_values( array_filter( $posts, static function ( $post ) {
			return ! get_post_meta( $post->ID, '_pk_auto_translation', true );
		} ) );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'partikulier' ), '', array( 'response' => 403 ) );
		}

		$pending = self::pending_listings();
		$done    = isset( $_GET['pk_approved'] ) ? absint( $_GET['pk_approved'] ) : 0;
		$creds   = get_transient( 'pk_last_credentials' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Valider les annonces', 'partikulier' ); ?></h1>
			<p class="description" style="max-width:46em">
				<?php esc_html_e( 'À la validation, l’annonce est publiée dans les trois langues, un compte est créé pour l’annonceur, et n8n reçoit son identifiant et son mot de passe pour les lui envoyer sur WhatsApp.', 'partikulier' ); ?>
			</p>

			<?php if ( $done && $creds ) : ?>
				<div class="notice notice-success">
					<p><strong><?php esc_html_e( 'Annonce publiée. Identifiants générés :', 'partikulier' ); ?></strong></p>
					<p style="font-family:monospace;background:#fff;padding:10px;border:1px solid #dcdcde">
						<?php esc_html_e( 'Identifiant', 'partikulier' ); ?> : <strong><?php echo esc_html( $creds['login'] ); ?></strong><br>
						<?php esc_html_e( 'Mot de passe', 'partikulier' ); ?> :
						<strong><?php echo $creds['password'] ? esc_html( $creds['password'] ) : esc_html__( 'inchangé (déjà transmis précédemment)', 'partikulier' ); ?></strong><br>
						<?php esc_html_e( 'Téléphone', 'partikulier' ); ?> : <?php echo esc_html( $creds['phone'] ); ?><br>
						<?php esc_html_e( 'Envoi n8n', 'partikulier' ); ?> :
						<?php echo $creds['sent'] ? esc_html__( 'transmis', 'partikulier' ) : esc_html__( 'non configuré — copiez les informations ci-dessus', 'partikulier' ); ?>
					</p>
				</div>
				<?php delete_transient( 'pk_last_credentials' ); ?>
			<?php endif; ?>

			<?php if ( ! $pending ) : ?>
				<div style="background:#edfaef;border-left:4px solid #00a32a;padding:14px 18px;max-width:46em;margin-top:16px">
					<strong><?php esc_html_e( 'Aucune annonce en attente.', 'partikulier' ); ?></strong>
				</div>
			<?php else : ?>
				<table class="widefat striped" style="max-width:60em;margin-top:16px">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Annonce', 'partikulier' ); ?></th>
							<th style="width:170px"><?php esc_html_e( 'Annonceur', 'partikulier' ); ?></th>
							<th style="width:110px"><?php esc_html_e( 'Code WhatsApp', 'partikulier' ); ?></th>
							<th style="width:210px"><?php esc_html_e( 'Décision', 'partikulier' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $pending as $post ) :
						$name  = get_post_meta( $post->ID, '_pk_owner_name', true );
						$phone = get_post_meta( $post->ID, '_pk_owner_phone', true );
						$code  = get_post_meta( $post->ID, '_pk_whatsapp_verification_code', true );
						$blocked = class_exists( 'Partikulier_Place_Requests' ) && Partikulier_Place_Requests::is_blocked( $post->ID );
						?>
						<tr>
							<td>
								<strong><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a></strong><br>
								<span style="color:#646970;font-size:12px"><?php echo esc_html( get_the_date( '', $post ) ); ?></span>
								<?php if ( $blocked ) : ?>
									<br><span style="color:#d63638;font-size:12px"><?php esc_html_e( 'Lieu en attente de validation', 'partikulier' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( $name ? $name : '—' ); ?><br>
								<span style="color:#646970;font-size:12px"><?php echo esc_html( $phone ); ?></span>
							</td>
							<td><code><?php echo esc_html( $code ? $code : '—' ); ?></code></td>
							<td>
								<?php if ( $blocked ) : ?>
									<span style="color:#8c8f94"><?php esc_html_e( 'Validez d’abord le lieu', 'partikulier' ); ?></span>
								<?php else : ?>
									<a class="button button-primary" href="<?php echo esc_url( self::decision_url( $post->ID, 'approve' ) ); ?>"><?php esc_html_e( 'Publier', 'partikulier' ); ?></a>
									<a class="button" href="<?php echo esc_url( self::decision_url( $post->ID, 'reject' ) ); ?>"
									   onclick="return confirm('<?php echo esc_js( __( 'Refuser cette annonce ?', 'partikulier' ) ); ?>');"><?php esc_html_e( 'Refuser', 'partikulier' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2 style="margin-top:32px"><?php esc_html_e( 'Renvoyer des identifiants', 'partikulier' ); ?></h2>
			<p class="description" style="max-width:46em">
				<?php esc_html_e( 'Si un annonceur a perdu son message WhatsApp, générez-lui un nouveau mot de passe. L’ancien cesse aussitôt de fonctionner.', 'partikulier' ); ?>
			</p>
			<?php $recent = self::recent_approved(); ?>
			<?php if ( ! $recent ) : ?>
				<p><em><?php esc_html_e( 'Aucune annonce publiée récemment.', 'partikulier' ); ?></em></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:60em">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Annonce', 'partikulier' ); ?></th>
							<th style="width:190px"><?php esc_html_e( 'Annonceur', 'partikulier' ); ?></th>
							<th style="width:190px"><?php esc_html_e( 'Action', 'partikulier' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $recent as $post ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a></td>
							<td>
								<?php echo esc_html( get_post_meta( $post->ID, '_pk_owner_name', true ) ); ?><br>
								<span style="color:#646970;font-size:12px"><?php echo esc_html( get_post_meta( $post->ID, '_pk_owner_phone', true ) ); ?></span>
							</td>
							<td>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_RESEND . '&listing=' . $post->ID ), self::ACTION_RESEND . '_' . $post->ID ) ); ?>"
								   onclick="return confirm('<?php echo esc_js( __( 'Générer un nouveau mot de passe ? L’ancien ne fonctionnera plus.', 'partikulier' ) ); ?>');">
									<?php esc_html_e( 'Nouveau mot de passe', 'partikulier' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2 style="margin-top:32px"><?php esc_html_e( 'Connexion n8n', 'partikulier' ); ?></h2>
			<table class="form-table" style="max-width:60em">
				<tr>
					<th><?php esc_html_e( 'Webhook sortant', 'partikulier' ); ?></th>
					<td>
						<?php $hook = Partikulier_Settings::get( 'n8n_webhook_url' ); ?>
						<?php if ( $hook ) : ?>
							<code><?php echo esc_html( $hook ); ?></code>
						<?php else : ?>
							<em><?php esc_html_e( 'Non configuré', 'partikulier' ); ?></em> —
							<?php esc_html_e( 'Apparence › Personnaliser › Validation WhatsApp › URL du webhook n8n', 'partikulier' ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Route de rattrapage', 'partikulier' ); ?></th>
					<td>
						<code>GET <?php echo esc_html( rest_url( 'partikulier/v1/approved-listings' ) ); ?></code><br>
						<span style="color:#646970;font-size:12px">
							<?php esc_html_e( 'En-tête : X-Partikulier-Automation. Renvoie les validations des dernières 72 heures si le webhook a échoué.', 'partikulier' ); ?>
						</span>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Annonces publiees recemment, pour le renvoi d'identifiants.
	 *
	 * @return WP_Post[]
	 */
	public static function recent_approved() {
		$posts = get_posts( array(
			'post_type'        => PARTIKULIER_ESTATIK_POST_TYPE,
			'post_status'      => 'publish',
			'posts_per_page'   => 20,
			'suppress_filters' => true,
			'lang'             => '',
			'meta_key'         => '_pk_approved_at',
			'orderby'          => 'meta_value',
			'order'            => 'DESC',
		) );

		return array_values( array_filter( $posts, static function ( $post ) {
			return ! get_post_meta( $post->ID, '_pk_auto_translation', true );
		} ) );
	}

	private static function decision_url( $post_id, $decision ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION . '&listing=' . (int) $post_id . '&decision=' . $decision ),
			self::ACTION . '_' . $post_id
		);
	}

	/* ------------------------------------------------------------------ */
	/* Traitement                                                          */
	/* ------------------------------------------------------------------ */

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'partikulier' ), '', array( 'response' => 403 ) );
		}

		$post_id  = isset( $_GET['listing'] ) ? absint( $_GET['listing'] ) : 0;
		$decision = isset( $_GET['decision'] ) ? sanitize_key( wp_unslash( $_GET['decision'] ) ) : '';
		check_admin_referer( self::ACTION . '_' . $post_id );

		$post = get_post( $post_id );
		if ( ! $post || PARTIKULIER_ESTATIK_POST_TYPE !== $post->post_type ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
			exit;
		}

		if ( 'reject' === $decision ) {
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
			update_post_meta( $post_id, '_pk_status', 'refuse' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
			exit;
		}

		self::approve( $post_id );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&pk_approved=' . $post_id ) );
		exit;
	}

	/**
	 * Regenere un mot de passe et le renvoie via n8n.
	 *
	 * Utile quand l'annonceur a perdu son message WhatsApp.
	 */
	public static function handle_resend() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'partikulier' ), '', array( 'response' => 403 ) );
		}

		$post_id = isset( $_GET['listing'] ) ? absint( $_GET['listing'] ) : 0;
		check_admin_referer( self::ACTION_RESEND . '_' . $post_id );

		$credentials = self::prepare_credentials( $post_id, true );
		$sent        = self::notify_n8n( $post_id, $credentials );

		set_transient( 'pk_last_credentials', array(
			'login'    => $credentials['login'],
			'password' => $credentials['password'],
			'phone'    => $credentials['phone'],
			'sent'     => $sent,
		), 5 * MINUTE_IN_SECONDS );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&pk_approved=' . $post_id ) );
		exit;
	}

	/**
	 * Publie l'annonce, prepare les identifiants et notifie n8n.
	 *
	 * @param int $post_id Annonce.
	 * @return array Donnees transmises.
	 */
	public static function approve( $post_id ) {
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		update_post_meta( $post_id, '_pk_status', 'actif' );
		update_post_meta( $post_id, '_pk_approved_at', current_time( 'mysql' ) );

		if ( class_exists( 'Partikulier_Listing_Translations' ) ) {
			Partikulier_Listing_Translations::sync_status( $post_id, 'publish' );
		}

		$credentials = self::prepare_credentials( $post_id );
		$sent        = self::notify_n8n( $post_id, $credentials );

		set_transient( 'pk_last_credentials', array(
			'login'    => $credentials['login'],
			'password' => $credentials['password'],
			'phone'    => $credentials['phone'],
			'sent'     => $sent,
		), 5 * MINUTE_IN_SECONDS );

		if ( class_exists( 'Partikulier_Cache' ) && method_exists( 'Partikulier_Cache', 'purge_all' ) ) {
			Partikulier_Cache::purge_all();
		}

		return $credentials;
	}

	/**
	 * Prepare identifiant et lien de definition du mot de passe.
	 * Aucun mot de passe en clair n'est produit ni stocke.
	 *
	 * @param int $post_id Annonce.
	 * @return array
	 */
	public static function prepare_credentials( $post_id, $force = false ) {
		$user = get_user_by( 'id', get_post_field( 'post_author', $post_id ) );

		if ( ! $user ) {
			return array(
				'login'    => '',
				'email'    => '',
				'phone'    => (string) get_post_meta( $post_id, '_pk_owner_phone', true ),
				'password' => '',
			);
		}

		// Un annonceur deja servi utilise peut-etre son mot de passe, voire
		// l'a change lui-meme : le reinitialiser en silence l'enfermerait
		// dehors. On ne le regenere donc que sur demande explicite.
		$already = get_user_meta( $user->ID, '_pk_credentials_sent', true );

		if ( $already && ! $force ) {
			return array(
				'user_id'      => $user->ID,
				'login'        => $user->user_login,
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'phone'        => (string) get_post_meta( $post_id, '_pk_owner_phone', true ),
				'password'     => '',
				'reused'       => true,
				'login_url'    => wp_login_url(),
			);
		}

		// Mot de passe lisible au telephone : ni O/0 ni I/l/1, qui se
		// confondent quand on recopie depuis WhatsApp.
		$password = self::readable_password();
		wp_set_password( $password, $user->ID );

		// Trace de l'envoi, jamais le mot de passe lui-meme.
		update_user_meta( $user->ID, '_pk_credentials_sent', current_time( 'mysql' ) );

		return array(
			'user_id'      => $user->ID,
			'login'        => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'phone'        => (string) get_post_meta( $post_id, '_pk_owner_phone', true ),
			'password'     => $password,
			'reused'       => false,
			'login_url'    => wp_login_url(),
		);
	}

	/**
	 * Mot de passe sans caracteres ambigus.
	 *
	 * @return string
	 */
	public static function readable_password() {
		$alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
		$max      = strlen( $alphabet ) - 1;
		$password = '';

		for ( $i = 0; $i < self::PASSWORD_LENGTH; $i++ ) {
			$password .= $alphabet[ wp_rand( 0, $max ) ];
		}

		return $password;
	}

	/* ------------------------------------------------------------------ */
	/* n8n : webhook sortant + route de rattrapage                         */
	/* ------------------------------------------------------------------ */

	/**
	 * Envoie l'evenement de validation au webhook n8n.
	 *
	 * @param int   $post_id     Annonce.
	 * @param array $credentials Identifiants.
	 * @return bool
	 */
	public static function notify_n8n( $post_id, $credentials ) {
		$url = trim( (string) Partikulier_Settings::get( 'n8n_webhook_url' ) );
		if ( ! $url ) {
			return false;
		}

		// wp_http_validate_url() refuse les adresses locales : parfait en
		// production, bloquant pour un n8n auto-heberge sur le meme serveur.
		// On verifie donc la forme, et on laisse WordPress gerer le reste.
		$parts = wp_parse_url( $url );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) || ! in_array( $parts['scheme'], array( 'http', 'https' ), true ) ) {
			update_post_meta( $post_id, '_pk_n8n_error', __( 'URL de webhook invalide.', 'partikulier' ) );

			return false;
		}

		$response = wp_remote_post( $url, array(
			'timeout'  => 8,
			'blocking' => true,
			'headers'  => array(
				'Content-Type'             => 'application/json',
				'X-Partikulier-Automation' => (string) Partikulier_Settings::automation_api_secret(),
			),
			'body'     => wp_json_encode( self::payload( $post_id, $credentials ) ),
		) );

		if ( is_wp_error( $response ) ) {
			update_post_meta( $post_id, '_pk_n8n_error', $response->get_error_message() );

			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			update_post_meta( $post_id, '_pk_n8n_sent', current_time( 'mysql' ) );
			delete_post_meta( $post_id, '_pk_n8n_error' );

			return true;
		}

		update_post_meta( $post_id, '_pk_n8n_error', 'HTTP ' . $code );

		return false;
	}

	/**
	 * Donnees transmises a n8n.
	 *
	 * @param int   $post_id     Annonce.
	 * @param array $credentials Identifiants.
	 * @return array
	 */
	private static function payload( $post_id, $credentials ) {
		return array(
			'event'    => 'listing_approved',
			'listing'  => array(
				'id'    => (int) $post_id,
				'title' => get_the_title( $post_id ),
				'url'   => get_permalink( $post_id ),
				'price' => get_post_meta( $post_id, 'es_property_price', true ),
			),
			'owner'    => array(
				'name'  => $credentials['display_name'] ?? '',
				'phone' => $credentials['phone'] ?? '',
				'email' => $credentials['email'] ?? '',
			),
			'account'  => array(
				'login'     => $credentials['login'] ?? '',
				'password'  => $credentials['password'] ?? '',
				'login_url' => $credentials['login_url'] ?? wp_login_url(),
				// false => l'annonceur a deja recu ses identifiants : n8n doit
				// envoyer un message de mise en ligne, sans identifiants.
				'send_credentials' => ! empty( $credentials['password'] ),
			),
			'sent_at'  => current_time( 'mysql' ),
		);
	}

	/**
	 * Route de rattrapage : n8n peut recuperer les validations recentes.
	 */
	public static function register_routes() {
		register_rest_route(
			'partikulier/v1',
			'/approved-listings',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_approved' ),
				'permission_callback' => array( 'Partikulier_Automation_Bridge', 'check_automation_secret' ),
			)
		);
	}

	/**
	 * Renvoie les annonces validees des 72 dernieres heures.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_approved() {
		$since = gmdate( 'Y-m-d H:i:s', time() - 72 * HOUR_IN_SECONDS );

		$posts = get_posts( array(
			'post_type'        => PARTIKULIER_ESTATIK_POST_TYPE,
			'post_status'      => 'publish',
			'posts_per_page'   => 50,
			'suppress_filters' => true,
			'lang'             => '',
			'meta_query'       => array(
				array(
					'key'     => '_pk_approved_at',
					'value'   => $since,
					'compare' => '>=',
					'type'    => 'DATETIME',
				),
			),
		) );

		$out = array();
		foreach ( $posts as $post ) {
			if ( get_post_meta( $post->ID, '_pk_auto_translation', true ) ) {
				continue;
			}
			$out[] = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'url'          => get_permalink( $post ),
				'approved_at'  => get_post_meta( $post->ID, '_pk_approved_at', true ),
				'owner_phone'  => get_post_meta( $post->ID, '_pk_owner_phone', true ),
				'owner_name'   => get_post_meta( $post->ID, '_pk_owner_name', true ),
				'webhook_sent' => (bool) get_post_meta( $post->ID, '_pk_n8n_sent', true ),
			);
		}

		return new WP_REST_Response( array( 'count' => count( $out ), 'listings' => $out ), 200 );
	}
}

Partikulier_Listing_Approval::init();
