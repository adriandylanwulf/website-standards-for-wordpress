<?php
/**
 * Bootstrap for Dylan Journal.
 *
 * The theme deliberately keeps its public behaviour in small, focused files:
 * setup/navigation, front-end performance, form-asset loading and SEO.
 *
 * @package Dylan_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/theme-setup.php';
require_once get_template_directory() . '/inc/contact-form-assets.php';
require_once get_template_directory() . '/inc/performance.php';
require_once get_template_directory() . '/inc/seo.php';
