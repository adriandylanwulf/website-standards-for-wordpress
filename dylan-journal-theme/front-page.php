<?php
/**
 * Front page for Dylan Journal.
 *
 * @package Dylan_Journal
 */

get_header();

$blog_url    = dylan_journal_page_url( 'blog', '/blog/' );
$photos_url  = dylan_journal_page_url( 'fotos', '/fotos/' );
$about_url   = dylan_journal_page_url( 'ueber-mich', '/ueber-mich/' );
$contact_url = dylan_journal_page_url( 'kontaktformular', '/kontaktformular/' );
$photos_page = get_page_by_path( 'fotos' );
$photo_items = $photos_page ? get_children(
	array(
		'post_parent'    => $photos_page->ID,
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'orderby'        => 'menu_order ID',
		'order'          => 'ASC',
	)
) : array();
$home_photo = $photo_items ? reset( $photo_items ) : null;
$home_is_ai  = $home_photo && function_exists( 'dylan_journal_is_ai_image' ) ? dylan_journal_is_ai_image( $home_photo->ID ) : false;
$home_image  = $home_photo ? wp_get_attachment_image_src( $home_photo->ID, 'large' ) : false;
$upload_dir  = wp_upload_dir();
$home_webp   = $home_image ? preg_replace( '/\.(jpe?g|png)$/i', '.webp', $home_image[0] ) : '';
$home_webp_path = $home_webp ? str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $home_webp ) : '';
$home_webp_small = $home_webp ? preg_replace( '/-768x1024\.webp$/i', '-512x683.webp', $home_webp ) : '';
$home_webp_small_path = $home_webp_small ? str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $home_webp_small ) : '';
$home_webp_srcset = array();
$home_image_sizes = '(max-width: 720px) calc(100vw - 2.25rem), 510px';
if ( $home_webp_small && $home_webp_small_path && file_exists( $home_webp_small_path ) ) {
	$home_webp_srcset[] = esc_url( $home_webp_small ) . ' 512w';
}
if ( $home_webp && $home_webp_path && file_exists( $home_webp_path ) ) {
	$home_webp_srcset[] = esc_url( $home_webp ) . ' 768w';
}
$notes_query = new WP_Query(
	array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => 3,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	)
);
?>
<section class="dj-home-stage" aria-labelledby="home-intro-title">
	<div class="dj-width dj-home-intro">
		<div class="dj-home-intro__copy">
			<p class="dj-home-intro__hello"><?php esc_html_e( 'Hallo, ich bin Adrian.', 'dylan-journal' ); ?></p>
			<h1 id="home-intro-title"><?php esc_html_e( 'Ich schreibe auf, was mich beschäftigt.', 'dylan-journal' ); ?></h1>
			<p class="dj-intro__copy"><?php esc_html_e( 'Auf dieser Seite sammle ich Notizen aus dem Alltag, Fotos von unterwegs und Dinge, die ich selbst ausprobiert habe.', 'dylan-journal' ); ?></p>
			<nav class="dj-home-intro__links" aria-label="<?php esc_attr_e( 'Einstieg', 'dylan-journal' ); ?>">
				<a class="dj-quiet-link" href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( 'Über mich', 'dylan-journal' ); ?></a>
				<a class="dj-quiet-link dj-quiet-link--subtle" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Blog lesen', 'dylan-journal' ); ?></a>
			</nav>
		</div>
		<?php if ( $home_photo ) : ?>
			<figure class="dj-home-intro__image<?php echo $home_is_ai ? ' dj-media-frame--ai' : ''; ?>">
				<a class="dj-media-frame__visual" href="<?php echo esc_url( $photos_url ); ?>" aria-label="<?php esc_attr_e( 'Zur Fotoseite', 'dylan-journal' ); ?>">
					<?php if ( $home_webp && $home_webp_path && file_exists( $home_webp_path ) ) : ?>
						<picture>
							<source type="image/webp" srcset="<?php echo esc_attr( implode( ', ', $home_webp_srcset ) ); ?>" sizes="<?php echo esc_attr( $home_image_sizes ); ?>">
							<?php echo wp_get_attachment_image( $home_photo->ID, 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => $home_image_sizes ) ); ?>
						</picture>
					<?php else : ?>
						<?php echo wp_get_attachment_image( $home_photo->ID, 'large', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => $home_image_sizes ) ); ?>
					<?php endif; ?>
					<?php if ( $home_is_ai ) : ?><?php echo wp_kses( dylan_journal_ai_badge(), array( 'span' => array( 'class' => true, 'aria-label' => true, 'aria-hidden' => true ) ) ); ?><?php endif; ?>
				</a>
				<figcaption><?php esc_html_e( 'Aus meiner Fotosammlung', 'dylan-journal' ); ?> <a href="<?php echo esc_url( $photos_url ); ?>"><?php esc_html_e( 'Alle Fotos', 'dylan-journal' ); ?></a></figcaption>
			</figure>
		<?php endif; ?>
	</div>
	<div class="dj-width dj-home-intro__meta">
		<span><?php esc_html_e( 'Notizen, Bilder und Erfahrungen', 'dylan-journal' ); ?></span>
		<span><?php esc_html_e( 'Zuletzt aktualisiert', 'dylan-journal' ); ?>: <?php echo esc_html( wp_date( 'd.m.Y' ) ); ?></span>
	</div>
</section>

