<?php
/**
 * Very small early cache reader. It runs from wp-content/advanced-cache.php,
 * before WordPress loads the theme and most plugins.
 */

defined( 'WP_CONTENT_DIR' ) || exit;

if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
	return;
}

if ( defined( 'MULTISITE' ) && MULTISITE ) {
	return;
}

$adrian_site_cache_request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$adrian_site_cache_request_uri    = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
$adrian_site_cache_query          = (string) ( $_SERVER['QUERY_STRING'] ?? '' );
$adrian_site_cache_cookie         = (string) ( $_SERVER['HTTP_COOKIE'] ?? '' );
$adrian_site_cache_path           = (string) parse_url( $adrian_site_cache_request_uri, PHP_URL_PATH );
$adrian_site_cache_cache_control  = strtolower( (string) ( $_SERVER['HTTP_CACHE_CONTROL'] ?? '' ) );
$adrian_site_cache_pragma         = strtolower( (string) ( $_SERVER['HTTP_PRAGMA'] ?? '' ) );
$adrian_site_cache_requested_with = strtolower( (string) ( $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' ) );

if ( ! in_array( $adrian_site_cache_request_method, [ 'GET', 'HEAD' ], true ) || '' !== $adrian_site_cache_query || '' !== $adrian_site_cache_cookie || '' !== (string) ( $_SERVER['HTTP_AUTHORIZATION'] ?? '' ) || preg_match( '/(?:^|,)\s*(?:no-cache|no-store|max-age\s*=\s*0)\s*(?:,|$)/', $adrian_site_cache_cache_control ) || false !== strpos( $adrian_site_cache_pragma, 'no-cache' ) || 'xmlhttprequest' === $adrian_site_cache_requested_with ) {
	return;
}

foreach ( [ '/wp-admin', '/wp-login.php', '/wp-json', '/xmlrpc.php', '/wp-cron.php', '/feed', '/comments/feed', '/sitemap', '/wp-sitemap', '/robots.txt', '/manifest.webmanifest', '/llms.txt', '/humans.txt', '/.well-known' ] as $adrian_site_cache_excluded_path ) {
	if ( 0 === strpos( $adrian_site_cache_path, $adrian_site_cache_excluded_path ) ) {
		return;
	}
}

$adrian_site_cache_dir    = WP_CONTENT_DIR . '/cache/adrian-site-cache/';
$adrian_site_cache_config = $adrian_site_cache_dir . 'config.php';
$adrian_site_cache_key_prefix = '';
$adrian_site_cache_file   = '';
$adrian_site_cache_ttl    = 900;
$adrian_site_cache_enabled = true;
$adrian_site_cache_early   = false;

if ( is_readable( $adrian_site_cache_config ) ) {
	$adrian_site_cache_values = require $adrian_site_cache_config;
	if ( is_array( $adrian_site_cache_values ) ) {
		if ( 2 !== absint( $adrian_site_cache_values['dropin_version'] ?? 0 ) || 'native' !== (string) ( $adrian_site_cache_values['engine'] ?? '' ) || ! empty( $adrian_site_cache_values['multisite'] ) ) {
			return;
		}
		$adrian_site_cache_ttl     = max( 60, (int) ( $adrian_site_cache_values['ttl'] ?? 900 ) );
		$adrian_site_cache_enabled = ! empty( $adrian_site_cache_values['enabled'] );
		$adrian_site_cache_early   = ! empty( $adrian_site_cache_values['early'] );
		$adrian_site_cache_key_prefix = (string) ( $adrian_site_cache_values['key_prefix'] ?? '' );
	}
}

if ( ! $adrian_site_cache_early || '' === $adrian_site_cache_key_prefix ) {
	return;
}

$adrian_site_cache_configured_host = strtolower( (string) parse_url( $adrian_site_cache_key_prefix, PHP_URL_HOST ) );
$adrian_site_cache_request_host    = strtolower( (string) parse_url( 'https://' . (string) ( $_SERVER['HTTP_HOST'] ?? '' ), PHP_URL_HOST ) );
$adrian_site_cache_valid_host = static function ( string $host ): bool {
	return '' !== $host && ( false !== filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) || false !== filter_var( $host, FILTER_VALIDATE_IP ) );
};
if ( ! $adrian_site_cache_valid_host( $adrian_site_cache_configured_host ) || ! $adrian_site_cache_valid_host( $adrian_site_cache_request_host ) || $adrian_site_cache_configured_host !== $adrian_site_cache_request_host ) {
	return;
}

if ( '' !== $adrian_site_cache_key_prefix ) {
	$adrian_site_cache_file = $adrian_site_cache_dir . hash( 'sha256', $adrian_site_cache_key_prefix . '|' . $adrian_site_cache_path ) . '.html';
}

if ( ! $adrian_site_cache_enabled || '' === $adrian_site_cache_file || ! is_readable( $adrian_site_cache_file ) || time() - (int) filemtime( $adrian_site_cache_file ) > $adrian_site_cache_ttl ) {
	return;
}

$adrian_site_cache_gzip = $adrian_site_cache_file . '.gz';
$adrian_site_cache_use_gzip = is_readable( $adrian_site_cache_gzip ) && false !== stripos( (string) ( $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '' ), 'gzip' );
$adrian_site_cache_body = $adrian_site_cache_use_gzip ? $adrian_site_cache_gzip : $adrian_site_cache_file;
$adrian_site_cache_mtime = is_file( $adrian_site_cache_body ) ? (int) filemtime( $adrian_site_cache_body ) : 0;
$adrian_site_cache_size  = is_file( $adrian_site_cache_body ) ? (int) filesize( $adrian_site_cache_body ) : 0;

if ( $adrian_site_cache_mtime > 0 && $adrian_site_cache_size >= 0 && ! headers_sent() ) {
	$adrian_site_cache_etag = 'W/"' . $adrian_site_cache_mtime . '-' . $adrian_site_cache_size . ( $adrian_site_cache_use_gzip ? '-gzip' : '' ) . '"';
	header( 'X-Adrian-Site-Cache: HIT' );
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'Cache-Control: public, max-age=60, stale-while-revalidate=30' );
	if ( $adrian_site_cache_use_gzip ) {
		header( 'Content-Encoding: gzip' );
		header( 'Vary: Accept-Encoding' );
	}
	header( 'ETag: ' . $adrian_site_cache_etag );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \\G\\M\\T', $adrian_site_cache_mtime ) );
	$adrian_site_cache_if_none_match = trim( (string) ( $_SERVER['HTTP_IF_NONE_MATCH'] ?? '' ) );
	$adrian_site_cache_not_modified = false;
	if ( '' !== $adrian_site_cache_if_none_match ) {
		$adrian_site_cache_normalized_etag = preg_replace( '/^W\\//', '', $adrian_site_cache_etag );
		foreach ( explode( ',', $adrian_site_cache_if_none_match ) as $adrian_site_cache_candidate ) {
			$adrian_site_cache_candidate = preg_replace( '/^W\\//', '', trim( $adrian_site_cache_candidate ) );
			if ( '*' === $adrian_site_cache_candidate || ( is_string( $adrian_site_cache_normalized_etag ) && $adrian_site_cache_candidate === $adrian_site_cache_normalized_etag ) ) {
				$adrian_site_cache_not_modified = true;
				break;
			}
		}
	} elseif ( ! empty( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
		$adrian_site_cache_since = strtotime( (string) $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
		$adrian_site_cache_not_modified = false !== $adrian_site_cache_since && $adrian_site_cache_since >= $adrian_site_cache_mtime;
	}
	if ( $adrian_site_cache_not_modified ) {
		http_response_code( 304 );
		header( 'Content-Length: 0' );
	exit;
	}
}

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
