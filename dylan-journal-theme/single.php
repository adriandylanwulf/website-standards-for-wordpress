<?php
/** @package Dylan_Journal */
get_header();
while ( have_posts() ) : the_post();

$post_id    = get_the_ID();
$categories = get_the_category( $post_id );
$query_args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 2,
	'post__not_in'        => array( $post_id ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);

if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
	$query_args['category__in'] = wp_list_pluck( $categories, 'term_id' );
}

$related_posts = new WP_Query( $query_args );
?>
<article <?php post_class( 'dj-width dj-article' ); ?>>
	<header class="dj-article-head">
		<nav class="dj-breadcrumb" aria-label="<?php esc_attr_e( 'Seitenpfad', 'dylan-journal' ); ?>"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Startseite', 'dylan-journal' ); ?></a><span aria-hidden="true">/</span><a href="<?php echo esc_url( dylan_journal_page_url( 'blog', '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'dylan-journal' ); ?></a></nav>
		<p class="dj-article-meta">
			<?php echo wp_kses_post( dylan_journal_primary_category_link( $post_id ) ); ?>
			<span aria-hidden="true"> · </span>
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd. M Y' ) ); ?></time>
			<span aria-hidden="true"> · </span>
			<span><?php echo esc_html( dylan_journal_reading_time( $post_id ) ); ?></span>
		</p>
		<h1 class="dj-article-title"><?php the_title(); ?></h1>
		<p class="dj-byline"><?php esc_html_e( 'Von', 'dylan-journal' ); ?> <a rel="author" href="<?php echo esc_url( dylan_journal_page_url( 'ueber-mich', '/ueber-mich/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a></p>
	</header>
	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="dj-article-image">
				<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 768px) calc(100vw - 40px), 736px' ) ); ?>
			<?php if ( get_the_post_thumbnail_caption() ) : ?><figcaption><?php echo wp_kses_post( get_the_post_thumbnail_caption() ); ?></figcaption><?php endif; ?>
		</figure>
	<?php endif; ?>
	<div class="dj-reading dj-prose">
		<?php the_content(); ?>
		<?php wp_link_pages( array( 'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Beitragsseiten', 'dylan-journal' ) . '">', 'after' => '</nav>' ) ); ?>
		<a class="dj-back" href="<?php echo esc_url( dylan_journal_page_url( 'blog', '/blog/' ) ); ?>">← <?php esc_html_e( 'Zurück zum Blog', 'dylan-journal' ); ?></a>
	</div>
	<?php if ( comments_open() || get_comments_number() ) : ?>
		<?php comments_template(); ?>
	<?php endif; ?>
	<?php if ( $related_posts->have_posts() ) : ?>
		<section class="dj-reading dj-related" aria-labelledby="more-notes-title">
			<p class="dj-eyebrow"><?php esc_html_e( 'Weiterlesen', 'dylan-journal' ); ?></p>
			<h2 id="more-notes-title" class="dj-related__title"><?php esc_html_e( 'Weitere Beiträge', 'dylan-journal' ); ?></h2>
			<div class="dj-related__list">
				<?php while ( $related_posts->have_posts() ) : $related_posts->the_post(); ?>
					<article class="dj-related__item">
						<p class="dj-related__meta"><?php echo wp_kses_post( dylan_journal_primary_category_link() ); ?></p>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( dylan_journal_excerpt() ); ?></p>
					</article>
				<?php endwhile; ?>
			</div>
		</section>
	<?php endif; ?>
</article>
<?php
wp_reset_postdata();
endwhile;
get_footer();
