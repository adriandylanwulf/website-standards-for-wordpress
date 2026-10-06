<?php
/** @package Dylan_Journal */
get_header();
?>
<section class="dj-width dj-archive">
	<header class="dj-archive-head">
		<div><p class="dj-eyebrow"><?php esc_html_e( 'Aufgeschrieben', 'dylan-journal' ); ?></p><h1 class="dj-page-title"><?php esc_html_e( 'Blog', 'dylan-journal' ); ?></h1></div>
		<p><?php esc_html_e( 'Notizen zu Technik, Webhosting, digitaler Sicherheit und dem Alltag.', 'dylan-journal' ); ?></p>
	</header>
	<div class="dj-note-list">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); get_template_part( 'template-parts/note' ); endwhile; else : ?>
			<p><?php esc_html_e( 'Hier erscheint bald der erste Beitrag.', 'dylan-journal' ); ?></p>
		<?php endif; ?>
	</div>
	<?php the_posts_pagination( array( 'prev_text' => '← Zurück', 'next_text' => 'Weiter →' ) ); ?>
</section>
<?php get_footer(); ?>
