<?php
/**
 * Site header.
 *
 * The responsive menu uses native <details>/<summary>; it remains keyboard
 * accessible and functional when JavaScript is unavailable.
 *
 * @package Dylan_Journal
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $cookiebot_id = (string) get_option( 'cookiebot-cbid', '' ); ?>
	<?php if ( '' !== $cookiebot_id ) : ?>
		<link rel="preconnect" href="https://consent.cookiebot.com" crossorigin>
		<link rel="preconnect" href="https://consentcdn.cookiebot.com" crossorigin>
		<script id="Cookiebot" src="https://consent.cookiebot.com/uc.js" data-cbid="<?php echo esc_attr( $cookiebot_id ); ?>" data-blockingmode="auto" data-widget-enabled="false" type="text/javascript"></script>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#content"><?php esc_html_e( 'Zum Inhalt springen', 'dylan-journal' ); ?></a>
	<header class="dj-header" id="site-header">
		<div class="dj-width dj-header__inner">
			<a class="dj-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="dj-brand__name"><?php bloginfo( 'name' ); ?></span>
				<span class="dj-brand__note"><?php esc_html_e( 'Persönliche Website', 'dylan-journal' ); ?></span>
			</a>
		<details class="dj-menu">
				<summary class="dj-menu__trigger" aria-controls="site-menu">
					<span class="dj-menu__label"><?php esc_html_e( 'Menü', 'dylan-journal' ); ?></span>
					<span class="dj-menu__icon" aria-hidden="true"></span>
			</summary>
			<nav id="site-menu" class="dj-nav" aria-label="<?php esc_attr_e( 'Hauptnavigation', 'dylan-journal' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'fallback_cb'    => 'dylan_journal_navigation_fallback',
						'depth'          => 1,
					)
				);
				?>
			</nav>
		</details>
	</div>
</header>
<main id="content" tabindex="-1">
