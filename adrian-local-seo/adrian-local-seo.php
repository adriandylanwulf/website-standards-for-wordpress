<?php
/**
 * Plugin Name: Adrian Local SEO
 * Plugin URI: https://www.adriandylanwulf.de/
 * Description: Lokale SEO-Steuerung, Inhaltsprüfung und optionale WordPress-AI-Client-Integration ohne eigenen externen Dienst.
 * Version: 1.0.1
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Author: Adrian Dylan Wulf
 * Author URI: https://www.adriandylanwulf.de/
 * License: GPL-2.0-or-later
 * Text Domain: adrian-local-seo
 *
 * @package Adrian_Local_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADRIAN_LOCAL_SEO_VERSION', '1.0.1' );
define( 'ADRIAN_LOCAL_SEO_FILE', __FILE__ );
define( 'ADRIAN_LOCAL_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADRIAN_LOCAL_SEO_URL', plugin_dir_url( __FILE__ ) );

final class Adrian_Local_SEO {

	const OPTION        = 'adrian_local_seo_settings';
	const AUDIT_OPTION  = 'adrian_local_seo_last_audit';
	const NONCE_ACTION  = 'adrian_local_seo_meta_box';
	const SETTINGS_PAGE = 'adrian-local-seo';

	/**
	 * Singleton instance.
	 *
	 * @var Adrian_Local_SEO|null
	 */
	private static $instance = null;

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private $settings_hook = '';

	/**
	 * Get the plugin instance.
	 *
	 * @return Adrian_Local_SEO
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post_meta' ), 10, 2 );
		add_action( 'admin_post_adrian_local_seo_suggest', array( $this, 'handle_ai_suggestion' ) );

		add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ), 30 );
		add_filter( 'wp_robots', array( $this, 'filter_robots' ), 30 );
		add_action( 'wp_head', array( $this, 'output_archive_canonical' ), 1 );
		add_action( 'wp_head', array( $this, 'output_meta' ), 5 );

		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_ability_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );

		// The theme is allowed to remain the visual owner. Only its duplicate
		// metadata callbacks are removed when this plugin is the active owner.
		add_action( 'after_setup_theme', array( $this, 'neutralize_theme_seo' ), 999 );
	}

	/**
	 * Activation defaults. No content is changed and no network request is made.
	 */
	public static function activate() {
		if ( false === get_option( self::OPTION, false ) ) {
			$provider_active = defined( 'WPSEO_VERSION' )
				|| defined( 'RANK_MATH_VERSION' )
				|| defined( 'AIOSEO_VERSION' )
				|| defined( 'SEOPRESS_VERSION' )
				|| function_exists( 'the_seo_framework' );

			update_option(
				self::OPTION,
				array(
					'mode'                 => $provider_active ? 'audit' : 'takeover',
					'homepage_title'       => 'Adrian Dylan Wulf – Persönliche Notizen',
					'homepage_description' => 'Persönliche Notizen, Erfahrungen und Fotos von Adrian Dylan Wulf zu Technik, Webhosting, digitaler Sicherheit und dem Alltag.',
					'default_description'  => 'Persönliche Notizen von Adrian Dylan Wulf zu Technik, Webhosting, digitaler Sicherheit und dem Alltag.',
					'default_image_id'     => 0,
					'ai_enabled'           => 0,
				),
				false
			);
		}

		flush_rewrite_rules( false );
	}

	/**
	 * Do not remove stored SEO fields on uninstall. The user can remove them
	 * intentionally with a database tool or a future cleanup action.
	 */
	public static function deactivate() {
		flush_rewrite_rules( false );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function defaults() {
		return array(
			'mode'                 => 'audit',
			'homepage_title'       => 'Adrian Dylan Wulf – Persönliche Notizen',
			'homepage_description' => 'Persönliche Notizen, Erfahrungen und Fotos von Adrian Dylan Wulf zu Technik, Webhosting, digitaler Sicherheit und dem Alltag.',
			'default_description'  => 'Persönliche Notizen von Adrian Dylan Wulf zu Technik, Webhosting, digitaler Sicherheit und dem Alltag.',
			'default_image_id'     => 0,
			'ai_enabled'           => 0,
		);
	}

	/**
	 * Read normalized settings.
	 *
	 * @return array
	 */
	private function settings() {
		$settings = get_option( self::OPTION, array() );

		return wp_parse_args( is_array( $settings ) ? $settings : array(), $this->defaults() );
	}

	/**
	 * Whether another SEO provider owns public metadata.
	 *
	 * @return bool
	 */
	private function external_provider_active() {
		return defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| function_exists( 'the_seo_framework' );
	}

	/**
	 * Whether this plugin may emit public SEO metadata.
	 *
	 * @return bool
	 */
	public function is_authority() {
		$settings = $this->settings();

		return 'takeover' === $settings['mode'] && ! $this->external_provider_active();
	}

	/**
	 * Public helper for a theme bridge or other local integration.
	 *
	 * @return bool
	 */
	public function is_ai_enabled() {
		$settings = $this->settings();

		return ! empty( $settings['ai_enabled'] );
	}

	/**
	 * Register settings safely through the Settings API.
	 */
	public function register_settings() {
		register_setting(
			'adrian_local_seo',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults(),
			)
		);
	}

	/**
	 * Sanitize settings. AI remains opt-in and does not run while saving.
	 *
	 * @param mixed $input Raw settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = $this->defaults();
		$mode     = isset( $input['mode'] ) ? sanitize_key( $input['mode'] ) : $defaults['mode'];

		if ( ! in_array( $mode, array( 'audit', 'takeover' ), true ) ) {
			$mode = 'audit';
		}

		$description = isset( $input['default_description'] )
			? sanitize_textarea_field( wp_unslash( $input['default_description'] ) )
			: $defaults['default_description'];
		$homepage_title = isset( $input['homepage_title'] )
			? sanitize_text_field( wp_unslash( $input['homepage_title'] ) )
			: $defaults['homepage_title'];
		$homepage_description = isset( $input['homepage_description'] )
			? sanitize_textarea_field( wp_unslash( $input['homepage_description'] ) )
			: $defaults['homepage_description'];

		return array(
			'mode'                 => $mode,
			'homepage_title'       => $this->limit_title( $homepage_title ),
			'homepage_description' => $this->limit_description( $homepage_description ),
			'default_description'  => $this->limit_description( $description ),
			'default_image_id'     => isset( $input['default_image_id'] ) ? absint( $input['default_image_id'] ) : 0,
			'ai_enabled'           => ! empty( $input['ai_enabled'] ) ? 1 : 0,
		);
	}

	/**
	 * Register meta fields for REST-visible editorial tooling without making
	 * them publicly readable. The auth callback stays administrator/editor-only.
	 */
	public function register_meta() {
		$auth = function () {
			return current_user_can( 'edit_posts' );
		};

		foreach ( array( 'post', 'page' ) as $post_type ) {
			register_post_meta(
				$post_type,
				'_adrian_seo_title',
				array(
					'single'       => true,
					'type'         => 'string',
					'show_in_rest' => false,
					'auth_callback' => $auth,
				)
			);
			register_post_meta(
				$post_type,
				'_adrian_seo_description',
				array(
					'single'       => true,
					'type'         => 'string',
					'show_in_rest' => false,
					'auth_callback' => $auth,
				)
			);
			register_post_meta(
				$post_type,
				'_adrian_seo_canonical',
				array(
					'single'       => true,
					'type'         => 'string',
					'show_in_rest' => false,
					'auth_callback' => $auth,
				)
			);
			register_post_meta(
				$post_type,
				'_adrian_seo_robots',
				array(
					'single'       => true,
					'type'         => 'string',
					'show_in_rest' => false,
					'auth_callback' => $auth,
				)
			);
		}
	}

	/**
	 * Add the settings screen.
	 */
	public function register_admin_menu() {
		$this->settings_hook = add_options_page(
			__( 'Adrian Local SEO', 'adrian-local-seo' ),
			__( 'Adrian SEO', 'adrian-local-seo' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Load a tiny local admin stylesheet only on this plugin's page.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( $this->settings_hook !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'adrian-local-seo-admin',
			ADRIAN_LOCAL_SEO_URL . 'assets/admin.css',
			array(),
			ADRIAN_LOCAL_SEO_VERSION
		);
	}

	/**
	 * Add a focused editing panel to posts and pages.
	 */
	public function register_meta_box() {
		foreach ( array( 'post', 'page' ) as $post_type ) {
			add_meta_box(
				'adrian-local-seo',
				__( 'Adrian SEO', 'adrian-local-seo' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the post editor panel.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		$title       = get_post_meta( $post->ID, '_adrian_seo_title', true );
		$description = get_post_meta( $post->ID, '_adrian_seo_description', true );
		$canonical   = get_post_meta( $post->ID, '_adrian_seo_canonical', true );
		$robots      = get_post_meta( $post->ID, '_adrian_seo_robots', true );
		$suggestion  = get_transient( 'adrian_local_seo_suggestion_' . get_current_user_id() . '_' . $post->ID );

		wp_nonce_field( self::NONCE_ACTION, 'adrian_local_seo_meta_nonce' );
		?>
		<p class="description"><?php esc_html_e( 'Die Felder sind optional. Leer bedeutet: lokale Standardwerte und der vorhandene Inhalt werden verwendet.', 'adrian-local-seo' ); ?></p>
		<p>
			<label for="adrian_seo_title"><strong><?php esc_html_e( 'SEO-Titel', 'adrian-local-seo' ); ?></strong></label><br>
			<input class="widefat" type="text" id="adrian_seo_title" name="adrian_seo_title" value="<?php echo esc_attr( $title ); ?>" maxlength="70">
		</p>
		<p>
			<label for="adrian_seo_description"><strong><?php esc_html_e( 'Meta-Beschreibung', 'adrian-local-seo' ); ?></strong></label><br>
			<textarea class="widefat" rows="3" id="adrian_seo_description" name="adrian_seo_description" maxlength="160"><?php echo esc_textarea( $description ); ?></textarea>
		</p>
		<p>
			<label for="adrian_seo_canonical"><strong><?php esc_html_e( 'Kanonische URL', 'adrian-local-seo' ); ?></strong></label><br>
			<input class="widefat" type="url" id="adrian_seo_canonical" name="adrian_seo_canonical" value="<?php echo esc_attr( $canonical ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>">
		</p>
		<p>
			<label for="adrian_seo_robots"><strong><?php esc_html_e( 'Robots', 'adrian-local-seo' ); ?></strong></label><br>
			<select id="adrian_seo_robots" name="adrian_seo_robots">
				<option value="" <?php selected( $robots, '' ); ?>><?php esc_html_e( 'Standard', 'adrian-local-seo' ); ?></option>
				<option value="index,follow" <?php selected( $robots, 'index,follow' ); ?>>index, follow</option>
				<option value="noindex,follow" <?php selected( $robots, 'noindex,follow' ); ?>>noindex, follow</option>
			</select>
		</p>
		<?php if ( $this->is_ai_enabled() ) : ?>
			<hr>
			<p><strong><?php esc_html_e( 'Manueller KI-Vorschlag', 'adrian-local-seo' ); ?></strong></p>
			<p class="description"><?php esc_html_e( 'Nur auf Klick. Der Vorschlag wird nicht automatisch gespeichert oder veröffentlicht. Je nach WordPress-Konnektor kann der ausgewählte Inhalt an den dort konfigurierten Anbieter übertragen werden.', 'adrian-local-seo' ); ?></p>
			<p>
				<?php if ( $this->ai_status()['available'] ) : ?>
					<button type="submit" class="button" form="adrian-local-seo-ai-form"><?php esc_html_e( 'Vorschlag für Titel und Beschreibung erzeugen', 'adrian-local-seo' ); ?></button>
				<?php else : ?>
					<span class="description"><?php esc_html_e( 'Die WordPress-AI-Schnittstelle ist derzeit nicht verfügbar oder nicht freigegeben.', 'adrian-local-seo' ); ?></span>
				<?php endif; ?>
			</p>
			<?php if ( is_array( $suggestion ) ) : ?>
				<div class="notice notice-info inline">
					<p><strong><?php esc_html_e( 'Vorschlag – bitte redaktionell prüfen', 'adrian-local-seo' ); ?></strong></p>
					<p><strong><?php esc_html_e( 'Titel:', 'adrian-local-seo' ); ?></strong> <?php echo esc_html( isset( $suggestion['title'] ) ? $suggestion['title'] : '' ); ?></p>
					<p><strong><?php esc_html_e( 'Beschreibung:', 'adrian-local-seo' ); ?></strong> <?php echo esc_html( isset( $suggestion['description'] ) ? $suggestion['description'] : '' ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( $this->is_ai_enabled() ) : ?>
			<form id="adrian-local-seo-ai-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="adrian_local_seo_suggest">
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>">
				<?php wp_nonce_field( 'adrian_local_seo_ai_' . $post->ID, 'adrian_local_seo_ai_nonce' ); ?>
			</form>
		<?php endif; ?>
		<?php
	}

	/**
	 * Persist editorial fields with strict allow-lists.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_post_meta( $post_id, $post ) {
		if ( ! isset( $_POST['adrian_local_seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['adrian_local_seo_meta_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || ! in_array( $post->post_type, array( 'post', 'page' ), true ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$title       = isset( $_POST['adrian_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['adrian_seo_title'] ) ) : '';
		$description = isset( $_POST['adrian_seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['adrian_seo_description'] ) ) : '';
		$canonical   = isset( $_POST['adrian_seo_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['adrian_seo_canonical'] ) ) : '';
		$robots      = isset( $_POST['adrian_seo_robots'] ) ? sanitize_key( wp_unslash( $_POST['adrian_seo_robots'] ) ) : '';

		if ( '' !== $canonical && ! $this->is_same_site_url( $canonical ) ) {
			$canonical = '';
		}

		if ( '' !== $title ) {
			update_post_meta( $post_id, '_adrian_seo_title', $this->limit_title( $title ) );
		} else {
			delete_post_meta( $post_id, '_adrian_seo_title' );
		}

		if ( '' !== $description ) {
			update_post_meta( $post_id, '_adrian_seo_description', $this->limit_description( $description ) );
		} else {
			delete_post_meta( $post_id, '_adrian_seo_description' );
		}

		if ( '' !== $canonical ) {
			update_post_meta( $post_id, '_adrian_seo_canonical', $canonical );
		} else {
			delete_post_meta( $post_id, '_adrian_seo_canonical' );
		}

		if ( in_array( $robots, array( 'index,follow', 'noindex,follow' ), true ) ) {
			update_post_meta( $post_id, '_adrian_seo_robots', $robots );
		} else {
			delete_post_meta( $post_id, '_adrian_seo_robots' );
		}
	}

	/**
	 * Prevent theme-level duplicate metadata when this plugin is active owner.
	 */
	public function neutralize_theme_seo() {
		if ( ! $this->is_authority() ) {
			return;
		}

		if ( function_exists( 'dylan_journal_seo_meta' ) ) {
			remove_action( 'wp_head', 'dylan_journal_seo_meta', 5 );
		}
		if ( function_exists( 'dylan_journal_archive_canonical' ) ) {
			remove_action( 'wp_head', 'dylan_journal_archive_canonical', 1 );
		}
	}

	/**
	 * Use an editorial SEO title only when one exists; otherwise keep the
	 * theme/core title untouched.
	 *
	 * @param string $title Current document title.
	 * @return string
	 */
	public function filter_document_title( $title ) {
		if ( ! $this->is_authority() ) {
			return $title;
		}

		if ( is_front_page() ) {
			$homepage_title = $this->settings()['homepage_title'];
			if ( '' !== trim( (string) $homepage_title ) ) {
				return $this->limit_title( $homepage_title );
			}
		}

		if ( ! is_singular() ) {
			return $title;
		}

		$custom = get_post_meta( get_queried_object_id(), '_adrian_seo_title', true );

		return '' !== $custom ? $this->limit_title( $custom ) : $title;
	}

	/**
	 * Add safe noindex rules when the theme does not already provide them.
	 *
	 * @param array $robots Existing directives.
	 * @return array
	 */
	public function filter_robots( $robots ) {
		if ( ! $this->is_authority() ) {
			return $robots;
		}

		if ( is_singular() ) {
			$custom = get_post_meta( get_queried_object_id(), '_adrian_seo_robots', true );
			if ( 'noindex,follow' === $custom ) {
				unset( $robots['index'] );
				$robots['noindex'] = true;
				$robots['follow']  = true;
			}
		}

		if ( is_singular() && post_password_required() ) {
			unset( $robots['index'] );
			$robots['noindex']   = true;
			$robots['noarchive'] = true;
			$robots['follow']    = true;
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term && 0 === (int) $term->count ) {
				unset( $robots['index'] );
				$robots['noindex'] = true;
				$robots['follow']  = true;
			}
		}

		return $robots;
	}

	/**
	 * Add archive canonicals; WordPress core covers singular canonicals.
	 */
	public function output_archive_canonical() {
		if ( ! $this->is_authority() || is_admin() || is_feed() || is_404() || is_search() ) {
			return;
		}

		if ( ! is_home() && ! is_category() && ! is_tag() && ! is_tax() ) {
			return;
		}

		$url = get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) );
		if ( $url ) {
			printf( '<link rel="canonical" href="%s" />%s', esc_url( $url ), "\n" );
		}
	}

	/**
	 * Emit local description, social metadata and lightweight structured data.
	 */
	public function output_meta() {
		if ( ! $this->is_authority() || is_admin() || is_feed() || is_robots() || is_404() || is_search() ) {
			return;
		}

		if ( is_singular() && post_password_required() ) {
			return;
		}

		$description = $this->get_description();
		if ( '' === $description ) {
			return;
		}

		$title      = wp_get_document_title();
		$site_name  = get_bloginfo( 'name' );
		$locale     = get_locale();
		$language   = str_replace( '_', '-', $locale );
		$url        = $this->get_url();
		$is_article = is_singular( 'post' );
		$image      = $this->get_social_image();
		$type       = $is_article ? 'article' : 'website';

		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
		echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

		if ( $is_article ) {
			echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . "\n";
			echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( DATE_W3C ) ) . '">' . "\n";
		}

		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image['url'] ) . '">' . "\n";
			echo '<meta name="twitter:image" content="' . esc_url( $image['url'] ) . '">' . "\n";
			if ( ! empty( $image['alt'] ) ) {
				echo '<meta property="og:image:alt" content="' . esc_attr( $image['alt'] ) . '">' . "\n";
				echo '<meta name="twitter:image:alt" content="' . esc_attr( $image['alt'] ) . '">' . "\n";
			}
		}

		$schema = $this->get_schema( $url, $description, $title, $language, $image, $is_article );
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n";

		if ( $is_article ) {
			$breadcrumb = $this->get_breadcrumb_schema();
			echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n";
		}
	}

	/**
	 * Return a safe description from the post override, the theme's existing
	 * editorial fallback, or the site setting.
	 *
	 * @return string
	 */
	private function get_description() {
		if ( is_front_page() ) {
			$homepage_description = $this->settings()['homepage_description'];
			if ( '' !== trim( (string) $homepage_description ) ) {
				return $this->limit_description( $homepage_description );
			}
		}

		if ( is_singular() ) {
			$custom = get_post_meta( get_queried_object_id(), '_adrian_seo_description', true );
			if ( '' !== $custom ) {
				return $this->limit_description( $custom );
			}
		}

		if ( function_exists( 'dylan_journal_seo_description' ) ) {
			$theme_description = dylan_journal_seo_description();
			if ( '' !== trim( (string) $theme_description ) ) {
				return $this->limit_description( $theme_description );
			}
		}

		if ( is_front_page() ) {
			return $this->limit_description( $this->settings()['default_description'] );
		}

		if ( is_singular() ) {
			$content = get_post_field( 'post_content', get_queried_object_id() );
			return $this->limit_description( wp_strip_all_tags( strip_shortcodes( (string) $content ) ) );
		}

		return $this->limit_description( get_bloginfo( 'description' ) );
	}

	/**
	 * Return the current canonical URL.
	 *
	 * @return string
	 */
	private function get_url() {
		if ( is_singular() ) {
			$custom = get_post_meta( get_queried_object_id(), '_adrian_seo_canonical', true );
			if ( '' !== $custom && $this->is_same_site_url( $custom ) ) {
				return $custom;
			}
			return (string) get_permalink();
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term_url = get_term_link( get_queried_object() );
			if ( ! is_wp_error( $term_url ) ) {
				return (string) $term_url;
			}
		}

		if ( is_home() && (int) get_option( 'page_for_posts' ) ) {
			return (string) get_permalink( (int) get_option( 'page_for_posts' ) );
		}

		return home_url( '/' );
	}

	/**
	 * Return a social image from the post, configured default or theme fallback.
	 *
	 * @return array|null
	 */
	private function get_social_image() {
		$image_id = 0;
		if ( is_singular() && has_post_thumbnail() ) {
			$image_id = get_post_thumbnail_id( get_queried_object_id() );
		}
		if ( ! $image_id ) {
			$image_id = absint( $this->settings()['default_image_id'] );
		}
		if ( ! $image_id && function_exists( 'dylan_journal_social_fallback_image_id' ) ) {
			$image_id = absint( dylan_journal_social_fallback_image_id() );
		}
		if ( ! $image_id ) {
			return null;
		}

		$url = wp_get_attachment_image_url( $image_id, 'large' );
		if ( ! $url ) {
			return null;
		}

		return array(
			'url' => $url,
			'alt' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
		);
	}

	/**
	 * Build a compact schema object.
	 *
	 * @param string      $url         Current URL.
	 * @param string      $description Description.
	 * @param string      $title       Title.
	 * @param string      $language    Language.
	 * @param array|null  $image       Social image.
	 * @param bool        $is_article  Article flag.
	 * @return array
	 */
	private function get_schema( $url, $description, $title, $language, $image, $is_article ) {
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => $is_article ? 'BlogPosting' : ( is_home() || is_archive() ? 'CollectionPage' : 'WebPage' ),
			'@id'         => trailingslashit( $url ) . '#page',
			'name'        => wp_specialchars_decode( wp_strip_all_tags( $title ), ENT_QUOTES ),
			'url'         => $url,
			'description' => $description,
			'inLanguage'  => $language,
			'isPartOf'    => array(
				'@type' => 'WebSite',
				'@id'   => home_url( '/#website' ),
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			),
		);

		if ( $is_article ) {
			$schema['headline']      = wp_specialchars_decode( wp_strip_all_tags( get_the_title() ), ENT_QUOTES );
			$schema['datePublished'] = get_the_date( DATE_W3C );
			$schema['dateModified']  = get_the_modified_date( DATE_W3C );
			$schema['author']        = array(
				'@type' => 'Person',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			);
		}

		if ( $image ) {
			$schema['image'] = $image['url'];
		}

		return $schema;
	}

	/**
	 * Build article breadcrumbs.
	 *
	 * @return array
	 */
	private function get_breadcrumb_schema() {
		$items = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => 'Startseite',
				'item'     => home_url( '/' ),
			),
			array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => 'Blog',
				'item'     => function_exists( 'dylan_journal_page_url' ) ? dylan_journal_page_url( 'blog', '/blog/' ) : home_url( '/blog/' ),
			),
		);

		$categories = get_the_category( get_queried_object_id() );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => wp_specialchars_decode( wp_strip_all_tags( $categories[0]->name ), ENT_QUOTES ),
				'item'     => get_category_link( $categories[0]->term_id ),
			);
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'name'     => wp_specialchars_decode( wp_strip_all_tags( get_the_title() ), ENT_QUOTES ),
			'item'     => get_permalink(),
		);

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	/**
	 * Register local REST endpoints for future maintenance tooling.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'adrian-seo/v1',
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_status' ),
				'permission_callback' => array( $this, 'rest_manage_permission' ),
			)
		);
		register_rest_route(
			'adrian-seo/v1',
			'/audit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_audit' ),
				'permission_callback' => array( $this, 'rest_manage_permission' ),
			)
		);
		register_rest_route(
			'adrian-seo/v1',
			'/suggest',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_suggest' ),
				'permission_callback' => array( $this, 'rest_manage_permission' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) {
							return absint( $value ) > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * REST permission callback.
	 *
	 * @return bool
	 */
	public function rest_manage_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Return status without secrets or provider credentials.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_status() {
		$settings = $this->settings();

		return rest_ensure_response(
			array(
				'plugin_version'     => ADRIAN_LOCAL_SEO_VERSION,
				'mode'               => $settings['mode'],
				'active_authority'   => $this->is_authority(),
				'external_seo_plugin' => $this->external_provider_active(),
				'ai'                 => $this->ai_status(),
				'last_audit'         => get_option( self::AUDIT_OPTION, array() ),
			)
		);
	}

	/**
	 * Run a local audit via REST.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_audit() {
		return rest_ensure_response( $this->run_audit() );
	}

	/**
	 * Generate one manual suggestion via REST; never save it.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_suggest( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		$result  = $this->generate_ai_suggestion( $post_id );

		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Register Abilities where WordPress provides the API. This lets an
	 * authenticated MCP/agent integration discover narrowly scoped operations.
	 */
	public function register_ability_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'adrian-local-seo',
			array(
				'label'       => __( 'Adrian Local SEO', 'adrian-local-seo' ),
				'description' => __( 'Lokale SEO-Prüfungen und ausdrücklich angeforderte Metadaten-Vorschläge.', 'adrian-local-seo' ),
			)
		);
	}

	/**
	 * Register two narrowly scoped abilities. They never publish posts or alter
	 * metadata automatically.
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'adrian-local-seo/run-audit',
			array(
				'label'               => __( 'Lokale SEO-Prüfung ausführen', 'adrian-local-seo' ),
				'description'         => __( 'Prüft veröffentlichte Inhalte lokal auf grundlegende SEO-Punkte, ohne Daten an einen externen Dienst zu übertragen.', 'adrian-local-seo' ),
				'category'            => 'adrian-local-seo',
				'input_schema'        => array( 'type' => 'object', 'properties' => array() ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( $this, 'ability_run_audit' ),
				'permission_callback' => array( $this, 'rest_manage_permission' ),
				'meta'                => array( 'show_in_rest' => true ),
			)
		);

		wp_register_ability(
			'adrian-local-seo/suggest-metadata',
			array(
				'label'               => __( 'SEO-Metadaten vorschlagen', 'adrian-local-seo' ),
				'description'         => __( 'Erzeugt auf ausdrücklichen Abruf einen redaktionell zu prüfenden Vorschlag für einen Beitrag. Speichert oder veröffentlicht nichts automatisch.', 'adrian-local-seo' ),
				'category'            => 'adrian-local-seo',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array( 'type' => 'integer', 'description' => 'ID eines Beitrags oder einer Seite.' ),
					),
					'required'   => array( 'post_id' ),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( $this, 'ability_suggest_metadata' ),
				'permission_callback' => array( $this, 'rest_manage_permission' ),
				'meta'                => array( 'show_in_rest' => true ),
			)
		);
	}

	/**
	 * Ability callback.
	 *
	 * @return array
	 */
	public function ability_run_audit() {
		return $this->run_audit();
	}

	/**
	 * Ability callback.
	 *
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public function ability_suggest_metadata( $input ) {
		$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;

		return $this->generate_ai_suggestion( $post_id );
	}

	/**
	 * Run an inexpensive local content audit and cache only the result.
	 *
	 * @return array
	 */
	private function run_audit() {
		$ids = get_posts(
			array(
				'post_type'              => array( 'post', 'page' ),
				'post_status'            => 'publish',
				'posts_per_page'         => 500,
				'fields'                => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$result = array(
			'generated_at'          => current_time( 'mysql' ),
			'published_content'     => count( $ids ),
			'missing_featured_image' => 0,
			'long_titles'           => 0,
			'long_descriptions'     => 0,
			'customized_metadata'   => 0,
			'empty_categories'      => 0,
			'empty_tags'            => 0,
		);

		foreach ( $ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}
			if ( 'post' === $post->post_type && ! has_post_thumbnail( $post_id ) ) {
				$result['missing_featured_image']++;
			}
			$title = wp_strip_all_tags( get_the_title( $post_id ) );
			$length = function_exists( 'mb_strlen' ) ? mb_strlen( $title, 'UTF-8' ) : strlen( $title );
			if ( $length > 60 ) {
				$result['long_titles']++;
			}
			$description = get_post_meta( $post_id, '_adrian_seo_description', true );
			if ( '' !== $description ) {
				$result['customized_metadata']++;
				$description_length = function_exists( 'mb_strlen' ) ? mb_strlen( $description, 'UTF-8' ) : strlen( $description );
				if ( $description_length > 160 ) {
					$result['long_descriptions']++;
				}
			}
		}

		foreach ( array( 'category' => 'empty_categories', 'post_tag' => 'empty_tags' ) as $taxonomy => $key ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term_id ) {
					$term = get_term( $term_id, $taxonomy );
					if ( $term && 0 === (int) $term->count ) {
						$result[ $key ]++;
					}
				}
			}
		}

		update_option( self::AUDIT_OPTION, $result, false );

		return $result;
	}

	/**
	 * Show the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle the audit action before any page markup is sent so a redirect
		// cannot be lost to already-sent response headers.
		$this->maybe_handle_settings_action();

		$settings   = $this->settings();
		$audit      = get_option( self::AUDIT_OPTION, array() );
		$ai         = $this->ai_status();
		$last_audit = isset( $audit['generated_at'] ) ? $audit['generated_at'] : __( 'Noch nicht ausgeführt', 'adrian-local-seo' );
		?>
		<div class="wrap adrian-local-seo-wrap">
			<h1><?php esc_html_e( 'Adrian Local SEO', 'adrian-local-seo' ); ?></h1>
			<p class="adrian-local-seo-lead"><?php esc_html_e( 'Schlanke lokale SEO-Steuerung für diese Website. Es werden keine eigenen externen SEO-Dienste angesprochen.', 'adrian-local-seo' ); ?></p>

			<div class="adrian-local-seo-grid">
				<section class="adrian-local-seo-card">
					<h2><?php esc_html_e( 'Status', 'adrian-local-seo' ); ?></h2>
					<dl>
						<dt><?php esc_html_e( 'Öffentliche SEO-Ausgabe', 'adrian-local-seo' ); ?></dt>
						<dd><strong><?php echo $this->is_authority() ? esc_html__( 'Aktiv', 'adrian-local-seo' ) : esc_html__( 'Audit-Modus', 'adrian-local-seo' ); ?></strong></dd>
						<dt><?php esc_html_e( 'Anderer SEO-Anbieter', 'adrian-local-seo' ); ?></dt>
						<dd><?php echo $this->external_provider_active() ? esc_html__( 'Erkannt – dieser Anbieter bleibt zuständig.', 'adrian-local-seo' ) : esc_html__( 'Keiner erkannt.', 'adrian-local-seo' ); ?></dd>
						<dt><?php esc_html_e( 'Letzte lokale Prüfung', 'adrian-local-seo' ); ?></dt>
						<dd><?php echo esc_html( $last_audit ); ?></dd>
					</dl>
				</section>

				<section class="adrian-local-seo-card">
					<h2><?php esc_html_e( 'Lokale Prüfung', 'adrian-local-seo' ); ?></h2>
					<p><?php esc_html_e( 'Die Prüfung liest nur lokale WordPress-Inhalte und speichert eine kleine Zusammenfassung. Sie ruft keine externe URL auf.', 'adrian-local-seo' ); ?></p>
					<form method="post">
						<?php wp_nonce_field( 'adrian_local_seo_run_audit', 'adrian_local_seo_audit_nonce' ); ?>
						<input type="hidden" name="adrian_local_seo_action" value="run_audit">
						<?php submit_button( __( 'Lokale Prüfung starten', 'adrian-local-seo' ), 'secondary', 'submit', false ); ?>
					</form>
				</section>
			</div>

			<?php if ( ! empty( $audit ) ) : ?>
				<section class="adrian-local-seo-card adrian-local-seo-audit">
					<h2><?php esc_html_e( 'Prüfergebnis', 'adrian-local-seo' ); ?></h2>
					<div class="adrian-local-seo-metrics">
						<?php $this->metric( __( 'Veröffentlichte Inhalte', 'adrian-local-seo' ), $audit['published_content'] ); ?>
						<?php $this->metric( __( 'Beiträge ohne Titelbild', 'adrian-local-seo' ), $audit['missing_featured_image'] ); ?>
						<?php $this->metric( __( 'Leere Kategorien', 'adrian-local-seo' ), $audit['empty_categories'] ); ?>
						<?php $this->metric( __( 'Leere Schlagwörter', 'adrian-local-seo' ), $audit['empty_tags'] ); ?>
						<?php $this->metric( __( 'Lange Titel', 'adrian-local-seo' ), $audit['long_titles'] ); ?>
					</div>
				</section>
			<?php endif; ?>

			<form method="post" action="options.php" class="adrian-local-seo-card adrian-local-seo-form">
				<?php
				settings_fields( 'adrian_local_seo' );
				?>
				<h2><?php esc_html_e( 'Ausgabe und Standardwerte', 'adrian-local-seo' ); ?></h2>
				<p>
					<label for="adrian_local_seo_mode"><strong><?php esc_html_e( 'Betriebsart', 'adrian-local-seo' ); ?></strong></label><br>
					<select id="adrian_local_seo_mode" name="<?php echo esc_attr( self::OPTION ); ?>[mode]">
						<option value="audit" <?php selected( $settings['mode'], 'audit' ); ?>><?php esc_html_e( 'Nur prüfen (keine öffentliche SEO-Ausgabe)', 'adrian-local-seo' ); ?></option>
						<option value="takeover" <?php selected( $settings['mode'], 'takeover' ); ?>><?php esc_html_e( 'Lokale Ausgabe verwenden', 'adrian-local-seo' ); ?></option>
					</select>
				</p>
				<p class="description"><?php esc_html_e( 'Die lokale Ausgabe sollte nur aktiv sein, wenn kein anderer SEO-Anbieter aktiv ist. Das Plugin entfernt dann die doppelten SEO-Ausgaben des aktuellen Themes, ohne das Design zu verändern.', 'adrian-local-seo' ); ?></p>
				<p>
					<label for="adrian_local_seo_homepage_title"><strong><?php esc_html_e( 'Startseiten-SEO-Titel', 'adrian-local-seo' ); ?></strong></label><br>
					<input class="large-text" type="text" maxlength="70" id="adrian_local_seo_homepage_title" name="<?php echo esc_attr( self::OPTION ); ?>[homepage_title]" value="<?php echo esc_attr( $settings['homepage_title'] ); ?>">
				</p>
				<p class="description"><?php esc_html_e( 'Wird nur auf der Startseite verwendet. Leer lassen, wenn der WordPress-/Theme-Titel beibehalten werden soll.', 'adrian-local-seo' ); ?></p>
				<p>
					<label for="adrian_local_seo_homepage_description"><strong><?php esc_html_e( 'Startseiten-Meta-Beschreibung', 'adrian-local-seo' ); ?></strong></label><br>
					<textarea class="large-text" rows="3" maxlength="160" id="adrian_local_seo_homepage_description" name="<?php echo esc_attr( self::OPTION ); ?>[homepage_description]"><?php echo esc_textarea( $settings['homepage_description'] ); ?></textarea>
				</p>
				<p class="description"><?php esc_html_e( 'Diese Beschreibung wird für Suchmaschinen und Social-Media-Vorschauen der Startseite verwendet.', 'adrian-local-seo' ); ?></p>
				<p>
					<label for="adrian_local_seo_default_description"><strong><?php esc_html_e( 'Standardbeschreibung', 'adrian-local-seo' ); ?></strong></label><br>
					<textarea class="large-text" rows="3" maxlength="160" id="adrian_local_seo_default_description" name="<?php echo esc_attr( self::OPTION ); ?>[default_description]"><?php echo esc_textarea( $settings['default_description'] ); ?></textarea>
				</p>
				<p>
					<label for="adrian_local_seo_default_image_id"><strong><?php esc_html_e( 'Standardbild-ID (optional)', 'adrian-local-seo' ); ?></strong></label><br>
					<input class="small-text" type="number" min="0" id="adrian_local_seo_default_image_id" name="<?php echo esc_attr( self::OPTION ); ?>[default_image_id]" value="<?php echo esc_attr( $settings['default_image_id'] ); ?>">
				</p>

				<h2><?php esc_html_e( 'Optionale WordPress-KI', 'adrian-local-seo' ); ?></h2>
				<p class="adrian-local-seo-warning"><?php esc_html_e( 'Standardmäßig ausgeschaltet. Wenn du sie aktivierst und einen WordPress-Konnektor freigibst, können ausgewählte Inhalte bei einem manuellen Vorschlag an den dort eingerichteten Anbieter übertragen werden. Es gibt keine automatische Übertragung, keinen Zeitplan und keine automatische Veröffentlichung.', 'adrian-local-seo' ); ?></p>
				<p>
					<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[ai_enabled]" value="1" <?php checked( $settings['ai_enabled'], 1 ); ?>> <?php esc_html_e( 'Manuelle KI-Vorschläge im Beitragseditor erlauben', 'adrian-local-seo' ); ?></label>
				</p>
				<p class="description"><?php echo esc_html( $ai['message'] ); ?></p>
				<?php submit_button( __( 'Einstellungen speichern', 'adrian-local-seo' ), 'primary' ); ?>
			</form>

			<section class="adrian-local-seo-card adrian-local-seo-privacy">
				<h2><?php esc_html_e( 'Sicherheits- und Datenschutzmodell', 'adrian-local-seo' ); ?></h2>
				<ul>
					<li><?php esc_html_e( 'Audit, Metadaten und strukturierte Daten werden lokal in WordPress erzeugt.', 'adrian-local-seo' ); ?></li>
					<li><?php esc_html_e( 'Es werden keine API-Schlüssel gespeichert und keine eigenen externen HTTP-Aufrufe ausgeführt.', 'adrian-local-seo' ); ?></li>
					<li><?php esc_html_e( 'REST und Abilities sind ausschließlich für Administratoren freigegeben.', 'adrian-local-seo' ); ?></li>
					<li><?php esc_html_e( 'KI-Vorschläge werden nie automatisch in Inhalte übernommen oder veröffentlicht.', 'adrian-local-seo' ); ?></li>
				</ul>
			</section>
		</div>
		<?php
	}

	/**
	 * Handle the audit button before the page form is rendered.
	 */
	private function maybe_handle_settings_action() {
		if ( empty( $_POST['adrian_local_seo_action'] ) || 'run_audit' !== $_POST['adrian_local_seo_action'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) || empty( $_POST['adrian_local_seo_audit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['adrian_local_seo_audit_nonce'] ) ), 'adrian_local_seo_run_audit' ) ) {
			return;
		}

		$this->run_audit();
		wp_safe_redirect( add_query_arg( array( 'page' => self::SETTINGS_PAGE, 'audit' => 'done' ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Output one audit metric.
	 *
	 * @param string $label Metric label.
	 * @param int    $value Metric value.
	 */
	private function metric( $label, $value ) {
		?>
		<div class="adrian-local-seo-metric">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (int) $value ) ); ?></strong>
		</div>
		<?php
	}

	/**
	 * AI capability status. The support check is deterministic and does not
	 * make an AI request.
	 *
	 * @return array
	 */
	private function ai_status() {
		if ( ! $this->is_ai_enabled() ) {
			return array(
				'available' => false,
				'code'      => 'disabled',
				'message'   => __( 'Die optionale KI-Funktion ist ausgeschaltet.', 'adrian-local-seo' ),
			);
		}

		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return array(
				'available' => false,
				'code'      => 'client_missing',
				'message'   => __( 'Der WordPress AI Client ist auf dieser Installation nicht verfügbar.', 'adrian-local-seo' ),
			);
		}

		try {
			$builder = wp_ai_client_prompt( 'SEO-Metadaten für einen WordPress-Beitrag.' );
			$supported = is_object( $builder ) && method_exists( $builder, 'is_supported_for_text_generation' )
				? (bool) $builder->is_supported_for_text_generation()
				: false;
		} catch ( Throwable $error ) {
			$supported = false;
		}

		return array(
			'available' => $supported,
			'code'      => $supported ? 'ready' : 'not_configured',
			'message'   => $supported
				? __( 'Ein freigegebener WordPress-Konnektor ist verfügbar. Der eigentliche Aufruf erfolgt nur manuell.', 'adrian-local-seo' )
				: __( 'Kein freigegebener oder passender WordPress-Konnektor ist verfügbar.', 'adrian-local-seo' ),
		);
	}

	/**
	 * Generate a narrowly scoped suggestion. Nothing is saved.
	 *
	 * @param int $post_id Post ID.
	 * @return array|WP_Error
	 */
	private function generate_ai_suggestion( $post_id ) {
		if ( ! $this->is_ai_enabled() || ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'ai_disabled', __( 'Die optionale KI-Funktion ist nicht freigegeben.', 'adrian-local-seo' ), array( 'status' => 403 ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'invalid_post', __( 'Der angegebene Inhalt ist nicht zulässig.', 'adrian-local-seo' ), array( 'status' => 400 ) );
		}

		$status = $this->ai_status();
		if ( ! $status['available'] ) {
			return new WP_Error( 'ai_unavailable', $status['message'], array( 'status' => 503 ) );
		}

		$content = wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );
		$content = function_exists( 'mb_substr' ) ? mb_substr( $content, 0, 5000, 'UTF-8' ) : substr( $content, 0, 5000 );
		$prompt  = 'Erstelle für den folgenden deutschsprachigen WordPress-Inhalt einen sachlichen SEO-Titel mit maximal 60 Zeichen und eine natürliche Meta-Beschreibung mit maximal 155 Zeichen. Schreibe persönlich und konkret, ohne Werbesprache und ohne den Eindruck automatisierter Texte. Antworte ausschließlich als JSON-Objekt mit den Schlüsseln "title" und "description". Titel: ' . wp_strip_all_tags( $post->post_title ) . ' Inhalt: ' . $content;

		try {
			$builder = wp_ai_client_prompt( $prompt );
			if ( is_object( $builder ) && method_exists( $builder, 'using_system_instruction' ) ) {
				$builder = $builder->using_system_instruction( 'Du lieferst nur einen redaktionell zu prüfenden Vorschlag. Keine Veröffentlichung, keine erfundenen Fakten.' );
			}
			if ( is_object( $builder ) && method_exists( $builder, 'using_max_tokens' ) ) {
				$builder = $builder->using_max_tokens( 300 );
			}
			$result = $builder->generate_text();
		} catch ( Throwable $error ) {
			return new WP_Error( 'ai_request_failed', __( 'Der WordPress-AI-Aufruf konnte nicht ausgeführt werden.', 'adrian-local-seo' ), array( 'status' => 502 ) );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$data = json_decode( trim( (string) $result ), true );
		if ( ! is_array( $data ) ) {
			$text = trim( preg_replace( '/^```(?:json)?|```$/mi', '', (string) $result ) );
			$data = json_decode( $text, true );
		}
		if ( ! is_array( $data ) || ! isset( $data['title'], $data['description'] ) || ! is_string( $data['title'] ) || ! is_string( $data['description'] ) || '' === trim( $data['title'] ) || '' === trim( $data['description'] ) ) {
			return new WP_Error( 'ai_invalid_response', __( 'Die KI-Antwort konnte nicht sicher als Metadaten-Vorschlag gelesen werden.', 'adrian-local-seo' ), array( 'status' => 502 ) );
		}

		return array(
			'post_id'     => $post_id,
			'title'       => $this->limit_title( sanitize_text_field( $data['title'] ) ),
			'description' => $this->limit_description( sanitize_textarea_field( $data['description'] ) ),
			'saved'       => false,
		);
	}

	/**
	 * Handle the editor's manual suggestion form.
	 */
	public function handle_ai_suggestion() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$target  = $post_id ? get_edit_post_link( $post_id, 'url' ) : admin_url( 'edit.php' );

		if ( ! current_user_can( 'manage_options' ) || ! $post_id || empty( $_POST['adrian_local_seo_ai_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['adrian_local_seo_ai_nonce'] ) ), 'adrian_local_seo_ai_' . $post_id ) ) {
			wp_safe_redirect( $target );
			exit;
		}

		$result = $this->generate_ai_suggestion( $post_id );
		if ( ! is_wp_error( $result ) ) {
			set_transient( 'adrian_local_seo_suggestion_' . get_current_user_id() . '_' . $post_id, $result, 10 * MINUTE_IN_SECONDS );
		}

		wp_safe_redirect( $target );
		exit;
	}

	/**
	 * Limit a title without breaking multibyte text.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	private function limit_title( $title ) {
		$title  = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $title ) ) );
		$limit  = 70;
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $title, 'UTF-8' ) : strlen( $title );
		if ( $length <= $limit ) {
			return $title;
		}

		$short = function_exists( 'mb_substr' ) ? mb_substr( $title, 0, $limit - 1, 'UTF-8' ) : substr( $title, 0, $limit - 1 );
		return rtrim( $short, " \t\n\r\0\x0B.,;:!?-" ) . '…';
	}

	/**
	 * Limit a description to 160 characters.
	 *
	 * @param string $description Description.
	 * @return string
	 */
	private function limit_description( $description ) {
		$description = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $description ) ) );
		$limit       = 160;
		$length      = function_exists( 'mb_strlen' ) ? mb_strlen( $description, 'UTF-8' ) : strlen( $description );
		if ( $length <= $limit ) {
			return $description;
		}

		$short      = function_exists( 'mb_substr' ) ? mb_substr( $description, 0, $limit - 2, 'UTF-8' ) : substr( $description, 0, $limit - 2 );
		$last_space = function_exists( 'mb_strrpos' ) ? mb_strrpos( $short, ' ', 0, 'UTF-8' ) : strrpos( $short, ' ' );
		if ( false !== $last_space && $last_space > (int) floor( ( $limit - 2 ) * 0.6 ) ) {
			$short = function_exists( 'mb_substr' ) ? mb_substr( $short, 0, $last_space, 'UTF-8' ) : substr( $short, 0, $last_space );
		}

		return rtrim( $short, " \t\n\r\0\x0B.,;:!?-" ) . ' …';
	}

	/**
	 * Restrict canonical overrides to this site.
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	private function is_same_site_url( $url ) {
		$host       = wp_parse_url( $url, PHP_URL_HOST );
		$home_host  = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		return $host && $home_host && strtolower( $host ) === strtolower( $home_host );
	}
}

register_activation_hook( __FILE__, array( 'Adrian_Local_SEO', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Adrian_Local_SEO', 'deactivate' ) );

Adrian_Local_SEO::instance();

/**
 * Public, read-only bridge for themes and local tooling.
 *
 * @return bool
 */
function adrian_local_seo_is_authority() {
	return Adrian_Local_SEO::instance()->is_authority();
}
