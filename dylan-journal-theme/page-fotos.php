<?php
/**
 * Personal photo journal page.
 *
 * @package Dylan_Journal
 */

get_header();

$photo_items = get_children(
	array(
		'post_parent'    => get_the_ID(),
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'orderby'        => 'menu_order ID',
		'order'          => 'ASC',
		'numberposts'    => -1,
	)
);
?>
<div class="dj-photos-page">
	<div class="dj-width">
		<header class="dj-photos-intro">
			<div>
				<p class="dj-eyebrow"><?php esc_html_e( 'Fotos', 'dylan-journal' ); ?></p>
				<h1><?php esc_html_e( 'Unterwegs festgehalten', 'dylan-journal' ); ?></h1>
			</div>
			<p><?php esc_html_e( 'Bilder von unterwegs, aus Städten und von kleinen Wegen, die mir im Kopf geblieben sind. Ich lasse die Motive möglichst für sich sprechen – ohne Filtershow und ohne große Erklärung.', 'dylan-journal' ); ?></p>
		</header>

		<?php if ( $photo_items ) : ?>
			<div class="dj-photo-grid">
				<?php $photo_index = 0; ?>
				<?php foreach ( $photo_items as $photo ) : ?>
					<figure class="dj-photo-card">
						<a href="<?php echo esc_url( wp_get_attachment_url( $photo->ID ) ); ?>">
							<?php
							$image_attributes = array(
								'loading'  => 0 === $photo_index ? 'eager' : 'lazy',
								'decoding' => 'async',
								'sizes'    => '(max-width: 420px) calc(100vw - 2.25rem), (max-width: 760px) calc(50vw - 1.2rem), (max-width: 1040px) 31vw, 24rem',
							);

							if ( 0 === $photo_index ) {
								$image_attributes['fetchpriority'] = 'high';
							}

							echo wp_get_attachment_image( $photo->ID, 'large', false, $image_attributes );
							?>
						</a>
						<?php if ( $photo->post_title ) : ?><figcaption class="dj-photo-card__title"><?php echo esc_html( $photo->post_title ); ?></figcaption><?php endif; ?>
						<?php if ( $photo->post_excerpt ) : ?><p class="dj-photo-card__note"><?php echo esc_html( $photo->post_excerpt ); ?></p><?php endif; ?>
					</figure>
					<?php $photo_index++; ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="dj-photo-empty"><?php esc_html_e( 'Die ersten Bilder kommen bald dazu.', 'dylan-journal' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php get_footer(); ?>
