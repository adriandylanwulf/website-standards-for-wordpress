<?php
/**
 * Site footer.
 *
 * @package Dylan_Journal
 */

$imprint_page = get_page_by_path( 'impressum' );
$about_page   = get_page_by_path( 'ueber-mich' );
$blog_page    = get_page_by_path( 'blog' );
$contact_page = get_page_by_path( 'kontaktformular' );
$online_page  = get_page_by_path( 'online' );
$photos_page  = get_page_by_path( 'fotos' );
$privacy_url  = get_privacy_policy_url();

if ( ! $privacy_url ) {
	$privacy_page = get_page_by_path( 'datenschutzerklaerung' );
	$privacy_url  = $privacy_page && 'publish' === $privacy_page->post_status ? get_permalink( $privacy_page ) : '';
}
?>
</main>
<footer class="dj-footer">
	<div class="dj-width dj-footer__inner">
		<div class="dj-footer__masthead">
			<div>
				<a class="dj-footer__signature" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="dj-footer__name"><?php bloginfo( 'name' ); ?></span>
				</a>
				<p class="dj-footer__intro"><?php esc_html_e( 'Notizen, Fotos und Dinge, die unterwegs hängen bleiben.', 'dylan-journal' ); ?></p>
			</div>
			<div class="dj-footer__actions">
				<?php if ( $photos_page && 'publish' === $photos_page->post_status ) : ?>
					<a class="dj-footer__contact" href="<?php echo esc_url( get_permalink( $photos_page ) ); ?>"><?php esc_html_e( 'Zu den Fotos', 'dylan-journal' ); ?> <span aria-hidden="true">↗</span></a>
				<?php endif; ?>
				<?php if ( $contact_page && 'publish' === $contact_page->post_status ) : ?>
					<a class="dj-footer__contact" href="<?php echo esc_url( get_permalink( $contact_page ) ); ?>"><?php esc_html_e( 'Schreib mir', 'dylan-journal' ); ?> <span aria-hidden="true">↗</span></a>
				<?php endif; ?>
			</div>
		</div>
		<div class="dj-footer__body">
			<nav class="dj-footer__navigation" aria-label="<?php esc_attr_e( 'Seiten-Navigation', 'dylan-journal' ); ?>">
				<p class="dj-footer__label"><?php esc_html_e( 'Auf dieser Website', 'dylan-journal' ); ?></p>
				<div class="dj-footer__links">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Startseite', 'dylan-journal' ); ?></a>
				<?php if ( $about_page && 'publish' === $about_page->post_status ) : ?>
					<a href="<?php echo esc_url( get_permalink( $about_page ) ); ?>"><?php esc_html_e( 'Über mich', 'dylan-journal' ); ?></a>
				<?php endif; ?>
				<?php if ( $blog_page && 'publish' === $blog_page->post_status ) : ?>
					<a href="<?php echo esc_url( get_permalink( $blog_page ) ); ?>"><?php esc_html_e( 'Blog', 'dylan-journal' ); ?></a>
				<?php endif; ?>
				<?php if ( $photos_page && 'publish' === $photos_page->post_status ) : ?>
					<a href="<?php echo esc_url( get_permalink( $photos_page ) ); ?>"><?php esc_html_e( 'Fotos', 'dylan-journal' ); ?></a>
				<?php endif; ?>
				<?php if ( $online_page && 'publish' === $online_page->post_status ) : ?>
					<a href="<?php echo esc_url( get_permalink( $online_page ) ); ?>"><?php esc_html_e( 'Online & Kontakt', 'dylan-journal' ); ?></a>
				<?php endif; ?>
				</div>
			</nav>
			<nav class="dj-footer__navigation dj-footer__navigation--legal" aria-label="<?php esc_attr_e( 'Rechtliche Links', 'dylan-journal' ); ?>">
				<p class="dj-footer__label"><?php esc_html_e( 'Rechtliche Hinweise', 'dylan-journal' ); ?></p>
				<div class="dj-footer__links">
				<?php if ( $imprint_page && 'publish' === $imprint_page->post_status ) : ?>
					<a href="<?php echo esc_url( get_permalink( $imprint_page ) ); ?>"><?php esc_html_e( 'Impressum', 'dylan-journal' ); ?></a>
				<?php endif; ?>
				<?php if ( $privacy_url ) : ?>
					<a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Datenschutz', 'dylan-journal' ); ?></a>
				<?php endif; ?>
				</div>
			</nav>
			</div>
		<div class="dj-footer__base">
			<p id="cookie-settings-status" class="dj-cookie-status" role="status"></p>
			<p class="dj-footer__copyright"><span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Adrian Dylan Wulf</span></p>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
