<?php
/** @package Dylan_Journal */
get_header();

$blog_url = dylan_journal_page_url( 'blog', '/blog/' );
?>
<section class="dj-not-found" aria-labelledby="not-found-title">
	<div class="dj-width dj-not-found__inner">
		<p class="dj-eyebrow"><?php esc_html_e( 'Nicht gefunden', 'dylan-journal' ); ?></p>
		<h1 id="not-found-title" class="dj-page-title"><?php esc_html_e( 'Diese Seite gibt es nicht.', 'dylan-journal' ); ?></h1>
		<p class="dj-page-summary"><?php esc_html_e( 'Die Adresse ist nicht bekannt. Über die Startseite oder den Blog geht es weiter.', 'dylan-journal' ); ?></p>
		<div class="dj-not-found__links">
			<a class="dj-quiet-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Zur Startseite', 'dylan-journal' ); ?></a>
			<a class="dj-quiet-link" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Zum Blog', 'dylan-journal' ); ?></a>
		</div>
		<?php get_search_form(); ?>
	</div>
</section>
<?php get_footer(); ?>