<section class="dj-section dj-section--journal" aria-labelledby="latest-notes-title">
	<div class="dj-width">
		<div class="dj-section__head dj-section__head--v2">
			<div>
				<p class="dj-eyebrow"><?php esc_html_e( 'Aus dem Blog', 'dylan-journal' ); ?></p>
				<h2 id="latest-notes-title" class="dj-section-title"><?php esc_html_e( 'Neue Beiträge', 'dylan-journal' ); ?></h2>
			</div>
			<p class="dj-section__hint"><?php esc_html_e( 'Technik, Sicherheit, Hosting und Beobachtungen aus meinem Alltag.', 'dylan-journal' ); ?></p>
		</div>

		<?php if ( $notes_query->have_posts() ) : ?>
			<?php $notes_query->the_post(); ?>
			<article class="dj-featured-note">
				<div class="dj-featured-note__body">
					<p class="dj-featured-note__meta"><?php echo wp_kses_post( dylan_journal_primary_category_link() ); ?><span aria-hidden="true"> · </span><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd. M Y' ) ); ?></time></p>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php echo esc_html( dylan_journal_excerpt() ); ?></p>
				<a class="dj-quiet-link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Beitrag lesen', 'dylan-journal' ); ?></a>
				</div>
				<?php if ( has_post_thumbnail() ) : ?>
					<?php
					$featured_image_id = get_post_thumbnail_id();
					$featured_caption  = get_the_post_thumbnail_caption();
					$featured_is_ai    = function_exists( 'dylan_journal_is_ai_image' ) && dylan_journal_is_ai_image( $featured_image_id, $featured_caption );
					?>
					<figure class="dj-featured-note__image<?php echo $featured_is_ai ? ' dj-media-frame--ai' : ''; ?>">
						<span class="dj-media-frame__visual">
							<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 720px) calc(100vw - 88px), 440px' ) ); ?>
							<?php if ( $featured_is_ai ) : ?><?php echo wp_kses( dylan_journal_ai_badge(), array( 'span' => array( 'class' => true, 'aria-label' => true, 'aria-hidden' => true ) ) ); ?><?php endif; ?>
						</span>
					</figure>
				<?php endif; ?>
			</article>

			<?php if ( $notes_query->have_posts() ) : ?>
				<div class="dj-note-list dj-note-list--secondary">
					<?php while ( $notes_query->have_posts() ) : $notes_query->the_post(); ?>
						<article class="dj-note">
							<time class="dj-note__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd. M Y' ) ); ?></time>
							<div class="dj-note__content">
								<p class="dj-note__category"><?php echo wp_kses_post( dylan_journal_primary_category_link() ); ?></p>
								<h3 class="dj-note-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<p class="dj-note__excerpt"><?php echo esc_html( dylan_journal_excerpt() ); ?></p>
							</div>
							<a class="dj-note__arrow" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( '%s lesen', 'dylan-journal' ), get_the_title() ) ); ?>">→</a>
						</article>
					<?php endwhile; ?>
				</div>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<p class="dj-empty-note"><?php esc_html_e( 'Der nächste Eintrag ist gerade in Arbeit.', 'dylan-journal' ); ?></p>
		<?php endif; ?>

		<a class="dj-quiet-link" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Zum Blog', 'dylan-journal' ); ?></a>
	</div>
</section>

<section class="dj-section dj-section--soft" aria-labelledby="explore-title">
	<div class="dj-width dj-explore">
		<div>
			<p class="dj-eyebrow"><?php esc_html_e( 'Weiterstöbern', 'dylan-journal' ); ?></p>
			<h2 id="explore-title" class="dj-section-title"><?php esc_html_e( 'Worauf hast du Lust?', 'dylan-journal' ); ?></h2>
			<p class="dj-explore__intro"><?php esc_html_e( 'Im Blog stehen meine Notizen, in den Fotos bleiben Wege und Augenblicke hängen. Wenn du mir direkt etwas sagen möchtest, findest du dort auch das Kontaktformular.', 'dylan-journal' ); ?></p>
		</div>
		<div class="dj-explore__links">
			<a href="<?php echo esc_url( $blog_url ); ?>"><span><?php esc_html_e( 'Blog', 'dylan-journal' ); ?></span><strong><?php esc_html_e( 'Was ich ausprobiere', 'dylan-journal' ); ?></strong><small><?php esc_html_e( 'Webhosting, E-Mail, Sicherheit und andere Dinge, die ich nicht nur im Kopf behalten möchte.', 'dylan-journal' ); ?></small></a>
			<a href="<?php echo esc_url( $photos_url ); ?>"><span><?php esc_html_e( 'Fotos', 'dylan-journal' ); ?></span><strong><?php esc_html_e( 'Was unterwegs hängen bleibt', 'dylan-journal' ); ?></strong><small><?php esc_html_e( 'Eigene Bilder und kleine Ausschnitte von den Wegen dazwischen.', 'dylan-journal' ); ?></small></a>
			<a href="<?php echo esc_url( $contact_url ); ?>"><span><?php esc_html_e( 'Kontakt', 'dylan-journal' ); ?></span><strong><?php esc_html_e( 'Wenn du mir schreiben möchtest', 'dylan-journal' ); ?></strong><small><?php esc_html_e( 'Fragen, Hinweise oder eine Rückmeldung zu einem Beitrag sind willkommen.', 'dylan-journal' ); ?></small></a>
		</div>
	</div>
</section>

<section class="dj-section dj-section--contact" aria-labelledby="contact-title">
	<div class="dj-width dj-home-footer">
		<div>
			<h2 id="contact-title" class="dj-section-title"><?php esc_html_e( 'Wenn du mir schreiben möchtest', 'dylan-journal' ); ?></h2>
		</div>
		<div>
			<p><?php esc_html_e( 'Wenn du etwas zu einem Beitrag sagen möchtest oder eine Frage hast, schreib mir über das Formular.', 'dylan-journal' ); ?></p>
			<a class="dj-quiet-link" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Zum Kontaktformular', 'dylan-journal' ); ?></a>
		</div>
	</div>
</section>
<?php get_footer(); ?>
