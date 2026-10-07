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
		<?php $thumbnail_caption = get_the_post_thumbnail_caption(); ?>
		<?php $thumbnail_is_ai = function_exists( 'dylan_journal_is_ai_image' ) && dylan_journal_is_ai_image( get_post_thumbnail_id(), $thumbnail_caption ); ?>
		<figure class="dj-article-image<?php echo $thumbnail_is_ai ? ' dj-media-frame--ai' : ''; ?>">
			<div class="dj-media-frame__visual">
				<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '(max-width: 768px) calc(100vw - 40px), 736px' ) ); ?>
				<?php if ( $thumbnail_is_ai ) : ?><?php echo wp_kses( dylan_journal_ai_badge(), array( 'span' => array( 'class' => true, 'aria-label' => true, 'aria-hidden' => true ) ) ); ?><?php endif; ?>
			</div>
			<?php if ( ! $thumbnail_is_ai && $thumbnail_caption ) : ?><figcaption><?php echo wp_kses_post( $thumbnail_caption ); ?></figcaption><?php endif; ?>
		</figure>
	<?php endif; ?>
	<div class="dj-reading dj-prose">
		<?php the_content(); ?>
		<?php wp_link_pages( array( 'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Beitragsseiten', 'dylan-journal' ) . '">', 'after' => '</nav>' ) ); ?>
		<a class="dj-back" href="<?php echo esc_url( dylan_journal_page_url( 'blog', '/blog/' ) ); ?>">← <?php esc_html_e( 'Zurück zum Blog', 'dylan-journal' ); ?></a>
	</div>
	<?php if ( $related_posts->have_posts() ) : ?>
		<section class="dj-reading dj-related" aria-labelledby="more-notes-title">
			<h2 id="more-notes-title" class="dj-related__title"><?php esc_html_e( 'Noch mehr aus dem Blog', 'dylan-journal' ); ?></h2>
			<div class="dj-related__list">
				<?php while ( $related_posts->have_posts() ) : $related_posts->the_post(); ?>
					<?php
					$related_url       = get_permalink();
					$related_thumbnail = get_post_thumbnail_id();
					$related_caption   = get_the_post_thumbnail_caption();
					$related_is_ai     = function_exists( 'dylan_journal_is_ai_image' ) && dylan_journal_is_ai_image( $related_thumbnail, $related_caption );
					?>
					<article class="dj-related__item">
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="dj-related__media<?php echo $related_is_ai ? ' dj-media-frame--ai' : ''; ?>" href="<?php echo esc_url( $related_url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Beitrag lesen: %s', 'dylan-journal' ), get_the_title() ) ); ?>">
							<span class="dj-media-frame__visual">
								<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 760px) calc(100vw - 40px), 360px' ) ); ?>
								<?php if ( $related_is_ai ) : ?><?php echo wp_kses( dylan_journal_ai_badge(), array( 'span' => array( 'class' => true, 'aria-label' => true, 'aria-hidden' => true ) ) ); ?><?php endif; ?>
							</span>
						</a>
					<?php endif; ?>
						<div class="dj-related__body">
							<p class="dj-related__meta"><?php echo wp_kses_post( dylan_journal_primary_category_link() ); ?><span aria-hidden="true"> · </span><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd. M Y' ) ); ?></time></p>
							<h3><a href="<?php echo esc_url( $related_url ); ?>"><?php the_title(); ?></a></h3>
							<p class="dj-related__excerpt"><?php echo esc_html( dylan_journal_excerpt() ); ?></p>
							<a class="dj-related__read" href="<?php echo esc_url( $related_url ); ?>"><?php esc_html_e( 'Beitrag lesen', 'dylan-journal' ); ?> <span aria-hidden="true">→</span></a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
		</section>
	<?php endif; ?>
	<?php if ( comments_open() || get_comments_number() ) : ?>
		<?php comments_template(); ?>
	<?php endif; ?>
</article>
<?php
wp_reset_postdata();
endwhile;
get_footer();
