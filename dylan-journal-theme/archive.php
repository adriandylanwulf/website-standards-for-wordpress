<?php
/** @package Dylan_Journal */
get_header();
?>
<section class="dj-width dj-archive">
	<header class="dj-archive-head">
		<div><p class="dj-eyebrow"><?php esc_html_e( 'Archiv', 'dylan-journal' ); ?></p><h1 class="dj-page-title"><?php the_archive_title(); ?></h1></div>
		<?php the_archive_description( '<div class="dj-archive-description">', '</div>' ); ?>
	</header>
	<div class="dj-note-list">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); get_template_part( 'template-parts/note' ); endwhile; else : ?>
			<p><?php esc_html_e( 'Hier gibt es noch keine Beiträge.', 'dylan-journal' ); ?></p>
		<?php endif; ?>
	</div>
	<?php the_posts_pagination( array( 'prev_text' => '← Zurück', 'next_text' => 'Weiter →' ) ); ?>
</section>
<?php get_footer(); ?>
