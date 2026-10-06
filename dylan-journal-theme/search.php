<?php
/** @package Dylan_Journal */
get_header();
?>
<section class="dj-width dj-archive">
	<header class="dj-archive-head"><div><p class="dj-eyebrow"><?php esc_html_e( 'Suche', 'dylan-journal' ); ?></p><h1 class="dj-page-title"><?php printf( esc_html__( 'Ergebnisse für „%s“', 'dylan-journal' ), esc_html( get_search_query() ) ); ?></h1></div></header>
	<?php get_search_form(); ?>
	<div class="dj-note-list">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); get_template_part( 'template-parts/note' ); endwhile; else : ?>
			<p><?php esc_html_e( 'Dazu habe ich noch nichts veröffentlicht. Versuche einen anderen Suchbegriff oder schau bei den Beiträgen vorbei.', 'dylan-journal' ); ?></p>
		<?php endif; ?>
	</div>
	<?php the_posts_pagination(); ?>
</section>
<?php get_footer(); ?>
