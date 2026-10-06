<?php
/** Native WordPress comments template. @package Dylan_Journal */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="dj-comments dj-reading" aria-labelledby="comments-title">
	<?php if ( have_comments() ) : ?>
		<h2 id="comments-title" class="comments-title">
			<?php
			printf(
				esc_html( _nx( '%1$s Kommentar', '%1$s Kommentare', get_comments_number(), 'comments title', 'dylan-journal' ) ),
				number_format_i18n( get_comments_number() )
			);
			?>
		</h2>
		<ol class="comment-list">
			<?php wp_list_comments(); ?>
		</ol>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'          => __( 'Kommentar hinterlassen', 'dylan-journal' ),
			'label_submit'         => __( 'Kommentar senden', 'dylan-journal' ),
			'comment_notes_before' => '<p class="comment-notes">Deine E-Mail-Adresse wird nicht veröffentlicht. Pflichtfelder sind mit * gekennzeichnet.</p>',
			'comment_notes_after'  => '<p class="privacy-notice">Mit dem Absenden werden deine Angaben zur Bearbeitung des Kommentars verarbeitet. Weitere Informationen findest du in der <a href="' . esc_url( dylan_journal_page_url( 'datenschutzerklaerung', '/datenschutzerklaerung/' ) ) . '">Datenschutzerklärung</a>.</p>',
			'comment_field'        => '<p class="comment-form-comment"><label for="comment">Kommentar <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>',
		)
	);
	?>
</section>
