<?php
/**
 * Plugin Name: Adrian Site Cache
 * Description: Schlanke, sichere Cache-Steuerung für eine persönliche WordPress-Website mit WP-Super-Cache-Integration und eigenem Datei-Cache als Fallback.
 * Version: 1.0.7
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Adrian Dylan Wulf
 * License: GPL-2.0-or-later
 * Text Domain: adrian-site-cache
 */

defined( 'ABSPATH' ) || exit;

final class Adrian_Site_Cache {
	private const VERSION      = '1.0.7';
	private const OPTION       = 'adrian_site_cache_options';
	private const VERSION_OPTION = 'adrian_site_cache_version';
	private const DROPIN_BACKUP_OPTION = 'adrian_site_cache_previous_dropin';
	private const CRON_HOOK    = 'adrian_site_cache_gc';
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
		if ( is_multisite() ) {
			$options['engine'] = 'controller';
			$options['early']  = 0;
		}
		update_option( self::OPTION, $options, false );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}

		$instance = self::instance();
		$instance->ensure_native_cache_dir();
		if ( is_multisite() || $instance->wp_super_cache_active() ) {
			$instance->remove_owned_dropin();
			$instance->delete_native_cache_files();
		}
		update_option( self::VERSION_OPTION, self::VERSION, false );
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		self::instance()->remove_owned_dropin();
	}

	private static function defaults(): array {
		return [
			'enabled'    => 1,
			'engine'     => 'controller',
			'early'      => 0,
			'mode'       => 'normal',
			'ttl'        => 900,
			'max_files'  => 2000,
			'max_bytes'  => 67108864,
			'last_purge' => 0,
		];
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'maybe_upgrade' ], 20 );
		add_action( 'init', [ $this, 'maybe_schedule' ], 20 );
		add_action( 'template_redirect', [ $this, 'maybe_serve_or_buffer' ], 0 );
		add_action( self::CRON_HOOK, [ $this, 'garbage_collect' ] );

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
		add_action( 'activated_plugin', [ $this, 'handle_plugin_lifecycle' ], 20, 2 );
		add_action( 'deactivated_plugin', [ $this, 'handle_plugin_lifecycle' ], 20, 2 );

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

		// Do not let a new drop-in serve files created by an older implementation.
		$this->remove_owned_dropin();
		$this->delete_native_cache_files();
		if ( is_multisite() || $this->wp_super_cache_active() ) {
			$options['engine'] = 'controller';
			$options['early']  = 0;
		}
		update_option( self::OPTION, $options, false );
		update_option( self::VERSION_OPTION, self::VERSION, false );
	}

	private function wp_super_cache_active(): bool {
		$active = (array) get_option( 'active_plugins', [] );
		if ( in_array( 'wp-super-cache/wp-cache.php', $active, true ) ) {
			return true;
		}

		if ( is_multisite() ) {
			$network_active = (array) get_site_option( 'active_sitewide_plugins', [] );
			return isset( $network_active['wp-super-cache/wp-cache.php'] );
		}

		return false;
	}

	private function native_mode(): bool {
		$options = $this->options();
		return ! is_multisite() && 'native' === $options['engine'] && ! $this->wp_super_cache_active();
	}

	public function handle_plugin_lifecycle( string $plugin ): void {
		if ( 'wp-super-cache/wp-cache.php' !== $plugin || ! $this->wp_super_cache_active() ) {
			return;
		}

		$options           = $this->options();
		$options['engine'] = 'controller';
		$options['early']  = 0;
		update_option( self::OPTION, $options, false );
		$this->remove_owned_dropin();
		$this->delete_native_cache_files();
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
		if ( ! defined( 'WP_CONTENT_DIR' ) || ! $this->native_mode() || empty( $this->options()['enabled'] ) ) {
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
		if ( ! headers_sent() ) {
			header( 'X-Adrian-Site-Cache: HIT' );
			header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		}

		if ( 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			readfile( $file );
		}
		exit;
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
		if ( $this->wp_super_cache_active() && function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		$this->delete_native_cache_files();
		$options             = $this->options();
		$options['last_purge'] = time();
		update_option( self::OPTION, $options, false );
		if ( $this->native_mode() ) {
			$this->write_dropin_config();
			$this->sync_owned_dropin();
		}
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
	}

	private function cache_stats(): array {
		$dir   = $this->native_mode() ? $this->native_cache_dir() : WP_CONTENT_DIR . '/cache/supercache';
		$count = 0;
		$bytes = 0;
		if ( is_dir( $dir ) ) {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $iterator as $file ) {
				if ( $file->isFile() ) {
					$count++;
					$bytes += $file->getSize();
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
		if ( 'save' !== $action ) {
			return;
		}

		$options = $this->options();
		$engine  = sanitize_key( wp_unslash( $_POST['engine'] ?? 'controller' ) );
		if ( ! in_array( $engine, [ 'controller', 'native' ], true ) ) {
			$engine = 'controller';
		}
		if ( 'native' === $engine && ( $this->wp_super_cache_active() || is_multisite() ) ) {
			$this->redirect_admin( 'conflict' );
		}

		$modes = self::cache_modes();
		$mode  = sanitize_key( wp_unslash( $_POST['mode'] ?? 'normal' ) );
		if ( ! isset( $modes[ $mode ] ) ) {
			$mode = 'normal';
		}
		$options['enabled']   = empty( $_POST['enabled'] ) ? 0 : 1;
		$options['engine']    = $engine;
		$options['early']     = empty( $_POST['early'] ) ? 0 : 1;
		$options['mode']      = $mode;
		$options['ttl']       = $modes[ $mode ]['ttl'];
		$options['max_files'] = $modes[ $mode ]['max_files'];
		$options['max_bytes'] = $modes[ $mode ]['max_bytes'];
		update_option( self::OPTION, $options, false );
		if ( 'native' === $engine ) {
			$this->ensure_native_cache_dir();
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
		$modes   = self::cache_modes();
		$mode    = isset( $modes[ $options['mode'] ] ) ? $options['mode'] : 'normal';
		?>
		<div class="wrap adrian-site-cache-admin">
			<style>
				.adrian-site-cache-admin{max-width:860px}.adrian-site-cache-admin .asc-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px;margin:18px 0}.adrian-site-cache-admin .asc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.adrian-site-cache-admin .asc-stat{background:#f6f7f7;border-radius:6px;padding:14px}.adrian-site-cache-admin .asc-stat strong{display:block;font-size:20px;margin-top:4px}.adrian-site-cache-admin label{display:block;margin:14px 0 6px;font-weight:600}.adrian-site-cache-admin select{min-width:260px}.adrian-site-cache-admin .description{color:#50575e}.adrian-site-cache-admin .notice-inline{padding:10px 12px;border-left:4px solid #dba617;background:#fff8e5}.adrian-site-cache-admin .asc-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}.adrian-site-cache-admin .asc-mode-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px}.adrian-site-cache-admin .asc-mode{background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;padding:12px}.adrian-site-cache-admin .asc-mode strong{display:block;margin-bottom:4px}.adrian-site-cache-admin .asc-mode small{display:block;color:#50575e;margin-top:5px}.adrian-site-cache-admin .asc-facts{color:#50575e;margin:8px 0 0}@media(max-width:700px){.adrian-site-cache-admin .asc-grid,.adrian-site-cache-admin .asc-mode-list{grid-template-columns:1fr}}
			</style>
			<h1>Adrian Site Cache</h1>
			<p>Schlanke Cache-Steuerung für diese Website. Der Cache wird nur für öffentliche GET-Seiten verwendet.</p>
			<?php if ( 'conflict' === $message ) : ?><div class="notice-inline"><p>Der eigene Datei-Cache wurde nicht aktiviert, weil entweder WP Super Cache noch aktiv ist oder diese Installation eine WordPress-Multisite ist. Der native Cache bleibt in diesen Fällen aus Sicherheitsgründen deaktiviert.</p></div><?php endif; ?>
			<?php if ( is_multisite() ) : ?><div class="notice-inline"><p>Der native Datei-Cache ist auf Multisite deaktiviert, damit gemeinsame Cache- und Drop-in-Dateien keine Inhalte zwischen Websites vermischen können.</p></div><?php endif; ?>
			<?php if ( in_array( $message, [ 'saved', 'purged', 'collected' ], true ) ) : ?><div class="notice notice-success is-dismissible"><p>Cache-Einstellung gespeichert.</p></div><?php endif; ?>
			<div class="asc-card">
				<div class="asc-grid">
					<div class="asc-stat">Aktiver Weg<strong><?php echo esc_html( $this->wp_super_cache_active() ? 'WP Super Cache' : ( 'native' === $options['engine'] ? 'Eigener Datei-Cache' : 'Kein Page-Cache' ) ); ?></strong></div>
					<div class="asc-stat">Cache-Dateien<strong><?php echo esc_html( number_format_i18n( $stats['count'] ) ); ?></strong></div>
					<div class="asc-stat">Cache-Größe<strong><?php echo esc_html( size_format( $stats['bytes'] ) ); ?></strong></div>
				</div>
			</div>
			<form method="post" class="asc-card">
				<?php wp_nonce_field( 'adrian_site_cache_settings' ); ?>
				<input type="hidden" name="adrian_site_cache_action" value="save">
				<label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?>> Cache-Steuerung aktiv</label>
				<label><input type="checkbox" name="early" value="1" <?php checked( ! empty( $options['early'] ) ); ?>> Frühe Auslieferung über den Drop-in aktivieren</label>
				<label for="adrian-site-cache-engine">Cache-Weg</label>
				<select id="adrian-site-cache-engine" name="engine">
					<option value="controller" <?php selected( $options['engine'], 'controller' ); ?>>WP Super Cache steuern (empfohlen)</option>
					<option value="native" <?php selected( $options['engine'], 'native' ); ?>>Eigener Datei-Cache</option>
				</select>
				<p class="description">Der eigene Datei-Cache darf erst verwendet werden, wenn WP Super Cache deaktiviert wurde. Die frühe Auslieferung bleibt aus Sicherheitsgründen standardmäßig deaktiviert und sollte erst nach einem Test von Login, Formularen, Datenschutz und allen dynamischen Bereichen aktiviert werden.</p>
				<label for="adrian-site-cache-mode">Cache-Modus</label>
				<select id="adrian-site-cache-mode" name="mode">
					<?php foreach ( $modes as $mode_key => $mode_data ) : ?>
						<option value="<?php echo esc_attr( $mode_key ); ?>" <?php selected( $mode, $mode_key ); ?>><?php echo esc_html( $mode_data['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">Der Modus legt Gültigkeitsdauer und Speichergrenze gemeinsam fest. So bleibt die Einstellung verständlich und kann nicht versehentlich in eine zu aggressive Einzelkonfiguration kippen.</p>
				<div class="asc-mode-list" aria-label="Vor- und Nachteile der Cache-Modi">
					<?php foreach ( $modes as $mode_data ) : ?>
						<div class="asc-mode">
							<strong><?php echo esc_html( $mode_data['label'] ); ?></strong>
							<span><?php echo esc_html( $mode_data['description'] ); ?></span>
							<small><strong>Vorteil:</strong> <?php echo esc_html( $mode_data['advantages'] ); ?><br><strong>Nachteil:</strong> <?php echo esc_html( $mode_data['disadvantages'] ); ?></small>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="asc-facts">Aktueller Modus: <?php echo esc_html( $modes[ $mode ]['label'] ); ?> · <?php echo esc_html( $modes[ $mode ]['ttl'] ); ?> Sekunden · <?php echo esc_html( number_format_i18n( $modes[ $mode ]['max_files'] ) ); ?> Dateien · <?php echo esc_html( size_format( $modes[ $mode ]['max_bytes'] ) ); ?> Speicherlimit.</p>
				<p><button type="submit" class="button button-primary">Einstellungen speichern</button></p>
			</form>
			<div class="asc-card">
				<h2>Wartung</h2>
				<p>Letzte Leerung: <?php echo $options['last_purge'] ? esc_html( wp_date( 'd.m.Y H:i', (int) $options['last_purge'] ) ) : 'noch nicht'; ?></p>
				<p>Cronjob: <?php echo $cron ? esc_html( wp_date( 'd.m.Y H:i', $cron ) ) : 'nicht geplant'; ?></p>
				<?php if ( 'native' === $options['engine'] && ! $this->wp_super_cache_active() && defined( 'WP_CACHE' ) && WP_CACHE ) : ?><p>Frühe Auslieferung: <?php echo ! empty( $options['early'] ) && file_exists( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ) && false !== strpos( (string) file_get_contents( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ), 'ADRIAN_SITE_CACHE_DROPIN' ) ? 'aktiv' : 'nicht aktiv'; ?></p><?php endif; ?>
				<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?><p class="notice-inline">WordPress-Cron ist deaktiviert. Die automatische Bereinigung funktioniert nur, wenn Mittwald regelmäßig <code>wp-cron.php</code> oder WP-CLI ausführt.</p><?php endif; ?>
				<?php if ( 'native' === $options['engine'] && ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) ) : ?><p class="notice-inline">Für die schnelle frühe Auslieferung muss <code>WP_CACHE</code> aktiviert sein. Ohne diese Konstante arbeitet der Cache nur nach dem WordPress-Start.</p><?php endif; ?>
				<div class="asc-actions">
					<form method="post"><?php wp_nonce_field( 'adrian_site_cache_settings' ); ?><input type="hidden" name="adrian_site_cache_action" value="purge"><button class="button">Alle Caches leeren</button></form>
					<form method="post"><?php wp_nonce_field( 'adrian_site_cache_settings' ); ?><input type="hidden" name="adrian_site_cache_action" value="gc"><button class="button">Eigene Cache-Dateien bereinigen</button></form>
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

		$options = $this->options();
		$stats   = $this->cache_stats();
		WP_CLI::log( 'enabled=' . ( ! empty( $options['enabled'] ) ? '1' : '0' ) );
		WP_CLI::log( 'engine=' . $options['engine'] );
		WP_CLI::log( 'mode=' . $options['mode'] );
		WP_CLI::log( 'wp_super_cache=' . ( $this->wp_super_cache_active() ? '1' : '0' ) );
		WP_CLI::log( 'cache_files=' . $stats['count'] );
		WP_CLI::log( 'cache_bytes=' . $stats['bytes'] );
	}
}

register_activation_hook( __FILE__, [ 'Adrian_Site_Cache', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Adrian_Site_Cache', 'deactivate' ] );
Adrian_Site_Cache::instance();
