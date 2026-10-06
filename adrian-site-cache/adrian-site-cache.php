<?php
/**
 * Plugin Name: Adrian Site Cache
 * Description: Eigenständiger, sicherer Datei-Cache für eine persönliche WordPress-Website.
 * Version: 1.3.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Adrian Dylan Wulf
 * License: GPL-2.0-or-later
 * Text Domain: adrian-site-cache
 */

defined( 'ABSPATH' ) || exit;

final class Adrian_Site_Cache {
	private const VERSION      = '1.3.0';
	private const OPTION       = 'adrian_site_cache_options';
	private const VERSION_OPTION = 'adrian_site_cache_version';
	private const DROPIN_BACKUP_OPTION = 'adrian_site_cache_previous_dropin';
	private const CRON_LAST_OPTION = 'adrian_site_cache_last_cron_run';
	private const CRON_HOOK    = 'adrian_site_cache_gc';
	private const WARM_HOOK    = 'adrian_site_cache_warm';
	private const CACHE_FOLDER = 'adrian-site-cache';

	private static ?self $instance = null;
	private bool $buffer_started = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate(): void {
		$defaults = self::defaults();
		$current  = get_option( self::OPTION, [] );
		$options = wp_parse_args( is_array( $current ) ? $current : [], $defaults );
		$options['engine'] = 'native';
		if ( is_multisite() ) {
			$options['early'] = 0;
		}
		update_option( self::OPTION, $options, false );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}

