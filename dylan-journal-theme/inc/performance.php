<?php
/**
 * Conservative, front-end performance improvements.
 *
 * @package Dylan_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The browser-native emoji implementation is sufficient for this site.
 *
 * @return void
 */
function dylan_journal_disable_emoji_fallbacks() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_print_footer_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_detection_script' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'dylan_journal_disable_emoji_fallbacks' );

/**
 * Remove obsolete remote-publishing discovery tags. The WordPress REST API,
 * XML-RPC and embeds themselves remain untouched for compatibility.
 *
 * @return void
 */
function dylan_journal_clean_document_head() {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'wp_generator' );
}
add_action( 'init', 'dylan_journal_clean_document_head' );

/**
 * Do not establish speculative connections to analytics hosts.
 * Cookiebot and any security plugin still load their actual scripts only when
 * their own consent or security rules allow it.
 *
 * @param array  $urls          Resource hints.
 * @param string $relation_type Hint type.
 * @return array
 */
function dylan_journal_remove_unused_google_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' !== $relation_type ) {
		return $urls;
	}

	return array_values(
		array_filter(
			$urls,
			function( $url ) {
				$hint = is_array( $url ) ? wp_json_encode( $url ) : (string) $url;

				return false === strpos( $hint, 'googletagmanager.com' );
			}
		)
	);
}
add_filter( 'wp_resource_hints', 'dylan_journal_remove_unused_google_prefetch', PHP_INT_MAX, 2 );

/**
 * Keep Site Kit's content-event helper on individual articles only. Page
 * views and consent-controlled Analytics remain untouched; archive, contact
 * and legal pages do not need the extra event listener.
 *
 * @return void
 */
function dylan_journal_limit_site_kit_content_events() {
	if ( is_admin() || is_singular( 'post' ) ) {
		return;
	}

	wp_dequeue_script( 'googlesitekit-events-provider-content-events' );
}
add_action( 'wp_enqueue_scripts', 'dylan_journal_limit_site_kit_content_events', PHP_INT_MAX );

/**
 * Keep editor-only WordPress AI styles out of public pages.
 *
 * The AI plugin registers these styles through `enqueue_block_assets` so they
 * are available inside the block editor iframe. The styles contain controls
 * for summarization and translation and are not used by this theme on the
 * public site. Dequeue them only on the front end; the admin/editor remains
 * unchanged so the AI tools continue to work there.
 *
 * @return void
 */
function dylan_journal_remove_editor_only_ai_styles() {
	if ( is_admin() ) {
		return;
	}

	foreach ( array( 'ai_summarization', 'ai_content_translation' ) as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'dylan_journal_remove_editor_only_ai_styles', PHP_INT_MAX );
