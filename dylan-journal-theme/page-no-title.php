<?php
/**
 * Template Name: Seite ohne Titelzeile
 * Template Post Type: page
 *
 * The page content on this personal website supplies its own carefully styled
 * H1. Omitting the generic theme header avoids a duplicate main heading while
 * keeping the page readable and semantically clear.
 *
 * @package Dylan_Journal
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'dj-reading dj-page' ); ?>>
		<div class="dj-prose">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
