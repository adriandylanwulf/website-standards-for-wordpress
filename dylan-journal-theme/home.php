<?php
/** @package Dylan_Journal */
get_header();
?>
<section class="dj-width dj-archive">
	<header class="dj-archive-head">
		<div><h1 class="dj-page-title"><?php esc_html_e( 'Blog', 'dylan-journal' ); ?></h1></div>
		<p><?php esc_html_e( 'Notizen zu Technik, Webhosting, digitaler Sicherheit und Dingen aus dem Alltag.', 'dylan-journal' ); ?></p>
	</header>
	<?php
	$blog_page_id = (int) get_option( 'page_for_posts' );
	$blog_url      = $blog_page_id ? get_permalink( $blog_page_id ) : home_url( '/blog/' );
	$topics        = get_categories(
		array(
			'hide_empty' => true,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	if ( $topics ) :
		?>
		<nav class="dj-topic-nav" aria-label="<?php esc_attr_e( 'Blog-Themen', 'dylan-journal' ); ?>">
			<span class="dj-topic-nav__label"><?php esc_html_e( 'Themen', 'dylan-journal' ); ?></span>
			<div class="dj-topic-nav__links">
				<a href="<?php echo esc_url( $blog_url ); ?>" aria-current="page"><?php esc_html_e( 'Alle Beiträge', 'dylan-journal' ); ?></a>
				<?php foreach ( $topics as $topic ) : ?>
					<a href="<?php echo esc_url( get_category_link( $topic->term_id ) ); ?>"><?php echo esc_html( $topic->name ); ?></a>
				<?php endforeach; ?>
			</div>
		</nav>
	<?php endif; ?>
	<div class="dj-note-list">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); get_template_part( 'template-parts/note' ); endwhile; else : ?>
			<p><?php esc_html_e( 'Hier erscheint bald der erste Beitrag.', 'dylan-journal' ); ?></p>
		<?php endif; ?>
	</div>
	<?php the_posts_pagination( array( 'prev_text' => '← Zurück', 'next_text' => 'Weiter →' ) ); ?>
</section>
<?php get_footer(); ?>
