<?php
/** Shared entry for journal, archives and search. @package Dylan_Journal */
?>
<article <?php post_class( 'dj-note' ); ?>>
	<time class="dj-note__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd. M Y' ) ); ?></time>
	<div class="dj-note__content">
		<?php if ( 'post' === get_post_type() ) : ?><p class="dj-note__category"><?php echo wp_kses_post( dylan_journal_primary_category_link() ); ?></p><?php endif; ?>
		<h2 class="dj-note-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="dj-note__excerpt"><?php echo esc_html( dylan_journal_excerpt() ); ?></p>
	</div>
	<span class="dj-note__arrow" aria-hidden="true">→</span>
</article>
