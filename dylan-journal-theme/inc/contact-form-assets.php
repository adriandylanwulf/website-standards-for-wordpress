<?php
/**
 * Targeted Contact Form 7 asset loading.
 *
 * @package Dylan_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether the current singular page needs Contact Form 7 assets.
 *
 * @return bool
 */
function dylan_journal_page_has_contact_form() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_post();
	if ( ! $post ) {
		return false;
	}

	return is_page( 'kontaktformular' ) || dylan_journal_content_has_form( $post->post_content );
}

/**
 * Inspect stored blocks without rendering them or executing shortcodes.
 *
 * The explicit work limits deliberately fail open: when unusual, deeply
 * nested content exceeds the inspection budget, Contact Form 7 assets remain
 * available rather than risking a broken form. This avoids recursive parsing
 * of author-controlled block trees on every singular request.
 *
 * @param string $content Stored post content.
 * @return bool
 */
function dylan_journal_content_has_form( $content ) {
	if ( ! is_string( $content ) || '' === trim( $content ) ) {
		return false;
	}

	$max_bytes  = 524288;
	$max_nodes  = 300;
	$max_depth  = 12;
	$seen_bytes = 0;
	$seen_nodes = 0;
	$seen_refs  = array();
	$queue      = array(
		array(
			'kind'  => 'content',
			'value' => $content,
			'depth' => 0,
		),
	);

	while ( ! empty( $queue ) ) {
		$item  = array_pop( $queue );
		$depth = (int) $item['depth'];

		if ( $depth > $max_depth ) {
			return true;
		}

		if ( 'content' === $item['kind'] ) {
			$stored_content = (string) $item['value'];
			$seen_bytes    += strlen( $stored_content );

			if ( $seen_bytes > $max_bytes ) {
				return true;
			}

			if ( has_shortcode( $stored_content, 'contact-form-7' ) || has_block( 'contact-form-7/contact-form-selector', $stored_content ) ) {
				return true;
			}

			$blocks = parse_blocks( $stored_content );
			if ( ! is_array( $blocks ) ) {
				continue;
			}

			foreach ( $blocks as $block ) {
				if ( is_array( $block ) ) {
					$queue[] = array(
						'kind'  => 'block',
						'value' => $block,
						'depth' => $depth,
					);
				}
			}

			continue;
		}

		++$seen_nodes;
		if ( $seen_nodes > $max_nodes ) {
			return true;
		}

		$block      = $item['value'];
		$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

		if ( 'contact-form-7/contact-form-selector' === $block_name ) {
			return true;
		}

		$inner_html = isset( $block['innerHTML'] ) ? (string) $block['innerHTML'] : '';
		if ( '' !== $inner_html && has_shortcode( $inner_html, 'contact-form-7' ) ) {
			return true;
		}

		if ( 'core/block' === $block_name && ! empty( $block['attrs']['ref'] ) ) {
			$reference_id = absint( $block['attrs']['ref'] );
			if ( $reference_id && ! isset( $seen_refs[ $reference_id ] ) ) {
				$seen_refs[ $reference_id ] = true;
				$reusable                   = get_post( $reference_id );

				if ( $reusable && is_string( $reusable->post_content ) ) {
					$queue[] = array(
						'kind'  => 'content',
						'value' => $reusable->post_content,
						'depth' => $depth + 1,
					);
				}
			}
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				if ( is_array( $inner_block ) ) {
					$queue[] = array(
						'kind'  => 'block',
						'value' => $inner_block,
							'depth' => $depth + 1,
						);
					}
				}
			}
	}

	return false;
}

/**
 * Prevent Contact Form 7 from adding its CSS and JavaScript to unrelated
 * pages. Turnstile remains owned by its plugin because it can protect other
 * forms, including login pages.
 *
 * @return void
 */
function dylan_journal_limit_contact_form_assets() {
	if ( is_admin() || dylan_journal_page_has_contact_form() ) {
		return;
	}

	wp_dequeue_style( 'contact-form-7' );
	wp_dequeue_script( 'contact-form-7' );
	wp_dequeue_script( 'swv' );
	// Site Kit's Contact Form 7 event provider is only useful where a form
	// can actually be interacted with. Keep it on form pages and avoid the
	// extra request on the homepage, archives and ordinary articles.
	wp_dequeue_script( 'googlesitekit-events-provider-contact-form-7' );
}
add_action( 'wp_enqueue_scripts', 'dylan_journal_limit_contact_form_assets', PHP_INT_MAX );
