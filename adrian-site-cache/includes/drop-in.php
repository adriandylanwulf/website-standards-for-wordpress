<?php
/**
 * Very small early cache reader. It runs from wp-content/advanced-cache.php,
 * before WordPress loads the theme and most plugins.
 */

defined( 'WP_CONTENT_DIR' ) || exit;

$adrian_site_cache_request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$adrian_site_cache_request_uri    = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
$adrian_site_cache_query          = (string) ( $_SERVER['QUERY_STRING'] ?? '' );
$adrian_site_cache_cookie         = (string) ( $_SERVER['HTTP_COOKIE'] ?? '' );
$adrian_site_cache_path           = (string) parse_url( $adrian_site_cache_request_uri, PHP_URL_PATH );

if ( ! in_array( $adrian_site_cache_request_method, [ 'GET', 'HEAD' ], true ) || '' !== $adrian_site_cache_query || '' !== $adrian_site_cache_cookie || '' !== (string) ( $_SERVER['HTTP_AUTHORIZATION'] ?? '' ) ) {
	return;
}

foreach ( [ '/wp-admin', '/wp-login.php', '/wp-json', '/xmlrpc.php', '/wp-cron.php', '/feed', '/comments/feed', '/sitemap', '/.well-known' ] as $adrian_site_cache_excluded_path ) {
	if ( 0 === strpos( $adrian_site_cache_path, $adrian_site_cache_excluded_path ) ) {
		return;
	}
}

$adrian_site_cache_dir    = WP_CONTENT_DIR . '/cache/adrian-site-cache/';
$adrian_site_cache_config = $adrian_site_cache_dir . 'config.php';
$adrian_site_cache_file   = $adrian_site_cache_dir . hash( 'sha256', ( $_SERVER['HTTP_HOST'] ?? '' ) . '|' . $adrian_site_cache_path ) . '.html';
$adrian_site_cache_ttl    = 900;
$adrian_site_cache_enabled = true;

if ( is_readable( $adrian_site_cache_config ) ) {
	$adrian_site_cache_values = require $adrian_site_cache_config;
	if ( is_array( $adrian_site_cache_values ) ) {
		$adrian_site_cache_ttl     = max( 60, (int) ( $adrian_site_cache_values['ttl'] ?? 900 ) );
		$adrian_site_cache_enabled = ! empty( $adrian_site_cache_values['enabled'] );
	}
}

if ( ! $adrian_site_cache_enabled || ! is_readable( $adrian_site_cache_file ) || time() - (int) filemtime( $adrian_site_cache_file ) > $adrian_site_cache_ttl ) {
	return;
}

$adrian_site_cache_gzip = $adrian_site_cache_file . '.gz';
$adrian_site_cache_use_gzip = is_readable( $adrian_site_cache_gzip ) && false !== stripos( (string) ( $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '' ), 'gzip' );
$adrian_site_cache_body = $adrian_site_cache_use_gzip ? $adrian_site_cache_gzip : $adrian_site_cache_file;

if ( ! headers_sent() ) {
	header( 'X-Adrian-Site-Cache: HIT' );
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'Content-Length: ' . (string) filesize( $adrian_site_cache_body ) );
	header( 'Cache-Control: public, max-age=60, stale-while-revalidate=30' );
	if ( $adrian_site_cache_use_gzip ) {
		header( 'Content-Encoding: gzip' );
		header( 'Vary: Accept-Encoding' );
	}
}

if ( 'HEAD' !== $adrian_site_cache_request_method ) {
	readfile( $adrian_site_cache_body );
}

exit;
