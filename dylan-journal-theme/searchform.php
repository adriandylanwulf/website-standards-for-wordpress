<?php /** @package Dylan_Journal */ ?>
<form role="search" method="get" class="dj-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label><span class="screen-reader-text"><?php esc_html_e( 'Website durchsuchen', 'dylan-journal' ); ?></span><input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Suchbegriff eingeben', 'dylan-journal' ); ?>" required></label>
	<button type="submit"><?php esc_html_e( 'Suchen', 'dylan-journal' ); ?></button>
</form>
