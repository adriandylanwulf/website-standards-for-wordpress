<?php
/**
 * Template Name: Online & Kontakt
 * Template Post Type: page
 *
 * A quiet, editable home for public profiles and future account links.
 * The links themselves live in the page content so they can be extended in
 * WordPress without another footer or theme change.
 *
 * @package Dylan_Journal
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<header class="dj-page-header dj-page-header--online">
		<div class="dj-width">
			<p class="dj-eyebrow"><?php esc_html_e( 'Öffentliche Profile', 'dylan-journal' ); ?></p>
			<h1 id="online-title" class="dj-page-title"><?php the_title(); ?></h1>
		</div>
	</header>
	<article id="post-<?php the_ID(); ?>" aria-labelledby="online-title" <?php post_class( 'dj-reading dj-page dj-online-page' ); ?>>
		<div class="dj-prose">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
