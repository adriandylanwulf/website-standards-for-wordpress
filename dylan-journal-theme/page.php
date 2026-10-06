<?php
/** @package Dylan_Journal */
get_header();
while ( have_posts() ) : the_post();
	/*
	 * These pages already provide their own designed H1 within the content.
	 * Respect that page template so visitors and search engines see only one
	 * main heading.
	 */
	/*
	 * Earlier theme uploads stored the template name without its .php suffix.
	 * Accept both values, so existing pages need no manual reassignment in the
	 * WordPress editor.
	 */
	$page_template    = get_page_template_slug( get_the_ID() );
	$has_content_title = is_page_template( 'page-no-title.php' ) || 'page-no-title' === $page_template;
	$is_legal_page     = is_page( array( 'impressum', 'datenschutzerklaerung' ) );
	$page_header_class = $is_legal_page ? ' dj-page-header--legal' : '';
	$article_class     = 'dj-reading dj-page' . ( $is_legal_page ? ' dj-legal-template' : '' );
	if ( ! $has_content_title ) :
?>
<header class="dj-page-header<?php echo esc_attr( $page_header_class ); ?>"><div class="dj-width"><?php if ( $is_legal_page ) : ?><p class="dj-eyebrow"><?php esc_html_e( 'Rechtliche Informationen', 'dylan-journal' ); ?></p><?php endif; ?><h1 class="dj-page-title"><?php the_title(); ?></h1></div></header>
<?php endif; ?>
<article <?php post_class( $article_class ); ?>><div class="dj-prose"><?php the_content(); ?></div></article>
<?php endwhile; get_footer(); ?>
