<?php
/**
 * Personal About page.
 *
 * @package Dylan_Journal
 */

get_header();

$about_images = get_children(
	array(
		'post_parent'    => get_the_ID(),
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'orderby'        => 'menu_order ID',
		'order'          => 'ASC',
		'numberposts'    => -1,
	)
);
$portrait = $about_images ? reset( $about_images ) : false;
$photo_page = get_page_by_path( 'fotos' );
$photo_items = $photo_page ? get_children(
	array(
		'post_parent'    => $photo_page->ID,
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'orderby'        => 'menu_order ID',
		'order'          => 'ASC',
		'numberposts'    => -1,
	)
) : array();

if ( count( $photo_items ) > 4 ) {
	$curated_photo_items = array_values(
		array_filter(
			$photo_items,
			static function ( $photo ) {
				return ! in_array( $photo->post_title, array( 'Unterwegs', 'Unterwegs mit Kamera' ), true );
			}
		)
	);
	$photo_items = count( $curated_photo_items ) > 4
		? array_merge( array_slice( $curated_photo_items, 0, 3 ), array_slice( $curated_photo_items, -1 ) )
		: $curated_photo_items;
}
?>
<div class="dj-about-page">
	<div class="dj-width">
		<section class="dj-about-hero" aria-labelledby="about-title">
			<div class="dj-about-hero__copy">
				<p class="dj-eyebrow"><?php esc_html_e( 'Über mich', 'dylan-journal' ); ?></p>
				<h1 id="about-title"><?php esc_html_e( 'Adrian Dylan Wulf', 'dylan-journal' ); ?></h1>
				<p class="dj-about-hero__lede"><?php esc_html_e( 'Ich schreibe hier über Technik, unterwegs gemachte Bilder und die Dinge, die im Alltag hängen bleiben.', 'dylan-journal' ); ?></p>
			</div>
			<?php if ( $portrait ) : ?>
				<figure class="dj-about-hero__image">
					<?php echo wp_get_attachment_image( $portrait->ID, 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => get_post_meta( $portrait->ID, '_wp_attachment_image_alt', true ) ) ); ?>
					<?php if ( $portrait->post_excerpt ) : ?><figcaption><?php echo esc_html( $portrait->post_excerpt ); ?></figcaption><?php endif; ?>
				</figure>
			<?php endif; ?>
		</section>

		<section class="dj-about-body" aria-label="<?php esc_attr_e( 'Über Adrian Dylan Wulf', 'dylan-journal' ); ?>">
			<div class="dj-prose">
				<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
			</div>
			<aside class="dj-about-body__aside">
				<h2><?php esc_html_e( 'Kurz gesagt', 'dylan-journal' ); ?></h2>
				<p><?php esc_html_e( 'Technik · Fotos · persönliche Notizen', 'dylan-journal' ); ?></p>
				<p><?php esc_html_e( 'Ich mag Dinge, die verständlich bleiben und zuverlässig funktionieren – bei einer Website genauso wie unterwegs.', 'dylan-journal' ); ?></p>
			</aside>
		</section>

		<?php if ( $photo_items && $photo_page && 'publish' === $photo_page->post_status ) : ?>
			<section class="dj-about-gallery" aria-labelledby="about-gallery-title">
				<header class="dj-about-gallery__head">
					<div>
						<p class="dj-eyebrow"><?php esc_html_e( 'Unterwegs', 'dylan-journal' ); ?></p>
						<h2 id="about-gallery-title"><?php esc_html_e( 'Ein paar Ausschnitte', 'dylan-journal' ); ?></h2>
					</div>
					<a class="dj-about-gallery__link" href="<?php echo esc_url( get_permalink( $photo_page ) ); ?>"><?php esc_html_e( 'Alle Fotos', 'dylan-journal' ); ?> <span aria-hidden="true">↗</span></a>
				</header>
				<div class="dj-about-gallery__grid">
					<?php foreach ( $photo_items as $photo ) : ?>
						<figure class="dj-about-gallery__item">
							<a href="<?php echo esc_url( wp_get_attachment_url( $photo->ID ) ); ?>">
								<?php echo wp_get_attachment_image( $photo->ID, 'medium_large', false, array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
							</a>
							<?php if ( $photo->post_title ) : ?><figcaption><?php echo esc_html( $photo->post_title ); ?></figcaption><?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</div>
</div>
<?php get_footer(); ?>