		$instance = self::instance();
		$instance->ensure_native_cache_dir();
		$instance->delete_native_cache_files();
		if ( ! empty( $options['early'] ) && ! is_multisite() ) {
			$instance->sync_owned_dropin();
		} else {
			$instance->remove_owned_dropin();
		}
		update_option( self::VERSION_OPTION, self::VERSION, false );
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		wp_clear_scheduled_hook( self::WARM_HOOK );
		self::instance()->remove_owned_dropin();
	}

	private static function defaults(): array {
		return [
			'enabled'    => 1,
			'engine'     => 'native',
			'early'      => 0,
			'mode'       => 'normal',
			'ttl'        => 900,
			'max_files'  => 2000,
			'max_bytes'  => 67108864,
			'last_purge' => 0,
			'warm_after_purge' => 0,
			'last_warm' => 0,
			'last_warm_message' => '',
		];
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'maybe_upgrade' ], 20 );
		add_action( 'init', [ $this, 'maybe_schedule' ], 20 );
		add_action( 'init', [ $this, 'record_cron_run' ], 1 );
		add_action( 'template_redirect', [ $this, 'maybe_serve_or_buffer' ], 0 );
		add_action( self::CRON_HOOK, [ $this, 'garbage_collect' ] );
		add_action( self::WARM_HOOK, [ $this, 'warm_cache' ] );

		add_action( 'save_post', [ $this, 'purge_after_post_change' ], 20, 3 );
		add_action( 'deleted_post', [ $this, 'purge_after_content_change' ] );
		add_action( 'trashed_post', [ $this, 'purge_after_content_change' ] );
		add_action( 'untrashed_post', [ $this, 'purge_after_content_change' ] );
		add_action( 'created_term', [ $this, 'purge_after_content_change' ] );
		add_action( 'edited_term', [ $this, 'purge_after_content_change' ] );
		add_action( 'delete_term', [ $this, 'purge_after_content_change' ] );
		add_action( 'wp_update_nav_menu', [ $this, 'purge_after_content_change' ] );
		add_action( 'switch_theme', [ $this, 'purge_after_content_change' ] );
		add_action( 'customize_save_after', [ $this, 'purge_after_content_change' ] );
		add_action( 'updated_option', [ $this, 'purge_after_option_change' ], 20, 3 );

		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
			add_action( 'admin_init', [ $this, 'handle_admin_actions' ] );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'adrian-cache', [ $this, 'cli_command' ] );
		}
	}

	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}
	}

	public function record_cron_run(): void {
		if ( ! function_exists( 'wp_doing_cron' ) || ! wp_doing_cron() ) {
			return;
		}

		$now  = time();
		$last = (int) get_option( self::CRON_LAST_OPTION, 0 );
		if ( $last < $now - MINUTE_IN_SECONDS ) {
			update_option( self::CRON_LAST_OPTION, $now, false );
		}
	}

	private function options(): array {
		$value = get_option( self::OPTION, [] );
		return wp_parse_args( is_array( $value ) ? $value : [], self::defaults() );
	}

	private static function cache_modes(): array {
		$mb = 1024 * 1024;
		return [
			'minimal' => [
				'label'         => 'Kaum Cache',
				'description'   => 'Inhalte bleiben fast immer aktuell. Geeignet für häufige Änderungen, mit dem geringsten Cache-Effekt.',
				'advantages'    => 'Sehr frische Inhalte und geringes Risiko veralteter Seiten.',
				'disadvantages' => 'Weniger Entlastung und etwas mehr Serverarbeit.',
				'ttl'           => 60,
				'max_files'     => 500,
				'max_bytes'     => 16 * $mb,
			],
			'light' => [
				'label'         => 'Leicht',
				'description'   => 'Kurze Zwischenspeicherung für eine spürbare Entlastung ohne lange Lebensdauer.',
				'advantages'    => 'Guter Kompromiss bei gelegentlichen Änderungen.',
				'disadvantages' => 'Etwas weniger Wirkung als Normal oder Stark.',
				'ttl'           => 300,
				'max_files'     => 1000,
				'max_bytes'     => 32 * $mb,
			],
			'normal' => [
				'label'         => 'Normal (empfohlen)',
				'description'   => 'Ausgewogene Einstellung für eine persönliche Website mit Blog und Fotos.',
				'advantages'    => 'Gute Geschwindigkeit bei überschaubarem Speicherbedarf.',
				'disadvantages' => 'Änderungen werden kurz zwischengespeichert; nach Inhaltsänderungen leert das Plugin den Cache.',
				'ttl'           => 900,
				'max_files'     => 2000,
				'max_bytes'     => 64 * $mb,
			],
			'strong' => [
				'label'         => 'Stark',
				'description'   => 'Längere Zwischenspeicherung für maximale Entlastung bei überwiegend statischen Inhalten.',
				'advantages'    => 'Beste Wirkung bei wiederholten öffentlichen Seitenaufrufen.',
				'disadvantages' => 'Ohne Inhaltsänderung oder manuelles Leeren können Änderungen länger auf sich warten lassen.',
				'ttl'           => 3600,
				'max_files'     => 5000,
				'max_bytes'     => 128 * $mb,
			],
		];
	}

	public function maybe_upgrade(): void {
		$stored_version = (string) get_option( self::VERSION_OPTION, '' );
		if ( self::VERSION === $stored_version ) {
			return;
		}

		$options = $this->options();
		$modes   = self::cache_modes();
		if ( ! isset( $modes[ $options['mode'] ] ) ) {
			$options['mode'] = 'normal';
		}

		$options['engine'] = 'native';
		if ( is_multisite() ) {
			$options['early'] = 0;
		}

		// Do not let a new drop-in serve files created by an older implementation.
		$this->remove_owned_dropin();
		$this->delete_native_cache_files();
		update_option( self::OPTION, $options, false );
		$this->ensure_native_cache_dir();
		update_option( self::VERSION_OPTION, self::VERSION, false );
	}

	private function native_mode(): bool {
		$options = $this->options();
		return ! is_multisite() && 'native' === $options['engine'];
	}

	public function maybe_serve_or_buffer(): void {
		$options = $this->options();
		if ( empty( $options['enabled'] ) || ! $this->native_mode() || ! $this->request_is_cacheable() ) {
			return;
		}

		$key  = $this->cache_key();
		$file = $this->cache_file( $key );
		$ttl  = max( 60, (int) $options['ttl'] );

		if ( is_readable( $file ) && ( time() - (int) filemtime( $file ) ) <= $ttl ) {
			$this->serve_cached_file( $file );
		}

		if ( ! headers_sent() ) {
			header( 'X-Adrian-Site-Cache: MISS' );
		}

		ob_start( [ $this, 'capture_page' ] );
		$this->buffer_started = true;
	}

	public function capture_page( string $html ): string {
		if ( ! $this->buffer_started || ! $this->request_is_cacheable() || strlen( $html ) < 200 || strlen( $html ) > 5 * MB_IN_BYTES ) {
			return $html;
		}

		$status = http_response_code();
		if ( false !== $status && ( $status < 200 || $status >= 300 ) ) {
			return $html;
		}

		foreach ( headers_list() as $header ) {
			if ( preg_match( '/^(set-cookie|location|content-disposition):/i', $header ) || preg_match( '/cache-control:.*(private|no-cache|no-store)/i', $header ) || preg_match( '/^pragma:\s*no-cache/i', $header ) || preg_match( '/^expires:\s*Thu, 01 Jan 1970/i', $header ) ) {
				return $html;
			}

			if ( preg_match( '/^vary:\\s*(.*)$/i', $header, $matches ) ) {
				$vary_values = array_filter( array_map( 'trim', explode( ',', strtolower( $matches[1] ) ) ) );
				foreach ( $vary_values as $vary_value ) {
					if ( 'accept-encoding' !== $vary_value ) {
						return $html;
					}
				}
			}
		}

		if ( false === stripos( $html, '<html' ) && false === stripos( $html, '<!doctype' ) ) {
			return $html;
		}

		$this->write_cache_file( $this->cache_key(), $html );
		return $html;
	}

	private function request_is_cacheable(): bool {
		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			return false;
		}

		if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) && 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			return false;
		}

		if ( is_admin() || is_user_logged_in() || is_preview() || is_search() || is_feed() || is_404() || ! empty( $_GET ) || ! empty( $_SERVER['QUERY_STRING'] ) || ! empty( $_SERVER['HTTP_COOKIE'] ) || ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			return false;
		}

		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST || defined( 'DOING_CRON' ) && DOING_CRON || defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return false;
		}

		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
		foreach ( [ '/wp-admin', '/wp-login.php', '/wp-json', '/xmlrpc.php', '/wp-cron.php', '/feed', '/comments/feed', '/sitemap' ] as $excluded ) {
			if ( 0 === strpos( $path, $excluded ) ) {
				return false;
			}
		}

		return true;
	}

	private function cache_key(): string {
		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
		return hash( 'sha256', home_url( '/' ) . '|' . $path );
	}

	private function native_cache_dir(): string {
		return trailingslashit( WP_CONTENT_DIR ) . 'cache/' . self::CACHE_FOLDER;
	}

	private function cache_file( string $key ): string {
		return trailingslashit( $this->native_cache_dir() ) . $key . '.html';
	}

	private function ensure_native_cache_dir(): bool {
		$dir = $this->native_cache_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$index = trailingslashit( $dir ) . 'index.html';
		if ( ! file_exists( $index ) ) {
			$this->atomic_write_file( $index, '' );
		}

		$htaccess    = trailingslashit( $dir ) . '.htaccess';
		$deny_rules  = "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n";
		if ( ! file_exists( $htaccess ) || (string) file_get_contents( $htaccess ) !== $deny_rules ) {
			$this->atomic_write_file( $htaccess, $deny_rules );
		}

		$this->write_dropin_config();

		return is_dir( $dir ) && is_writable( $dir );
	}

	private function write_dropin_config(): void {
		$options = $this->options();
		$config  = trailingslashit( $this->native_cache_dir() ) . 'config.php';
		$contents = "<?php\nreturn " . var_export( [
			'version'    => self::VERSION,
			'ttl'        => max( 60, (int) $options['ttl'] ),
			'enabled'    => ! empty( $options['enabled'] ),
			'early'      => ! empty( $options['early'] ),
			'engine'     => (string) $options['engine'],
			'multisite'  => is_multisite(),
			'key_prefix' => home_url( '/' ),
		], true ) . ";\n";
		if ( ! file_exists( $config ) || (string) file_get_contents( $config ) !== $contents ) {
			$this->atomic_write_file( $config, $contents );
		}
	}

	private function atomic_write_file( string $path, string $contents ): bool {
		$directory = dirname( $path );
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			return false;
		}

		$tmp     = $path . '.' . wp_generate_uuid4() . '.tmp';
		$written = file_put_contents( $tmp, $contents, LOCK_EX );
		if ( false === $written || ! @rename( $tmp, $path ) ) {
			if ( file_exists( $tmp ) ) {
				@unlink( $tmp );
			}
			return false;
		}

		return true;
	}

	private function sync_owned_dropin(): bool {
		if ( ! defined( 'WP_CONTENT_DIR' ) || ! $this->native_mode() || empty( $this->options()['enabled'] ) || empty( $this->options()['early'] ) ) {
			return false;
		}

		$dropin = trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php';
		$current = file_exists( $dropin ) ? (string) file_get_contents( $dropin ) : '';
		if ( '' !== $current && false === strpos( $current, 'ADRIAN_SITE_CACHE_DROPIN' ) ) {
			if ( '' === (string) get_option( self::DROPIN_BACKUP_OPTION, '' ) ) {
				update_option( self::DROPIN_BACKUP_OPTION, $current, false );
			}
		}

		$plugin_file = WP_CONTENT_DIR . '/plugins/adrian-site-cache/includes/drop-in.php';
		$contents    = <<<'PHP'
<?php
/* ADRIAN_SITE_CACHE_DROPIN */
if ( defined( 'WP_CONTENT_DIR' ) ) {
    $adrian_site_cache_dropin = WP_CONTENT_DIR . '/plugins/adrian-site-cache/includes/drop-in.php';
    if ( is_readable( $adrian_site_cache_dropin ) ) {
        require $adrian_site_cache_dropin;
    }
}
PHP;
		if ( ! is_readable( $plugin_file ) || ! $this->atomic_write_file( $dropin, $contents ) ) {
			return false;
		}

		return true;
	}

	private function remove_owned_dropin(): void {
		$dropin = trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php';
		if ( ! file_exists( $dropin ) || false === strpos( (string) file_get_contents( $dropin ), 'ADRIAN_SITE_CACHE_DROPIN' ) ) {
			return;
		}

		$backup = (string) get_option( self::DROPIN_BACKUP_OPTION, '' );
		if ( '' !== $backup ) {
			$this->atomic_write_file( $dropin, $backup );
			delete_option( self::DROPIN_BACKUP_OPTION );
			return;
		}

		@unlink( $dropin );
	}

	private function write_cache_file( string $key, string $html ): void {
		if ( ! $this->ensure_native_cache_dir() ) {
			return;
		}

		$lock_path = trailingslashit( $this->native_cache_dir() ) . '.write.lock';
		$lock      = @fopen( $lock_path, 'c' );
		if ( false === $lock || ! @flock( $lock, LOCK_EX ) ) {
			if ( is_resource( $lock ) ) {
				@fclose( $lock );
			}
			return;
		}

		$gzip = function_exists( 'gzencode' ) ? gzencode( $html, 6 ) : '';
		if ( $this->has_cache_capacity( strlen( $html ), is_string( $gzip ) ? strlen( $gzip ) : 0, $key ) ) {
			$file = $this->cache_file( $key );
			if ( $this->atomic_write_file( $file, $html ) ) {
				if ( '' !== $gzip ) {
					$this->atomic_write_file( $file . '.gz', $gzip );
				} elseif ( file_exists( $file . '.gz' ) ) {
					@unlink( $file . '.gz' );
				}
			}
		}

		@flock( $lock, LOCK_UN );
		@fclose( $lock );
	}

	private function native_files(): array {
		$files = [];
		$dir   = $this->native_cache_dir();
		if ( ! is_dir( $dir ) ) {
			return $files;
		}

		foreach ( new DirectoryIterator( $dir ) as $item ) {
			if ( $item->isDot() || ! $item->isFile() || 'html' !== $item->getExtension() || 'index.html' === $item->getFilename() ) {
				continue;
			}
			$gzip = $item->getPathname() . '.gz';
			$files[] = [
				'path'  => $item->getPathname(),
				'mtime' => $item->getMTime(),
				'bytes' => $item->getSize() + ( is_file( $gzip ) ? (int) filesize( $gzip ) : 0 ),
			];
		}

		return $files;
	}

	private function has_cache_capacity( int $html_bytes, int $gzip_bytes, string $key ): bool {
		$options      = $this->options();
		$max_files    = max( 100, (int) $options['max_files'] );
		$max_bytes    = max( 16 * MB_IN_BYTES, (int) $options['max_bytes'] );
		$target_file  = $this->cache_file( $key );
		$target_exists = is_file( $target_file );
		$target_bytes = $target_exists ? (int) filesize( $target_file ) + ( is_file( $target_file . '.gz' ) ? (int) filesize( $target_file . '.gz' ) : 0 ) : 0;

		$usage = $this->native_files();
		$count = count( $usage ) + ( $target_exists ? 0 : 1 );
		$bytes = array_sum( array_column( $usage, 'bytes' ) ) - $target_bytes + $html_bytes + $gzip_bytes;
		if ( $count <= $max_files && $bytes <= $max_bytes ) {
			return true;
		}

		$this->garbage_collect_unlocked();
		$usage = $this->native_files();
		$count = count( $usage ) + ( $target_exists ? 0 : 1 );
		$target_bytes = $target_exists && is_file( $target_file ) ? (int) filesize( $target_file ) + ( is_file( $target_file . '.gz' ) ? (int) filesize( $target_file . '.gz' ) : 0 ) : 0;
		$bytes = array_sum( array_column( $usage, 'bytes' ) ) - $target_bytes + $html_bytes + $gzip_bytes;
		return $count <= $max_files && $bytes <= $max_bytes;
	}

	private function serve_cached_file( string $file ): void {
		$gzip_file = $file . '.gz';
		$use_gzip  = is_readable( $gzip_file ) && false !== stripos( (string) ( $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '' ), 'gzip' );
		$body      = $use_gzip ? $gzip_file : $file;
		$not_modified = $this->send_cached_file_headers( $body, $use_gzip );
		if ( $not_modified ) {
			exit;
		}

		if ( ! headers_sent() ) {
			header( 'X-Adrian-Site-Cache: HIT' );
			header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
			header( 'Content-Length: ' . (string) filesize( $body ) );
			header( 'Cache-Control: public, max-age=60, stale-while-revalidate=30' );
			if ( $use_gzip ) {
				header( 'Content-Encoding: gzip' );
				header( 'Vary: Accept-Encoding' );
			}
		}

		if ( 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			readfile( $body );
		}
		exit;
	}

	/**
	 * Send validators for a cached representation and handle conditional GETs.
	 *
	 * @param string $body Cached representation on disk.
	 * @param bool   $gzip Whether the representation is compressed.
	 * @return bool Whether the request was answered with 304.
	 */
	private function send_cached_file_headers( string $body, bool $gzip ): bool {
		$mtime = is_file( $body ) ? (int) filemtime( $body ) : 0;
		$size  = is_file( $body ) ? (int) filesize( $body ) : 0;
		if ( $mtime < 1 || $size < 0 || headers_sent() ) {
			return false;
		}

		$etag = 'W/"' . $mtime . '-' . $size . ( $gzip ? '-gzip' : '' ) . '"';
		header( 'X-Adrian-Site-Cache: HIT' );
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		header( 'Cache-Control: public, max-age=60, stale-while-revalidate=30' );
		if ( $gzip ) {
			header( 'Content-Encoding: gzip' );
			header( 'Vary: Accept-Encoding' );
		}
		header( 'ETag: ' . $etag );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \\G\\M\\T', $mtime ) );

		$if_none_match = trim( (string) ( $_SERVER['HTTP_IF_NONE_MATCH'] ?? '' ) );
		$etag_matches  = false;
		if ( '' !== $if_none_match ) {
			$normalized_etag = preg_replace( '/^W\\//', '', $etag );
			foreach ( explode( ',', $if_none_match ) as $candidate ) {
				$candidate = trim( $candidate );
				$candidate = preg_replace( '/^W\\//', '', $candidate );
				if ( '*' === $candidate || ( is_string( $normalized_etag ) && $candidate === $normalized_etag ) ) {
					$etag_matches = true;
					break;
				}
			}
		} elseif ( ! empty( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			$since = strtotime( (string) $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
			$etag_matches = false !== $since && $since >= $mtime;
		}

		if ( $etag_matches ) {
			status_header( 304 );
			header( 'Content-Length: 0' );
			return true;
		}

		return false;
	}

	public function purge_after_post_change( int $post_id, WP_Post $post, bool $update ): void {
		if ( wp_is_post_revision( $post_id ) || ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) ) {
			return;
		}
		$this->purge( 'content' );
	}

	public function purge_after_content_change(): void {
		$this->purge( 'content' );
	}

	public function purge_after_option_change( string $option ): void {
		if ( self::OPTION === $option ) {
			return;
		}

		$important = [ 'permalink_structure', 'show_on_front', 'page_on_front', 'page_for_posts', 'stylesheet', 'template', 'blogname', 'blogdescription' ];
		if ( in_array( $option, $important, true ) ) {
			$this->purge( 'site-setting' );
		}
	}

	public function purge( string $reason = 'manual' ): void {
		$this->delete_native_cache_files();
		$options             = $this->options();
		$options['last_purge'] = time();
		update_option( self::OPTION, $options, false );
		if ( $this->native_mode() ) {
			$this->write_dropin_config();
			if ( ! empty( $options['early'] ) ) {
				$this->sync_owned_dropin();
			} else {
				$this->remove_owned_dropin();
			}
		}
		if ( ! empty( $options['warm_after_purge'] ) && ! wp_next_scheduled( self::WARM_HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::WARM_HOOK );
		}
	}

	/**
	 * Return a small, fixed set of same-site URLs that are safe to warm.
	 * Filters may add URLs, but the host and query-string checks below remain mandatory.
	 *
	 * @return array<int, string>
	 */
	private function warm_urls(): array {
		$urls = [ home_url( '/' ) ];
		$front_id = (int) get_option( 'page_on_front', 0 );
		$posts_id = (int) get_option( 'page_for_posts', 0 );
		foreach ( [ $front_id, $posts_id ] as $page_id ) {
			if ( $page_id > 0 ) {
				$permalink = get_permalink( $page_id );
				if ( is_string( $permalink ) && '' !== $permalink ) {
					$urls[] = $permalink;
				}
			}
		}

		$urls = apply_filters( 'adrian_site_cache_warm_urls', $urls );
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$safe_urls = [];
		foreach ( is_array( $urls ) ? $urls : [] as $url ) {
			$url = is_string( $url ) ? trim( $url ) : '';
			$parts = wp_parse_url( $url );
			$host = strtolower( (string) ( $parts['host'] ?? '' ) );
			$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
			if ( '' === $url || $host !== $home_host || ! in_array( $scheme, [ 'http', 'https' ], true ) || ! empty( $parts['query'] ) || ! empty( $parts['fragment'] ) ) {
				continue;
			}
			$safe_urls[] = esc_url_raw( $url );
		}

		return array_values( array_unique( array_filter( $safe_urls ) ) );
	}

	/**
	 * Warm a few public pages after a purge. No response body is retained.
	 *
	 * @return array{success:int,total:int,message:string}
	 */
	public function warm_cache(): array {
		$options = $this->options();
		if ( empty( $options['enabled'] ) || ! $this->native_mode() ) {
			return [ 'success' => 0, 'total' => 0, 'message' => 'Cache-Aufwärmen ist deaktiviert.' ];
		}

		$success = 0;
		$total   = 0;
		foreach ( $this->warm_urls() as $url ) {
			++$total;
			$response = wp_safe_remote_get(
				$url,
				[
					'timeout'             => 5,
					'redirection'         => 0,
					'blocking'            => true,
					'limit_response_size' => 5 * MB_IN_BYTES,
					'reject_unsafe_urls'  => true,
					'headers'             => [
						'Accept'          => 'text/html',
						'Accept-Encoding' => 'identity',
						'Cache-Control'   => 'no-cache',
					],
				]
			);
			if ( is_wp_error( $response ) ) {
				continue;
			}
			$status      = (int) wp_remote_retrieve_response_code( $response );
			$content_type = strtolower( (string) wp_remote_retrieve_header( $response, 'content-type' ) );
			if ( $status >= 200 && $status < 300 && false !== strpos( $content_type, 'text/html' ) ) {
				++$success;
			}
		}

		$options['last_warm']         = time();
		$options['last_warm_message'] = sprintf( '%d von %d öffentlichen Seiten geprüft.', $success, $total );
		update_option( self::OPTION, $options, false );
		return [ 'success' => $success, 'total' => $total, 'message' => $options['last_warm_message'] ];
	}

	public function garbage_collect(): void {
		if ( ! $this->native_mode() ) {
			return;
		}

		$lock_path = trailingslashit( $this->native_cache_dir() ) . '.write.lock';
		$lock      = @fopen( $lock_path, 'c' );
		if ( false === $lock || ! @flock( $lock, LOCK_EX ) ) {
			if ( is_resource( $lock ) ) {
				@fclose( $lock );
			}
			return;
		}
		$this->garbage_collect_unlocked();
		@flock( $lock, LOCK_UN );
		@fclose( $lock );
	}

	private function garbage_collect_unlocked(): void {
		if ( ! $this->native_mode() ) {
			return;
		}

		$this->remove_stale_cache_artifacts();
		$options = $this->options();
		$files   = $this->native_files();
		if ( empty( $files ) ) {
			return;
		}

		$now      = time();
		$ttl      = max( 60, (int) $options['ttl'] );
		foreach ( $files as $file ) {
			if ( $now - $file['mtime'] > $ttl ) {
				@unlink( $file['path'] );
				@unlink( $file['path'] . '.gz' );
			}
		}

		$files = $this->native_files();
		usort( $files, static fn( array $a, array $b ): int => $b['mtime'] <=> $a['mtime'] );
		$max_files = max( 100, (int) $options['max_files'] );
		$max_bytes = max( 16 * MB_IN_BYTES, (int) $options['max_bytes'] );
		$total_bytes = array_sum( array_column( $files, 'bytes' ) );
		foreach ( $files as $index => $file ) {
			if ( $index < $max_files && $total_bytes <= $max_bytes ) {
				continue;
			}
			@unlink( $file['path'] );
			@unlink( $file['path'] . '.gz' );
			$total_bytes -= $file['bytes'];
		}
	}

	/**
	 * Remove only stale temporary files and orphaned gzip sidecars created by
	 * this plugin. A failed request or interrupted deploy must not leave
	 * unbounded debris in the cache directory, but active files are left alone.
	 *
	 * @return void
	 */
	private function remove_stale_cache_artifacts(): void {
		$dir = $this->native_cache_dir();
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$now = time();
		foreach ( new DirectoryIterator( $dir ) as $item ) {
			if ( $item->isDot() || ! $item->isFile() ) {
				continue;
			}

			$filename = $item->getFilename();
			$path     = $item->getPathname();
			$age      = $now - $item->getMTime();

			// Atomic writes use UUID-suffixed .tmp files. Only remove old
			// leftovers, never a file that could belong to an active request.
			if ( $age > HOUR_IN_SECONDS && str_ends_with( $filename, '.tmp' ) ) {
				@unlink( $path );
				continue;
			}

			if ( ! preg_match( '/^[a-f0-9]{64}\.html\.gz$/i', $filename ) ) {
				continue;
			}

			$html_file = substr( $path, 0, -3 );
			if ( ! is_file( $html_file ) ) {
				@unlink( $path );
			}
		}
	}

	private function delete_native_cache_files(): void {
		$dir = $this->native_cache_dir();
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$iterator = new DirectoryIterator( $dir );
		foreach ( $iterator as $item ) {
			if ( ! $item->isDot() && $item->isFile() && 'html' === $item->getExtension() && 'index.html' !== $item->getFilename() ) {
				@unlink( $item->getPathname() );
				@unlink( $item->getPathname() . '.gz' );
			}
		}

		// Manual purges should remove the same stale sidecars as the scheduled GC.
		$this->remove_stale_cache_artifacts();
	}

	private function cache_stats(): array {
		$dir   = $this->native_cache_dir();
		$count = 0;
		$bytes = 0;
		if ( $this->native_mode() && is_dir( $dir ) ) {
			foreach ( new DirectoryIterator( $dir ) as $file ) {
				if ( $file->isDot() || ! $file->isFile() || 'html' !== $file->getExtension() || 'index.html' === $file->getFilename() ) {
					continue;
				}
				$count++;
				$bytes += $file->getSize();
				$gzip = $file->getPathname() . '.gz';
				if ( is_file( $gzip ) ) {
					$bytes += (int) filesize( $gzip );
				}
			}
		}

		return [ 'count' => $count, 'bytes' => $bytes, 'dir' => $dir ];
	}

	public function register_admin_page(): void {
		add_options_page( 'Adrian Site Cache', 'Site Cache', 'manage_options', 'adrian-site-cache', [ $this, 'render_admin_page' ] );
	}

	public function handle_admin_actions(): void {
		if ( ! is_admin() || ! isset( $_POST['adrian_site_cache_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'adrian_site_cache_settings' );
		$action = sanitize_key( wp_unslash( $_POST['adrian_site_cache_action'] ) );
		if ( 'purge' === $action ) {
			$this->purge( 'manual' );
			$this->redirect_admin( 'purged' );
		}
		if ( 'gc' === $action ) {
			$this->garbage_collect();
			$this->redirect_admin( 'collected' );
		}
		if ( 'warm' === $action ) {
			$this->warm_cache();
			$this->redirect_admin( 'warmed' );
		}
		if ( 'save' !== $action ) {
			return;
		}

		$options = $this->options();
		$modes = self::cache_modes();
		$mode  = sanitize_key( wp_unslash( $_POST['mode'] ?? 'normal' ) );
		if ( ! isset( $modes[ $mode ] ) ) {
			$mode = 'normal';
		}
		$options['enabled']   = empty( $_POST['enabled'] ) ? 0 : 1;
		$options['engine']    = 'native';
		$options['early']     = empty( $_POST['early'] ) ? 0 : 1;
		if ( is_multisite() ) {
			$options['early'] = 0;
		}
		$options['mode']      = $mode;
		$options['ttl']       = $modes[ $mode ]['ttl'];
		$options['max_files'] = $modes[ $mode ]['max_files'];
		$options['max_bytes'] = $modes[ $mode ]['max_bytes'];
		$options['warm_after_purge'] = empty( $_POST['warm_after_purge'] ) ? 0 : 1;
		update_option( self::OPTION, $options, false );
		$this->ensure_native_cache_dir();
		if ( ! empty( $options['early'] ) ) {
			$this->sync_owned_dropin();
		} else {
			$this->remove_owned_dropin();
		}
		$this->purge( 'settings' );
		$this->redirect_admin( 'saved' );
	}

	private function redirect_admin( string $message ): void {
		$url = add_query_arg( [ 'page' => 'adrian-site-cache', 'message' => $message ], admin_url( 'options-general.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = $this->options();
		$stats   = $this->cache_stats();
		$message = sanitize_key( $_GET['message'] ?? '' );
		$cron    = wp_next_scheduled( self::CRON_HOOK );
		$last_cron = (int) get_option( self::CRON_LAST_OPTION, 0 );
		$modes   = self::cache_modes();
		$mode    = isset( $modes[ $options['mode'] ] ) ? $options['mode'] : 'normal';
		$messages = [
			'saved'     => 'Cache-Einstellungen gespeichert.',
			'purged'    => 'Alle Cache-Dateien wurden geleert.',
			'collected' => 'Abgelaufene Cache-Dateien wurden bereinigt.',
			'warmed'    => 'Öffentliche Seiten wurden geprüft und – soweit möglich – vorgewärmt.',
		];
		?>
		<div class="wrap adrian-site-cache-admin">
			<style>
				.adrian-site-cache-admin{max-width:1080px;margin-right:20px;color:#1d2327}.adrian-site-cache-admin .asc-header{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;background:#fff;border:1px solid #d9e2ec;border-top:4px solid #2271b1;border-radius:12px;padding:24px 28px;margin:18px 0 16px}.adrian-site-cache-admin .asc-kicker{margin:0 0 8px;color:#2271b1;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.adrian-site-cache-admin .asc-header h1{margin:0;color:#172b3a;font-size:29px;line-height:1.2}.adrian-site-cache-admin .asc-lead{max-width:680px;margin:10px 0 0;color:#536579;font-size:14px;line-height:1.55}.adrian-site-cache-admin .asc-status{display:inline-flex;align-items:center;gap:8px;flex:0 0 auto;padding:8px 12px;border:1px solid #bbd7c6;border-radius:999px;background:#f1faf4;color:#17683b;font-size:13px;font-weight:600;white-space:nowrap}.adrian-site-cache-admin .asc-status.is-paused{border-color:#e6c98c;background:#fff8e7;color:#7a4f00}.adrian-site-cache-admin .asc-status-dot{width:8px;height:8px;border-radius:50%;background:#2da05a}.adrian-site-cache-admin .asc-status.is-paused .asc-status-dot{background:#c58a00}.adrian-site-cache-admin .asc-card{background:#fff;border:1px solid #d9e2ec;border-radius:12px;padding:22px 24px;margin:16px 0;box-shadow:0 1px 2px rgba(23,43,58,.04)}.adrian-site-cache-admin .asc-overview{padding:12px}.adrian-site-cache-admin .asc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.adrian-site-cache-admin .asc-stat{min-height:78px;padding:16px;background:#f6f9fc;border:1px solid #e1eaf2;border-radius:9px}.adrian-site-cache-admin .asc-stat-label{display:block;color:#536579;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase}.adrian-site-cache-admin .asc-stat strong{display:block;margin-top:6px;color:#173a5a;font-size:20px;line-height:1.25}.adrian-site-cache-admin .asc-section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.adrian-site-cache-admin .asc-section-label{margin:0 0 5px;color:#2271b1;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.adrian-site-cache-admin .asc-section-head h2{margin:0;color:#172b3a;font-size:20px}.adrian-site-cache-admin .asc-settings-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:24px;align-items:start}.adrian-site-cache-admin .asc-toggle{display:flex;gap:10px;margin:0 0 12px;padding:12px;background:#f8fafc;border:1px solid #e4ebf2;border-radius:8px}.adrian-site-cache-admin .asc-toggle input{flex:0 0 auto;margin-top:3px}.adrian-site-cache-admin .asc-toggle strong{display:block;color:#243b50}.adrian-site-cache-admin .asc-toggle small{display:block;margin-top:3px;color:#536579;font-size:12px;line-height:1.45}.adrian-site-cache-admin .asc-note{margin:16px 0 0;padding:12px 14px;border-left:3px solid #2271b1;background:#f0f6fc;color:#46596b;font-size:13px;line-height:1.55}.adrian-site-cache-admin label{display:block;margin:14px 0 6px;font-weight:600}.adrian-site-cache-admin select{box-sizing:border-box;width:100%;min-width:0;max-width:100%}.adrian-site-cache-admin .description{color:#536579;line-height:1.55}.adrian-site-cache-admin .notice-inline,.adrian-site-cache-admin .notice-info{padding:10px 12px;border-left:4px solid #dba617;background:#fff8e5}.adrian-site-cache-admin .notice-info{border-left-color:#2271b1;background:#f0f6fc}.adrian-site-cache-admin .asc-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:20px}.adrian-site-cache-admin .asc-actions form{margin:0}.adrian-site-cache-admin .asc-actions .button{min-height:38px;padding:4px 14px}.adrian-site-cache-admin .asc-mode-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:20px}.adrian-site-cache-admin .asc-mode{padding:14px;background:#fbfcfd;border:1px solid #dfe7ef;border-radius:8px;color:#46596b;line-height:1.5}.adrian-site-cache-admin .asc-mode.is-current{border-color:#2271b1;background:#f5f9fd;box-shadow:inset 3px 0 0 #2271b1}.adrian-site-cache-admin .asc-mode strong{display:block;margin-bottom:4px;color:#243b50}.adrian-site-cache-admin .asc-mode small{display:block;margin-top:7px;color:#536579;font-size:12px;line-height:1.5}.adrian-site-cache-admin .asc-mode small strong{display:inline;margin:0;color:#243b50}.adrian-site-cache-admin .asc-facts{margin:18px 0 0;padding-top:14px;border-top:1px solid #e1eaf2;color:#536579;font-size:13px}.adrian-site-cache-admin .asc-submit-row{margin:20px 0 0}.adrian-site-cache-admin .asc-maintenance-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0 0 18px}.adrian-site-cache-admin .asc-maintenance-fact{padding:13px 14px;background:#f8fafc;border:1px solid #e4ebf2;border-radius:8px}.adrian-site-cache-admin .asc-maintenance-fact span{display:block;color:#536579;font-size:12px}.adrian-site-cache-admin .asc-maintenance-fact strong{display:block;margin-top:4px;color:#243b50;font-size:15px}.adrian-site-cache-admin .asc-help{margin:0;color:#536579;font-size:13px;line-height:1.55}@media(max-width:800px){.adrian-site-cache-admin .asc-settings-grid{grid-template-columns:1fr}}@media(max-width:600px){.adrian-site-cache-admin{margin-right:10px}.adrian-site-cache-admin .asc-header{display:block;padding:20px}.adrian-site-cache-admin .asc-status{margin-top:16px}.adrian-site-cache-admin .asc-card{padding:18px}.adrian-site-cache-admin .asc-grid,.adrian-site-cache-admin .asc-mode-list,.adrian-site-cache-admin .asc-maintenance-grid{grid-template-columns:1fr}.adrian-site-cache-admin .asc-actions form,.adrian-site-cache-admin .asc-actions .button{width:100%}}
			</style>
			<style>
				/* 1.1.2: quieter admin presentation with less dashboard chrome. */
				.adrian-site-cache-admin .asc-header{align-items:center;border:0;border-left:4px solid #2271b1;border-radius:5px;padding:20px 22px;margin:18px 0 14px;box-shadow:none}
				.adrian-site-cache-admin .asc-header h1{font-size:26px;font-weight:600}
				.adrian-site-cache-admin .asc-kicker,.adrian-site-cache-admin .asc-section-label{letter-spacing:.04em;font-size:11px}
				.adrian-site-cache-admin .asc-lead{margin-top:6px}
				.adrian-site-cache-admin .asc-status{padding:0;border:0;border-radius:0;background:transparent;color:#17683b;font-size:13px;font-weight:600}
				.adrian-site-cache-admin .asc-status.is-paused{background:transparent;color:#7a4f00}
				.adrian-site-cache-admin .asc-status-dot{width:7px;height:7px}
				.adrian-site-cache-admin .asc-card{border-radius:5px;padding:20px 22px;margin:14px 0;box-shadow:none}
				.adrian-site-cache-admin .asc-overview{padding:0;background:transparent;border:0}
				.adrian-site-cache-admin .asc-grid{gap:0;border-top:1px solid #d9e2ec;border-bottom:1px solid #d9e2ec}
				.adrian-site-cache-admin .asc-stat{min-height:0;padding:14px 18px;background:#fff;border:0;border-right:1px solid #e1eaf2;border-radius:0}
				.adrian-site-cache-admin .asc-stat:last-child{border-right:0}
				.adrian-site-cache-admin .asc-stat strong{font-size:17px}
				.adrian-site-cache-admin .asc-section-head{margin-bottom:14px}
				.adrian-site-cache-admin .asc-section-head h2{font-size:19px;font-weight:600}
				.adrian-site-cache-admin .asc-settings-grid{gap:30px}
				.adrian-site-cache-admin .asc-toggle{padding:10px 12px;border-radius:5px;background:#fff}
				.adrian-site-cache-admin .asc-note{margin-top:14px;padding:10px 12px}
				.adrian-site-cache-admin .asc-mode-list{display:block;margin-top:18px;border-top:1px solid #dfe7ef}
				.adrian-site-cache-admin .asc-mode{display:block;padding:0;border:0;border-bottom:1px solid #dfe7ef;border-radius:0;background:#fff;color:#46596b}
				.adrian-site-cache-admin .asc-mode.is-current{border-color:#dfe7ef;background:#f7fafc;box-shadow:none}
				.adrian-site-cache-admin .asc-mode summary{display:flex;align-items:center;gap:12px;padding:12px 10px;cursor:pointer;list-style:none}
				.adrian-site-cache-admin .asc-mode summary::-webkit-details-marker{display:none}
				.adrian-site-cache-admin .asc-mode summary:before{content:'›';display:inline-block;width:12px;color:#2271b1;font-size:20px;line-height:12px;transition:transform .15s ease}
				.adrian-site-cache-admin .asc-mode[open] summary:before{transform:rotate(90deg)}
				.adrian-site-cache-admin .asc-mode-title{flex:0 0 170px;color:#243b50;font-weight:600}
				.adrian-site-cache-admin .asc-mode-summary{flex:1;color:#536579;font-size:13px}
				.adrian-site-cache-admin .asc-mode-current{flex:0 0 auto;color:#2271b1;font-size:11px;font-weight:700;letter-spacing:.03em;text-transform:uppercase}
				.adrian-site-cache-admin .asc-mode-body{padding:0 36px 14px;color:#536579;font-size:12px;line-height:1.5}
				.adrian-site-cache-admin .asc-mode-body strong{display:inline;margin:0;color:#243b50}
				.adrian-site-cache-admin .asc-facts{margin-top:14px;padding-top:12px}
				.adrian-site-cache-admin .asc-maintenance-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:0;border-top:1px solid #d9e2ec;border-bottom:1px solid #d9e2ec}
				.adrian-site-cache-admin .asc-maintenance-fact{padding:12px 16px;background:#fff;border:0;border-right:1px solid #e1eaf2;border-radius:0}
				.adrian-site-cache-admin .asc-maintenance-fact:last-child{border-right:0}
				.adrian-site-cache-admin .asc-actions{margin-top:16px}
				.adrian-site-cache-admin .asc-actions .button{border-radius:4px}
				@media(max-width:600px){.adrian-site-cache-admin .asc-header{padding:18px}.adrian-site-cache-admin .asc-grid,.adrian-site-cache-admin .asc-maintenance-grid{display:grid}.adrian-site-cache-admin .asc-stat,.adrian-site-cache-admin .asc-maintenance-fact{border-right:0;border-bottom:1px solid #e1eaf2}.adrian-site-cache-admin .asc-stat:last-child,.adrian-site-cache-admin .asc-maintenance-fact:last-child{border-bottom:0}.adrian-site-cache-admin .asc-mode summary{align-items:flex-start}.adrian-site-cache-admin .asc-mode-title{flex-basis:auto}.adrian-site-cache-admin .asc-mode-summary{display:none}.adrian-site-cache-admin .asc-mode-body{padding-left:34px}}
			</style>
			<div class="asc-header">
				<div>
					<p class="asc-kicker">Website-Wartung</p>
					<h1>Adrian Site Cache</h1>
					<p class="asc-lead">Schlanke Cache-Steuerung für diese Website. Der Cache wird nur für öffentliche GET-Seiten verwendet.</p>
				</div>
				<span class="asc-status <?php echo empty( $options['enabled'] ) || is_multisite() ? 'is-paused' : ''; ?>"><span class="asc-status-dot" aria-hidden="true"></span><?php echo empty( $options['enabled'] ) || is_multisite() ? 'Cache pausiert' : 'Cache aktiv'; ?></span>
			</div>
			<?php if ( is_multisite() ) : ?><div class="notice-inline"><p>Der native Datei-Cache ist auf Multisite deaktiviert, damit gemeinsame Cache- und Drop-in-Dateien keine Inhalte zwischen Websites vermischen können.</p></div><?php endif; ?>
			<?php if ( isset( $messages[ $message ] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $messages[ $message ] ); ?></p></div><?php endif; ?>
			<div class="asc-card asc-overview">
				<div class="asc-grid">
					<div class="asc-stat"><span class="asc-stat-label">Aktiver Weg</span><strong><?php echo esc_html( is_multisite() ? 'Deaktiviert (Multisite)' : 'Eigener Datei-Cache' ); ?></strong></div>
					<div class="asc-stat"><span class="asc-stat-label">Cache-Dateien</span><strong><?php echo esc_html( number_format_i18n( $stats['count'] ) ); ?></strong></div>
					<div class="asc-stat"><span class="asc-stat-label">Cache-Größe</span><strong><?php echo esc_html( size_format( $stats['bytes'] ) ); ?></strong></div>
				</div>
			</div>
			<form method="post" class="asc-card">
				<?php wp_nonce_field( 'adrian_site_cache_settings' ); ?>
				<input type="hidden" name="adrian_site_cache_action" value="save">
				<div class="asc-section-head"><div><p class="asc-section-label">Betrieb</p><h2>Cache-Steuerung</h2></div></div>
				<div class="asc-settings-grid">
					<div>
						<label class="asc-toggle"><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?>><span><strong>Cache-Steuerung aktiv</strong><small>Öffentliche, parameterlose Seiten werden zwischengespeichert.</small></span></label>
						<label class="asc-toggle"><input type="checkbox" name="early" value="1" <?php checked( ! empty( $options['early'] ) ); ?>><span><strong>Frühe Auslieferung über den Drop-in</strong><small>Optional: Cache-Treffer werden vor dem WordPress-Start ausgeliefert.</small></span></label>
						<label class="asc-toggle"><input type="checkbox" name="warm_after_purge" value="1" <?php checked( ! empty( $options['warm_after_purge'] ) ); ?>><span><strong>Öffentliche Seiten nach Leerung vorwärmen</strong><small>Prüft nach einer Leerung Startseite und konfigurierten Blog-Einstieg mit wenigen, internen GET-Anfragen. Standardmäßig aus.</small></span></label>
						<p class="asc-note"><strong>Eigener Datei-Cache.</strong> Diese Version arbeitet eigenständig und benötigt kein zusätzliches Full-Page-Cache-Plugin. Die frühe Auslieferung bleibt aus Sicherheitsgründen standardmäßig deaktiviert und sollte erst nach einem Test von Login, Formularen, Datenschutz und allen dynamischen Bereichen aktiviert werden.</p>
					</div>
					<div>
						<label for="adrian-site-cache-mode">Cache-Modus</label>
						<select id="adrian-site-cache-mode" name="mode">
							<?php foreach ( $modes as $mode_key => $mode_data ) : ?>
								<option value="<?php echo esc_attr( $mode_key ); ?>" <?php selected( $mode, $mode_key ); ?>><?php echo esc_html( $mode_data['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">Der Modus legt Gültigkeitsdauer und Speichergrenze gemeinsam fest. So bleibt die Einstellung verständlich und kann nicht versehentlich in eine zu aggressive Einzelkonfiguration kippen.</p>
					</div>
				</div>
				<div class="asc-mode-list" aria-label="Vor- und Nachteile der Cache-Modi">
					<?php foreach ( $modes as $mode_key => $mode_data ) : ?>
						<details class="asc-mode <?php echo $mode === $mode_key ? 'is-current' : ''; ?>" <?php echo $mode === $mode_key ? 'open' : ''; ?>>
							<summary><span class="asc-mode-title"><?php echo esc_html( $mode_data['label'] ); ?></span><span class="asc-mode-summary"><?php echo esc_html( $mode_data['description'] ); ?></span><?php if ( $mode === $mode_key ) : ?><span class="asc-mode-current">Aktuell</span><?php endif; ?></summary>
							<div class="asc-mode-body"><strong>Vorteil:</strong> <?php echo esc_html( $mode_data['advantages'] ); ?><br><strong>Nachteil:</strong> <?php echo esc_html( $mode_data['disadvantages'] ); ?></div>
						</details>
					<?php endforeach; ?>
				</div>
				<p class="asc-facts">Aktueller Modus: <?php echo esc_html( $modes[ $mode ]['label'] ); ?> · <?php echo esc_html( $modes[ $mode ]['ttl'] ); ?> Sekunden · <?php echo esc_html( number_format_i18n( $modes[ $mode ]['max_files'] ) ); ?> Dateien · <?php echo esc_html( size_format( $modes[ $mode ]['max_bytes'] ) ); ?> Speicherlimit.</p>
				<p class="asc-submit-row"><button type="submit" class="button button-primary">Einstellungen speichern</button></p>
			</form>
			<div class="asc-card">
				<div class="asc-section-head"><div><p class="asc-section-label">Pflege</p><h2>Wartung</h2></div></div>
				<div class="asc-maintenance-grid">
					<div class="asc-maintenance-fact"><span>Letzte Leerung</span><strong><?php echo $options['last_purge'] ? esc_html( wp_date( 'd.m.Y H:i', (int) $options['last_purge'] ) ) : 'Noch nicht'; ?></strong></div>
					<div class="asc-maintenance-fact"><span>Nächste Cache-Bereinigung</span><strong><?php echo $cron ? esc_html( wp_date( 'd.m.Y H:i', $cron ) ) : 'Nicht geplant'; ?></strong></div>
					<div class="asc-maintenance-fact"><span>Letzter externer Cronlauf</span><strong><?php echo defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? ( $last_cron ? esc_html( wp_date( 'd.m.Y H:i', $last_cron ) ) : 'Noch nicht' ) : 'Nicht verwendet'; ?></strong></div>
					<div class="asc-maintenance-fact"><span>Letztes Aufwärmen</span><strong><?php echo ! empty( $options['last_warm'] ) ? esc_html( wp_date( 'd.m.Y H:i', (int) $options['last_warm'] ) ) : 'Noch nicht'; ?></strong></div>
				</div>
				<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
					<p class="notice-info">Der interne WordPress-Cron ist deaktiviert. Die Wartung wird über den externen Server-Cronjob ausgeführt. <?php echo $last_cron ? 'Letzter registrierter Cronlauf: ' . esc_html( wp_date( 'd.m.Y H:i', $last_cron ) ) . '.' : 'Ein externer Cronlauf wurde bisher noch nicht registriert.'; ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $options['early'] ) && ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) ) : ?><p class="notice-inline">Für die schnelle frühe Auslieferung muss <code>WP_CACHE</code> aktiviert sein. Ohne diese Konstante arbeitet der Cache nur nach dem WordPress-Start.</p><?php endif; ?>
				<?php if ( ! empty( $options['early'] ) && defined( 'WP_CACHE' ) && WP_CACHE ) : ?><p>Frühe Auslieferung: <?php echo file_exists( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ) && false !== strpos( (string) file_get_contents( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ), 'ADRIAN_SITE_CACHE_DROPIN' ) ? 'aktiv' : 'nicht aktiv'; ?></p><?php endif; ?>
				<p class="asc-help">Manuelle Leerungen sind jederzeit möglich. Die automatische Bereinigung entfernt abgelaufene Dateien und hält das konfigurierte Speicherlimit ein.</p>
				<div class="asc-actions">
					<form method="post"><?php wp_nonce_field( 'adrian_site_cache_settings' ); ?><input type="hidden" name="adrian_site_cache_action" value="purge"><button class="button">Alle Caches leeren</button></form>
					<form method="post"><?php wp_nonce_field( 'adrian_site_cache_settings' ); ?><input type="hidden" name="adrian_site_cache_action" value="gc"><button class="button">Eigene Cache-Dateien bereinigen</button></form>
					<form method="post"><?php wp_nonce_field( 'adrian_site_cache_settings' ); ?><input type="hidden" name="adrian_site_cache_action" value="warm"><button class="button">Öffentliche Seiten vorwärmen</button></form>
				</div>
			</div>
		</div>
		<?php
	}

	public function cli_command( array $args, array $assoc_args ): void {
		$action = $args[0] ?? 'status';
		if ( 'purge' === $action ) {
			$this->purge( 'cli' );
			WP_CLI::success( 'Cache geleert.' );
			return;
		}
		if ( 'gc' === $action ) {
			$this->garbage_collect();
			WP_CLI::success( 'Eigene Cache-Dateien bereinigt.' );
			return;
		}
		if ( 'warm' === $action ) {
			$result = $this->warm_cache();
			WP_CLI::success( $result['message'] );
			return;
		}

		$options = $this->options();
		$stats   = $this->cache_stats();
		WP_CLI::log( 'enabled=' . ( ! empty( $options['enabled'] ) ? '1' : '0' ) );
		WP_CLI::log( 'engine=native' );
		WP_CLI::log( 'mode=' . $options['mode'] );
		WP_CLI::log( 'early=' . ( ! empty( $options['early'] ) ? '1' : '0' ) );
		WP_CLI::log( 'warm_after_purge=' . ( ! empty( $options['warm_after_purge'] ) ? '1' : '0' ) );
		WP_CLI::log( 'last_cron_run=' . ( (int) get_option( self::CRON_LAST_OPTION, 0 ) ?: '0' ) );
		WP_CLI::log( 'last_warm=' . ( (int) ( $options['last_warm'] ?? 0 ) ?: '0' ) );
		WP_CLI::log( 'cache_files=' . $stats['count'] );
		WP_CLI::log( 'cache_bytes=' . $stats['bytes'] );
	}
}

register_activation_hook( __FILE__, [ 'Adrian_Site_Cache', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Adrian_Site_Cache', 'deactivate' ] );
Adrian_Site_Cache::instance();

