<?php
/**
 * Theme setup, assets and small presentation helpers.
 *
 * @package Dylan_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register WordPress capabilities used by the theme.
 *
 * @return void
 */
function dylan_journal_setup() {
	load_theme_textdomain( 'dylan-journal', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/editor.css' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'primary' => __( 'Hauptnavigation', 'dylan-journal' ),
		)
	);
}
add_action( 'after_setup_theme', 'dylan_journal_setup' );

/**
 * Use the file modification time to invalidate a browser cache after updates.
 *
 * @param string $relative_path Path relative to the current theme directory.
 * @return string
 */
function dylan_journal_asset_version( $relative_path ) {
	$file = get_theme_file_path( $relative_path );

	return file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' );
}

/**
 * Load one small local stylesheet and the optional Cookiebot helper.
 *
 * Keeping the readable CSS as the production asset avoids a stale minified
 * copy getting out of sync with its source during a WordPress update.
 *
 * @return void
 */
function dylan_journal_assets() {
	wp_enqueue_style(
		'dylan-journal',
		get_stylesheet_uri(),
		array(),
		dylan_journal_asset_version( 'style.css' )
	);

	$script_path = get_theme_file_path( 'assets/theme.js' );
	if ( file_exists( $script_path ) ) {
		wp_enqueue_script(
			'dylan-journal',
			get_theme_file_uri( 'assets/theme.js' ),
			array(),
			dylan_journal_asset_version( 'assets/theme.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'dylan_journal_assets' );

/**
 * Show useful links if a navigation menu has not been assigned yet.
 *
 * @return void
 */
function dylan_journal_navigation_fallback() {
	$items = array(
		'/'          => __( 'Startseite', 'dylan-journal' ),
		'ueber-mich' => __( 'Über mich', 'dylan-journal' ),
		'blog'       => __( 'Blog', 'dylan-journal' ),
		'fotos'      => __( 'Fotos', 'dylan-journal' ),
			'kontaktformular' => __( 'Kontakt', 'dylan-journal' ),
			'online'     => __( 'Online & Kontakt', 'dylan-journal' ),
	);

	echo '<ul>';
	foreach ( $items as $slug => $label ) {
		if ( '/' === $slug ) {
			$url     = home_url( '/' );
			$current = is_front_page();
		} else {
			$page = get_page_by_path( $slug );
			if ( ! $page || 'publish' !== $page->post_status ) {
				continue;
			}

			$url     = get_permalink( $page );
			$current = is_page( $page->ID ) || ( 'blog' === $slug && is_home() );
		}

		printf(
			'<li><a href="%1$s"%3$s>%2$s</a></li>',
			esc_url( $url ),
			esc_html( $label ),
			$current ? ' aria-current="page"' : ''
		);
	}
	echo '</ul>';
}

/**
 * Resolve a published WordPress page by slug with a predictable fallback.
 *
 * @param string $slug     Page slug.
 * @param string $fallback Fallback relative URL.
 * @return string
 */
function dylan_journal_page_url( $slug, $fallback ) {
	$page = get_page_by_path( $slug );

	return $page && 'publish' === $page->post_status ? get_permalink( $page ) : home_url( $fallback );
}

/**
 * Return the first published image assigned to a page.
 *
 * This keeps template and social-preview fallbacks tied to the current
 * editorial media order instead of to a historical attachment ID.
 *
 * @param string $page_slug Page slug.
 * @return int
 */
function dylan_journal_page_image_id( $page_slug ) {
	$page = get_page_by_path( sanitize_title( $page_slug ) );

	if ( ! $page || 'publish' !== $page->post_status ) {
		return 0;
	}

	$images = get_children(
		array(
			'post_parent'    => $page->ID,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'orderby'        => 'menu_order ID',
			'order'          => 'ASC',
			'numberposts'    => 1,
			'fields'         => 'ids',
		)
	);

	return $images ? absint( reset( $images ) ) : 0;
}

/**
 * Return a compact, plain-text excerpt for article lists.
 *
 * @param int $post_id Optional post ID.
 * @return string
 */
function dylan_journal_excerpt( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	return wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 21, ' …' );
}

/**
 * Estimate reading time from actual article content.
 *
 * @param int $post_id Optional post ID.
 * @return string
 */
function dylan_journal_reading_time( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$content = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
	$words   = preg_match_all( '/[\p{L}\p{N}]+/u', $content, $matches );
	$minutes = max( 1, (int) ceil( $words / 200 ) );

	return sprintf(
		/* translators: %d: estimated reading time in minutes. */
		_n( '%d Min. Lesezeit', '%d Min. Lesezeit', $minutes, 'dylan-journal' ),
		$minutes
	);
}

/**
 * Link the preferred category when an SEO plugin exposes one, otherwise the
 * first editorial category. It avoids storing duplicate taxonomy data.
 *
 * @param int $post_id Optional post ID.
 * @return string
 */
function dylan_journal_primary_category_link( $post_id = 0 ) {
	$post_id    = $post_id ? $post_id : get_the_ID();
	$categories = get_the_category( $post_id );

	if ( empty( $categories ) || is_wp_error( $categories ) ) {
		return '';
	}

	$category = $categories[0];
	if ( class_exists( 'WPSEO_Primary_Term' ) ) {
		$primary = ( new WPSEO_Primary_Term( 'category', $post_id ) )->get_primary_term();
		foreach ( $categories as $candidate ) {
			if ( ! is_wp_error( $primary ) && (int) $primary === (int) $candidate->term_id ) {
				$category = $candidate;
				break;
			}
		}
	}

	return sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( get_category_link( $category->term_id ) ),
		esc_html( $category->name )
	);
}
