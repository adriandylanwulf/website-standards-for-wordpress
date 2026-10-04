<?php
/**
 * Plugin Name: Adrian Site Cache
 * Description: Schlanke, sichere Cache-Steuerung für eine persönliche WordPress-Website mit WP-Super-Cache-Integration und eigenem Datei-Cache als Fallback.
 * Version: 1.0.1
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Adrian Dylan Wulf
 * License: GPL-2.0-or-later
 * Text Domain: adrian-site-cache
 */

defined( 'ABSPATH' ) || exit;

final class Adrian_Site_Cache {
	private const VERSION      = '1.0.1';
	private const OPTION       = 'adrian_site_cache_options';
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
		update_option( self::OPTION, wp_parse_args( is_array( $current ) ? $current : [], $defaults ), false );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}

		self::instance()->ensure_native_cache_dir();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		self::instance()->remove_owned_dropin();
	}

	private static function defaults(): array {
		return [
			'enabled'    => 1,
			'engine'     => 'controller',
			'ttl'        => 900,
			'max_files'  => 2000,
			'last_purge' => 0,
		];
	}

	private function __construct() {
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
		return 'native' === $options['engine'] && ! $this->wp_super_cache_active();
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
			if ( preg_match( '/^(set-cookie|location|content-disposition):/i', $header ) || preg_match( '/cache-control:.*(private|no-cache|no-store)/i', $header ) || preg_match( '/vary:.*cookie/i', $header ) ) {
				return $html;
			}
		}

		if ( false === stripos( $html, '<html' ) && false === stripos( $html, '<!doctype' ) ) {
			return $html;
		}

		$this->write_cache_file( $this->cache_key(), $html );
		return $html;
	}

	private function request_is_cacheable(): bool {
		if ( 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) && 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			return false;
		}

		if ( is_admin() || is_user_logged_in() || is_preview() || is_search() || is_feed() || ! empty( $_GET ) || ! empty( $_SERVER['QUERY_STRING'] ) || ! empty( $_SERVER['HTTP_COOKIE'] ) || ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
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
			file_put_contents( $index, '', LOCK_EX );
		}

		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Options -Indexes\n<FilesMatch \\\"\\.(php|phtml|phar)$\\\">\n    Require all denied\n</FilesMatch>\n", LOCK_EX );
		}

		$this->write_dropin_config();

		return is_dir( $dir ) && is_writable( $dir );
	}

	private function write_dropin_config(): void {
		$options = $this->options();
		$config  = trailingslashit( $this->native_cache_dir() ) . 'config.php';
		$contents = "<?php\nreturn " . var_export( [
			'ttl'     => max( 60, (int) $options['ttl'] ),
			'enabled' => ! empty( $options['enabled'] ),
		], true ) . ";\n";
		if ( ! file_exists( $config ) || (string) file_get_contents( $config ) !== $contents ) {
			file_put_contents( $config, $contents, LOCK_EX );
		}
	}

	private function sync_owned_dropin(): bool {
		if ( ! defined( 'WP_CONTENT_DIR' ) || ! $this->native_mode() || empty( $this->options()['enabled'] ) ) {
			return false;
		}

		$dropin = trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php';
		$current = file_exists( $dropin ) ? (string) file_get_contents( $dropin ) : '';
		if ( '' !== $current && false === strpos( $current, 'ADRIAN_SITE_CACHE_DROPIN' ) ) {
			$backup = trailingslashit( $this->native_cache_dir() ) . 'previous-advanced-cache.php';
			if ( ! file_exists( $backup ) ) {
				file_put_contents( $backup, $current, LOCK_EX );
			}
		}

		$plugin_file = WP_CONTENT_DIR . '/plugins/adrian-site-cache/includes/drop-in.php';
		$contents    = "<?php\n/* ADRIAN_SITE_CACHE_DROPIN */\nif ( defined( 'WP_CONTENT_DIR' ) ) {\n    $adrian_site_cache_dropin = WP_CONTENT_DIR . '/plugins/adrian-site-cache/includes/drop-in.php';\n    if ( is_readable( $adrian_site_cache_dropin ) ) {\n        require $adrian_site_cache_dropin;\n    }\n}\n";
		if ( ! is_readable( $plugin_file ) || false === file_put_contents( $dropin, $contents, LOCK_EX ) ) {
			return false;
		}

		return true;
	}

	private function remove_owned_dropin(): void {
		$dropin = trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php';
		if ( ! file_exists( $dropin ) || false === strpos( (string) file_get_contents( $dropin ), 'ADRIAN_SITE_CACHE_DROPIN' ) ) {
			return;
		}

		$backup = trailingslashit( $this->native_cache_dir() ) . 'previous-advanced-cache.php';
		if ( file_exists( $backup ) ) {
			@copy( $backup, $dropin );
			@unlink( $backup );
			return;
		}

		@unlink( $dropin );
	}

	private function write_cache_file( string $key, string $html ): void {
		if ( ! $this->ensure_native_cache_dir() ) {
			return;
		}

		$file = $this->cache_file( $key );
		$tmp  = $file . '.' . wp_generate_uuid4() . '.tmp';
		if ( false !== file_put_contents( $tmp, $html, LOCK_EX ) ) {
			@rename( $tmp, $file );
			if ( function_exists( 'gzencode' ) ) {
				file_put_contents( $file . '.gz', gzencode( $html, 6 ), LOCK_EX );
			} else {
				@unlink( $file . '.gz' );
			}
		}
		if ( file_exists( $tmp ) ) {
			@unlink( $tmp );
		}
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

		$options = $this->options();
		$files   = [];
		$dir     = $this->native_cache_dir();
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$iterator = new DirectoryIterator( $dir );
		$now      = time();
		$ttl      = max( 60, (int) $options['ttl'] );
		foreach ( $iterator as $item ) {
			if ( $item->isDot() || ! $item->isFile() || 'html' !== $item->getExtension() ) {
				continue;
			}
			if ( $now - $item->getMTime() > $ttl ) {
				@unlink( $item->getPathname() );
				@unlink( $item->getPathname() . '.gz' );
				continue;
			}
			$files[] = [ 'path' => $item->getPathname(), 'mtime' => $item->getMTime() ];
		}

		usort( $files, static fn( array $a, array $b ): int => $b['mtime'] <=> $a['mtime'] );
		$max_files = max( 100, (int) $options['max_files'] );
		foreach ( array_slice( $files, $max_files ) as $file ) {
			@unlink( $file['path'] );
			@unlink( $file['path'] . '.gz' );
		}
	}

	private function delete_native_cache_files(): void {
		$dir = $this->native_cache_dir();
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$iterator = new DirectoryIterator( $dir );
		foreach ( $iterator as $item ) {
			if ( ! $item->isDot() && $item->isFile() && 'html' === $item->getExtension() ) {
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
		if ( 'native' === $engine && $this->wp_super_cache_active() ) {
			$this->redirect_admin( 'conflict' );
		}

		$options['enabled']   = empty( $_POST['enabled'] ) ? 0 : 1;
		$options['engine']    = $engine;
		$options['ttl']       = min( 86400, max( 60, absint( $_POST['ttl'] ?? 900 ) ) );
		$options['max_files'] = min( 10000, max( 100, absint( $_POST['max_files'] ?? 2000 ) ) );
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
		?>
		<div class="wrap adrian-site-cache-admin">
			<style>
				.adrian-site-cache-admin{max-width:820px}.adrian-site-cache-admin .asc-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px;margin:18px 0}.adrian-site-cache-admin .asc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.adrian-site-cache-admin .asc-stat{background:#f6f7f7;border-radius:6px;padding:14px}.adrian-site-cache-admin .asc-stat strong{display:block;font-size:20px;margin-top:4px}.adrian-site-cache-admin label{display:block;margin:14px 0 6px;font-weight:600}.adrian-site-cache-admin input[type=number]{width:130px}.adrian-site-cache-admin .description{color:#50575e}.adrian-site-cache-admin .notice-inline{padding:10px 12px;border-left:4px solid #dba617;background:#fff8e5}.adrian-site-cache-admin .asc-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}@media(max-width:700px){.adrian-site-cache-admin .asc-grid{grid-template-columns:1fr}}
			</style>
			<h1>Adrian Site Cache</h1>
			<p>Schlanke Cache-Steuerung für diese Website. Der Cache wird nur für öffentliche GET-Seiten verwendet.</p>
			<?php if ( 'conflict' === $message ) : ?><div class="notice-inline"><p>Der eigene Datei-Cache wurde nicht aktiviert, weil WP Super Cache noch aktiv ist. Zwei Page-Caches gleichzeitig würden unnötige Doppelarbeit verursachen.</p></div><?php endif; ?>
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
				<label for="adrian-site-cache-engine">Cache-Weg</label>
				<select id="adrian-site-cache-engine" name="engine">
					<option value="controller" <?php selected( $options['engine'], 'controller' ); ?>>WP Super Cache steuern (empfohlen)</option>
					<option value="native" <?php selected( $options['engine'], 'native' ); ?>>Eigener Datei-Cache</option>
				</select>
				<p class="description">Der eigene Datei-Cache darf erst verwendet werden, wenn WP Super Cache deaktiviert wurde.</p>
				<label for="adrian-site-cache-ttl">Gültigkeit in Sekunden</label>
				<input id="adrian-site-cache-ttl" type="number" name="ttl" min="60" max="86400" value="<?php echo esc_attr( $options['ttl'] ); ?>">
				<label for="adrian-site-cache-max-files">Maximale eigene Cache-Dateien</label>
				<input id="adrian-site-cache-max-files" type="number" name="max_files" min="100" max="10000" value="<?php echo esc_attr( $options['max_files'] ); ?>">
				<p class="description">Für deine persönliche Website sind 15 Minuten und 2.000 Dateien ein guter Ausgangspunkt.</p>
				<p><button type="submit" class="button button-primary">Einstellungen speichern</button></p>
			</form>
			<div class="asc-card">
				<h2>Wartung</h2>
				<p>Letzte Leerung: <?php echo $options['last_purge'] ? esc_html( wp_date( 'd.m.Y H:i', (int) $options['last_purge'] ) ) : 'noch nicht'; ?></p>
				<p>Cronjob: <?php echo $cron ? esc_html( wp_date( 'd.m.Y H:i', $cron ) ) : 'nicht geplant'; ?></p>
				<?php if ( 'native' === $options['engine'] && ! $this->wp_super_cache_active() && defined( 'WP_CACHE' ) && WP_CACHE ) : ?><p>Frühe Auslieferung: <?php echo file_exists( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ) && false !== strpos( (string) file_get_contents( trailingslashit( WP_CONTENT_DIR ) . 'advanced-cache.php' ), 'ADRIAN_SITE_CACHE_DROPIN' ) ? 'aktiv' : 'nicht aktiv'; ?></p><?php endif; ?>
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
		WP_CLI::log( 'wp_super_cache=' . ( $this->wp_super_cache_active() ? '1' : '0' ) );
		WP_CLI::log( 'cache_files=' . $stats['count'] );
		WP_CLI::log( 'cache_bytes=' . $stats['bytes'] );
	}
}

register_activation_hook( __FILE__, [ 'Adrian_Site_Cache', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Adrian_Site_Cache', 'deactivate' ] );
Adrian_Site_Cache::instance();
