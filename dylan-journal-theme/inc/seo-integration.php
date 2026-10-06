<?php
/**
 * Compatible sitemap and metadata rules for optional SEO plugins.
 *
 * @package Dylan_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the placeholder photo-page ID only when it is genuinely empty.
 *
 * @return int
 */
function dylan_journal_empty_photos_id() {
	$page = get_page_by_path( 'fotos' );
	if ( ! $page || 'publish' !== $page->post_status ) {
		return 0;
	}

	// The photo page is rendered from attachments assigned to the page. Its
	// editor content can therefore stay empty while the public page still has
	// useful, indexable images.
	$published_photos = get_children(
		array(
			'post_parent'    => $page->ID,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $published_photos ) ) {
		return 0;
	}

	$content             = $page->post_content;
	$has_images          = has_post_thumbnail( $page->ID ) || has_block( 'core/image', $content ) || has_block( 'core/gallery', $content ) || has_block( 'core/cover', $content ) || false !== stripos( $content, '<img' );
	$has_dynamic_content = has_block( 'core/block', $content ) || has_block( 'core/shortcode', $content ) || false !== strpos( $content, '[' );

	return $has_images || $has_dynamic_content ? 0 : (int) $page->ID;
}

/**
 * Tags with no introduction and fewer than two articles are navigation only.
 *
 * @param WP_Term $term Taxonomy term.
 * @return bool
 */
function dylan_journal_thin_tag( $term ) {
	return $term instanceof WP_Term
		&& 'post_tag' === $term->taxonomy
		&& $term->count < 2
		&& '' === trim( wp_strip_all_tags( $term->description ) );
}

/**
 * Check views that should stay available to readers but out of search indexes.
 *
 * @return bool
 */
function dylan_journal_should_noindex() {
	return is_404()
		|| is_search()
		|| is_date()
		|| ( is_author() && ! is_multi_author() )
		|| ( is_tag() && dylan_journal_thin_tag( get_queried_object() ) )
		|| ( is_page( 'fotos' ) && get_queried_object_id() === dylan_journal_empty_photos_id() );
}

/**
 * Extend Yoast's robots output if Yoast owns metadata.
 *
 * @param array $robots Robots values.
 * @return array
 */
function dylan_journal_yoast_robots( $robots ) {
	if ( dylan_journal_should_noindex() ) {
		$robots['index'] = 'noindex';
	}

	return $robots;
}
add_filter( 'wpseo_robots_array', 'dylan_journal_yoast_robots', 20 );

/**
 * Add the empty photo page to plugin sitemap exclusions.
 *
 * @param array $ids Existing exclusions.
 * @return array
 */
function dylan_journal_excluded_page_ids( $ids ) {
	$id = dylan_journal_empty_photos_id();
	if ( $id ) {
		$ids[] = $id;
	}

	return array_values( array_unique( $ids ) );
}
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'dylan_journal_excluded_page_ids' );

/**
 * Clear the cached list of thin tag IDs after taxonomy changes.
 *
 * @return void
 */
function dylan_journal_clear_thin_tag_cache() {
	delete_transient( 'dylan_journal_thin_tag_ids' );
}
add_action( 'created_post_tag', 'dylan_journal_clear_thin_tag_cache' );
add_action( 'edited_post_tag', 'dylan_journal_clear_thin_tag_cache' );
add_action( 'delete_post_tag', 'dylan_journal_clear_thin_tag_cache' );

/**
 * Add thin tags to plugin sitemap exclusions.
 *
 * @param array $ids Existing exclusions.
 * @return array
 */
function dylan_journal_excluded_tag_ids( $ids ) {
	$thin_tag_ids = get_transient( 'dylan_journal_thin_tag_ids' );

	if ( false === $thin_tag_ids ) {
		$thin_tag_ids = array();
		$terms        = get_terms(
			array(
				'taxonomy'   => 'post_tag',
				'fields'     => 'all',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( dylan_journal_thin_tag( $term ) ) {
					$thin_tag_ids[] = (int) $term->term_id;
				}
			}
		}

		set_transient( 'dylan_journal_thin_tag_ids', $thin_tag_ids, HOUR_IN_SECONDS );
	}

	$ids = array_merge( $ids, (array) $thin_tag_ids );

	return array_values( array_unique( $ids ) );
}
add_filter( 'wpseo_exclude_from_sitemap_by_term_ids', 'dylan_journal_excluded_tag_ids' );

/**
 * Apply the same thin-tag rule to WordPress Core sitemaps.
 *
 * @param array  $args     Sitemap query args.
 * @param string $taxonomy Taxonomy name.
 * @return array
 */
function dylan_journal_core_sitemap_terms( $args, $taxonomy ) {
	if ( 'post_tag' === $taxonomy ) {
		$args['exclude'] = dylan_journal_excluded_tag_ids(
			isset( $args['exclude'] ) ? (array) $args['exclude'] : array()
		);
	}

	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'dylan_journal_core_sitemap_terms', 10, 2 );

/**
 * Avoid author sitemap entries for a single-author site in Yoast.
 *
 * @param array $users User IDs.
 * @return array
 */
function dylan_journal_yoast_authors( $users ) {
	return is_multi_author() ? $users : array();
}
add_filter( 'wpseo_sitemap_exclude_author', 'dylan_journal_yoast_authors' );

/**
 * Supply a practical fallback only when Yoast fields are empty.
 *
 * @param string $description Existing description.
 * @return string
 */
function dylan_journal_yoast_description( $description ) {
	if ( trim( (string) $description ) || is_404() || is_search() ) {
		return $description;
	}

	return dylan_journal_seo_description();
}
add_filter( 'wpseo_metadesc', 'dylan_journal_yoast_description' );
add_filter( 'wpseo_opengraph_desc', 'dylan_journal_yoast_description' );
add_filter( 'wpseo_twitter_description', 'dylan_journal_yoast_description' );

/**
 * Retain hand-written Yoast titles and improve only generic theme defaults.
 *
 * @param string $title Existing title.
 * @return string
 */
function dylan_journal_yoast_title( $title ) {
	if ( get_post_meta( get_queried_object_id(), '_yoast_wpseo_title', true ) ) {
		return $title;
	}

	if ( is_front_page() && preg_match( '/^(Startseite|Home)\s*[|–—-]\s*/u', $title ) ) {
		return 'Adrian Dylan Wulf – Persönliche Notizen';
	}

	if ( ( is_home() || is_page( 'blog' ) ) && preg_match( '/^(Journal|Blog|Notizen|Beiträge)\s*[|–—-]\s*/u', $title ) ) {
		return 'Blog: Technik & Sicherheit | Adrian Dylan Wulf';
	}

	return $title;
}
add_filter( 'wpseo_title', 'dylan_journal_yoast_title' );
add_filter( 'wpseo_opengraph_title', 'dylan_journal_yoast_title' );
add_filter( 'wpseo_twitter_title', 'dylan_journal_yoast_title' );
