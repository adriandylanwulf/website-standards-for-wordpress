<?php
/**
 * Search presentation, structured data and sitemap hygiene.
 *
 * @package Dylan_Journal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the current portrait assigned to the About page.
 *
 * Keeping this lookup dynamic means replacing the portrait in the media
 * library also updates social previews without another hard-coded ID.
 *
 * @return int
 */
function dylan_journal_portrait_image_id() {
	static $portrait_id = null;

	if ( null !== $portrait_id ) {
		return $portrait_id;
	}

	$portrait_id = 0;
	$about_page  = get_page_by_path( 'ueber-mich' );

	if ( ! $about_page ) {
		return $portrait_id;
	}

	$about_images = get_children(
		array(
			'post_parent'    => $about_page->ID,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'orderby'        => 'menu_order ID',
			'order'          => 'ASC',
			'numberposts'    => 1,
		)
	);

	if ( $about_images ) {
		$portrait    = reset( $about_images );
		$portrait_id = absint( $portrait->ID );
	}

	return $portrait_id;
}

/**
 * Let a dedicated SEO plugin own metadata when one is active.
 *
 * @return bool
 */
function dylan_journal_has_seo_plugin() {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| function_exists( 'the_seo_framework' );
}

/**
 * Return a concise page-specific search description.
 *
 * Explicit WordPress excerpts always take precedence for individual posts,
 * keeping editorial control with the person publishing the article.
 *
 * @return string
 */
function dylan_journal_seo_description() {
	if ( is_singular() && post_password_required() ) {
		return __( 'Dieser Inhalt ist passwortgeschützt.', 'dylan-journal' );
	}

	if ( is_singular() && has_excerpt() ) {
		return wp_html_excerpt( wp_strip_all_tags( get_the_excerpt() ), 160, ' …' );
	}

	if ( is_front_page() ) {
		return 'Persönliche Notizen von Adrian Dylan Wulf zu Technik, Webhosting, digitaler Sicherheit und dem Alltag.';
	}

	if ( is_page( 'ueber-mich' ) ) {
		return 'Über Adrian Dylan Wulf: persönliche Notizen zu Technik, digitaler Sicherheit, unterwegs sein und den Dingen, die im Alltag hängen bleiben.';
	}

	if ( is_home() || is_page( 'blog' ) ) {
		return 'Notizen von Adrian Dylan Wulf zu Technik, Webhosting, digitaler Sicherheit und dem Alltag.';
	}

	if ( is_privacy_policy() || is_page( 'datenschutzerklaerung' ) ) {
		return 'Datenschutzerklärung von Adrian Dylan Wulf: Informationen zur Verarbeitung personenbezogener Daten, zu eingesetzten Diensten und deinen Rechten.';
	}

	if ( is_page( 'impressum' ) ) {
		return 'Impressum der persönlichen Website von Adrian Dylan Wulf mit Angaben zum Verantwortlichen und Möglichkeiten zur Kontaktaufnahme.';
	}

	if ( is_page( 'fotos' ) ) {
		return 'Fotos von Adrian Dylan Wulf: unterwegs, draußen und kleine Beobachtungen am Wegesrand.';
	}

	if ( is_page( 'kontaktformular' ) ) {
		return 'Kontakt zu Adrian Dylan Wulf für Fragen zu Beiträgen oder um einen Gedanken zu teilen.';
	}

	if ( is_page( 'online' ) ) {
		return 'Öffentliche Profile und Links von Adrian Dylan Wulf: PayPal, Instagram, Facebook, Tellonym und weitere Online-Konten.';
	}

	if ( is_singular( 'post' ) ) {
		$descriptions = array(
			'meine-erfahrungen-mit-mittwald' => 'Meine persönlichen Erfahrungen mit Mittwald: schnelle Website, hilfreicher Support, eigene E-Mail-Adressen und tägliche Backups im mStudio.',
			'was-sich-auf-meiner-website-in-den-letzten-tagen-veraendert-hat' => 'Was sich auf meiner Website verändert hat: neue Strukturen, bessere Lesbarkeit und technische Pflege für Performance, SEO und Sicherheit.',
			'windows-11-26h2-was-ich-vom-neuen-update-erwarte' => 'Windows 11 26H2 im Check: Neuerungen, Sicherheitsupdates, CVE-Einordnung und bekannte Probleme – persönlich und verständlich erklärt.',
			'meine-website-optimierung-design-performance-seo-und-sicherheit' => 'Was ich an meiner Website geändert habe: bessere Lesbarkeit, weniger Ablenkung sowie technische Verbesserungen für Performance, SEO und Sicherheit.',
			'telekom-cloudflare-peering-im-traceroute-warum-1-1-1-1-nicht-wie-jede-cloudflare-ip-aussieht' => 'Telekom und Cloudflare: Ein Traceroute vom 1. Oktober 2026 zeigt unterschiedliche Wege zu 1.1.1.1, Cloudflare-IP-Adressen und Discord – mit Einordnung.',
			'ios-27-0-1-und-macos-golden-gate-27-0-1-was-die-updates-beheben' => 'iOS 27.0.1 und macOS Golden Gate 27.0.1: Welche Fehler die Updates beheben und warum die Installation zeitnah sinnvoll ist.',
			'ios-27-apple-schliesst-122-sicherheitsluecken-diese-schwachstellen-wurden-behoben' => 'iOS 27 schließt 122 Sicherheitslücken. Ein Überblick über die behobenen Schwachstellen, betroffene Geräte und wichtige Update-Hinweise.',
			'wordpress-7-1-2-kritisches-sicherheitsupdate-zeitnah-installieren' => 'WordPress 7.1.2 behebt eine kritisch eingestufte Sicherheitslücke. Das Update, die Voraussetzungen und eine kurze Checkliste für die Aktualisierung.',
			'wordpress-backups-wiederherstellung-testen' => 'WordPress-Backups prüfen: Was zur Sicherung gehört und wie du die Wiederherstellung von Dateien und Datenbank in einer getrennten Testumgebung testest.',
			'e-mail-header-lesen-was-sie-verraten-und-wann-der-blick-lohnt' => 'E-Mail-Header richtig einordnen: Welche Angaben bei verdächtigen Nachrichten hilfreich sind und warum der sichtbare Absender allein nicht genügt.',
			'e-mail-sicherheit-drei-gewohnheiten' => 'Drei alltagstaugliche Gewohnheiten für mehr E-Mail-Sicherheit: Links prüfen, einzigartige Passwörter verwenden und Zwei-Faktor-Authentifizierung aktivieren.',
			'woran-man-gutes-webhosting-erkennt' => 'Woran gutes Webhosting im Alltag zu erkennen ist: sichere Technik, zuverlässige Backups, klare Verwaltung und erreichbarer Support.',
			'willkommen-auf-meiner-website' => 'Willkommen auf der persönlichen Website von Adrian Dylan Wulf mit Notizen zu Technik, Webhosting, digitaler Sicherheit und Eindrücken von unterwegs.',
		);
		$slug         = get_post_field( 'post_name', get_the_ID() );

		if ( isset( $descriptions[ $slug ] ) ) {
			return $descriptions[ $slug ];
		}

		return wp_html_excerpt( wp_strip_all_tags( get_the_excerpt() ), 160, ' …' );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$description = wp_strip_all_tags( term_description() );

		return $description
			? wp_html_excerpt( $description, 160, ' …' )
			: sprintf(
				__( 'Beiträge zum Thema %1$s auf der persönlichen Website von %2$s.', 'dylan-journal' ),
				single_term_title( '', false ),
				get_bloginfo( 'name' )
			);
	}

	return get_bloginfo( 'description' );
}

/**
 * Provide descriptive archive titles unless an SEO plugin is responsible.
 *
 * @param string $title WordPress document title.
 * @return string
 */
function dylan_journal_home_document_title( $title ) {
	if ( dylan_journal_has_seo_plugin() || ! is_front_page() ) {
		if ( dylan_journal_has_seo_plugin() ) {
			return $title;
		}

		if ( is_home() || is_page( 'blog' ) ) {
			return 'Blog: Technik & Sicherheit – Adrian Dylan Wulf';
		}

		if ( is_singular( 'post' ) ) {
			$post_titles = array(
				'willkommen-auf-meiner-website' => 'Willkommen auf meiner Website | Adrian Dylan Wulf',
				'woran-man-gutes-webhosting-erkennt' => 'Gutes Webhosting: Worauf ich achte | Adrian Dylan Wulf',
				'e-mail-sicherheit-drei-gewohnheiten' => 'E-Mail-Sicherheit: Drei Gewohnheiten | Adrian Dylan Wulf',
				'e-mail-header-lesen-was-sie-verraten-und-wann-der-blick-lohnt' => 'E-Mail-Header richtig lesen | Adrian Dylan Wulf',
				'wordpress-backups-wiederherstellung-testen' => 'WordPress-Backups: Wiederherstellung testen | Adrian Dylan Wulf',
				'passkeys-verstehen-mehr-sicherheit-ohne-passwortstress' => 'Passkeys: Anmelden ohne Passwortstress | Adrian Dylan Wulf',
				'wordpress-7-1-1-sicherheitsupdate-schliesst-11-schwachstellen' => 'WordPress 7.1.1: 11 Lücken geschlossen | Adrian Dylan Wulf',
				'ios-27-apple-schliesst-122-sicherheitsluecken-diese-schwachstellen-wurden-behoben' => 'iOS 27: 122 Sicherheitslücken geschlossen | Adrian Dylan Wulf',
				'wordpress-7-1-2-kritisches-sicherheitsupdate-zeitnah-installieren' => 'WordPress 7.1.2: Kritisches Sicherheitsupdate | Adrian Dylan Wulf',
				'ios-27-0-1-und-macos-golden-gate-27-0-1-was-die-updates-beheben' => 'iOS 27.0.1: Das ändert sich im Alltag | Adrian Dylan Wulf',
				'meine-website-optimierung-design-performance-seo-und-sicherheit' => 'Meine Website: Design, Performance und SEO | Adrian Dylan Wulf',
				'meine-erfahrungen-mit-mittwald' => 'Meine Erfahrungen mit Mittwald | Adrian Dylan Wulf',
				'windows-11-26h2-was-ich-vom-neuen-update-erwarte' => 'Windows 11 26H2: Neuerungen und Sicherheit | Adrian Dylan Wulf',
				'was-sich-auf-meiner-website-in-den-letzten-tagen-veraendert-hat' => 'Was sich auf meiner Website verändert hat | Adrian Dylan Wulf',
				'telekom-cloudflare-peering-im-traceroute-warum-1-1-1-1-nicht-wie-jede-cloudflare-ip-aussieht' => 'Telekom und Cloudflare: Mein Traceroute | Adrian Dylan Wulf',
			);
			$slug = get_post_field( 'post_name', get_queried_object_id() );

			if ( isset( $post_titles[ $slug ] ) ) {
				return $post_titles[ $slug ];
			}
		}

		if ( is_category() || is_tag() || is_tax() ) {
			return sprintf( '%s – Beiträge | Adrian Dylan Wulf', single_term_title( '', false ) );
		}

		return $title;
	}

	return 'Adrian Dylan Wulf – Persönliche Notizen';
}
add_filter( 'pre_get_document_title', 'dylan_journal_home_document_title' );

/**
 * Preserve search equity when the posts page moves from /journal/ to /blog/.
 *
 * @return void
 */
function dylan_journal_redirect_old_blog_urls() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path        = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );

	if ( 'tag/seo-2' === $path ) {
		wp_safe_redirect( trailingslashit( home_url( '/tag/seo' ) ), 301 );
		exit;
	}

	if ( 'journal' !== $path && 0 !== strpos( $path, 'journal/page/' ) ) {
		return;
	}

	$suffix = substr( $path, strlen( 'journal' ) );
	$target = trailingslashit( home_url( '/blog' . $suffix ) );
	$query  = wp_parse_url( $request_uri, PHP_URL_QUERY );

	if ( $query ) {
		$target .= '?' . $query;
	}

	wp_safe_redirect( $target, 301 );
	exit;
}
add_action( 'template_redirect', 'dylan_journal_redirect_old_blog_urls', 1 );

/**
 * Build a stable public URL for Open Graph and structured data.
 *
 * @return string
 */
function dylan_journal_seo_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_singular() ) {
		return (string) get_permalink();
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$term_url = get_term_link( get_queried_object() );
		if ( ! is_wp_error( $term_url ) ) {
			return $term_url;
		}
	}

	if ( is_home() && (int) get_option( 'page_for_posts' ) ) {
		return (string) get_permalink( (int) get_option( 'page_for_posts' ) );
	}

	global $wp;
	$request = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';

	return '' === $request ? home_url( '/' ) : home_url( '/' . user_trailingslashit( $request ) );
}

/**
 * Return a plain title for machine-readable metadata.
 *
 * WordPress may expose typographic entities through get_the_title(). Those
 * entities are correct for HTML but should be decoded before JSON-LD is built.
 *
 * @param int $post_id Optional post ID.
 * @return string
 */
function dylan_journal_schema_title( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	return wp_specialchars_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES );
}

/**
 * Add self-referencing canonicals to paginated post and taxonomy archives.
 * WordPress core emits canonicals for singular views, but not for these
 * archive types. A dedicated SEO plugin remains the owner when present.
 *
 * @return void
 */
function dylan_journal_archive_canonical() {
	if ( dylan_journal_has_seo_plugin() || is_admin() || is_feed() || is_404() || is_search() ) {
		return;
	}

	if ( ! is_home() && ! is_category() && ! is_tag() && ! is_tax() ) {
		return;
	}

	$url = get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) );

	if ( $url ) {
		printf( '<link rel="canonical" href="%s" />%s', esc_url( $url ), "\n" );
	}
}
add_action( 'wp_head', 'dylan_journal_archive_canonical', 1 );

/**
 * Add useful social metadata and lightweight JSON-LD if no SEO plugin does.
 * Singular canonical URLs remain WordPress core's responsibility. Archive
 * canonicals are added separately because core does not emit them there.
 *
 * @return void
 */
function dylan_journal_seo_meta() {
	if ( dylan_journal_has_seo_plugin() || is_admin() || is_feed() || is_robots() || is_404() || is_search() ) {
		return;
	}

	// Do not expose identifying metadata for content that still requires a password.
	if ( is_singular() && post_password_required() ) {
		return;
	}

	$description = trim( preg_replace( '/\s+/', ' ', dylan_journal_seo_description() ) );
	if ( '' === $description ) {
		return;
	}

	$title      = wp_get_document_title();
	$site_name  = get_bloginfo( 'name' );
	$locale     = get_locale();
	$language   = str_replace( '_', '-', $locale );
	$url        = dylan_journal_seo_url();
	$is_article = is_singular( 'post' );
	$type       = $is_article ? 'article' : 'website';
	$image      = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'large' ) : '';
	$image_alt  = '';

	if ( $image ) {
		$image_alt = get_post_meta( get_post_thumbnail_id( get_the_ID() ), '_wp_attachment_image_alt', true );
	}

	// Keep link previews personal when a view has no dedicated featured image.
	// Use the current portrait for general pages and a published photo for the
	// front page and photo archive; never fall back to an unrelated article image.
	if ( ! $image ) {
		$fallback_image_id = ( is_front_page() || is_page( 'fotos' ) ) ? 217 : dylan_journal_portrait_image_id();

		if ( ! $fallback_image_id ) {
			$fallback_image_id = 217;
		}
		$image             = wp_get_attachment_image_url( $fallback_image_id, 'large' );
		$image_alt         = get_post_meta( $fallback_image_id, '_wp_attachment_image_alt', true );
	}

	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

	if ( $is_article ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( DATE_W3C ) ) . '">' . "\n";

		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			echo '<meta property="article:section" content="' . esc_attr( $categories[0]->name ) . '">' . "\n";
		}

		$tags = get_the_tags();
		if ( $tags && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . '">' . "\n";
			}
		}
	}

	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
		if ( $image_alt ) {
			echo '<meta property="og:image:alt" content="' . esc_attr( $image_alt ) . '">' . "\n";
			echo '<meta name="twitter:image:alt" content="' . esc_attr( $image_alt ) . '">' . "\n";
		}
	}

	$website = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'WebSite',
		'@id'         => home_url( '/#website' ),
		'name'        => $site_name,
		'url'         => home_url( '/' ),
		'description' => dylan_journal_seo_description(),
		'inLanguage'  => $language,
		'publisher'   => array(
			'@type' => 'Person',
			'name'  => $site_name,
			'url'   => home_url( '/' ),
		),
	);

	if ( $is_article ) {
		$author_id = (int) get_post_field( 'post_author', get_the_ID() );
		$author_name = get_bloginfo( 'name' );
		$author_url  = dylan_journal_page_url( 'ueber-mich', '/' );
		$schema    = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => $url,
			),
			'headline'         => dylan_journal_schema_title(),
			'description'      => dylan_journal_seo_description(),
			'datePublished'    => get_the_date( DATE_W3C ),
			'dateModified'     => get_the_modified_date( DATE_W3C ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => $author_name ? $author_name : get_the_author_meta( 'display_name', $author_id ),
				'url'   => $author_url,
			),
			'publisher'        => array(
				'@type' => 'Person',
				'name'  => $site_name,
				'url'   => home_url( '/' ),
			),
			'isPartOf'         => array( '@id' => home_url( '/#website' ) ),
			'timeRequired'     => 'PT' . max( 1, (int) ceil( preg_match_all( '/[\p{L}\p{N}]+/u', wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ), $matches ) / 200 ) ) . 'M',
		);

		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			$schema['articleSection'] = wp_specialchars_decode( wp_strip_all_tags( $categories[0]->name ), ENT_QUOTES );
		}

		if ( $image ) {
			$schema['image'] = $image;
		}
	} elseif ( is_front_page() ) {
		$schema = $website;
	} elseif ( is_home() || is_archive() ) {
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'CollectionPage',
			'@id'         => trailingslashit( $url ) . '#collection',
			'name'        => wp_get_document_title(),
			'description' => dylan_journal_seo_description(),
			'url'         => $url,
			'inLanguage'  => $language,
			'isPartOf'    => array( '@id' => home_url( '/#website' ) ),
		);
	} elseif ( is_singular() ) {
		$schema = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'WebPage',
			'name'         => dylan_journal_schema_title(),
			'description'  => dylan_journal_seo_description(),
			'url'          => $url,
			'inLanguage'   => $language,
			'isPartOf'     => array( '@id' => home_url( '/#website' ) ),
		);
	} else {
		$schema = $website;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n";

	if ( is_singular( 'post' ) ) {
		$breadcrumb_items = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => 'Startseite',
				'item'     => home_url( '/' ),
			),
			array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => 'Blog',
				'item'     => dylan_journal_page_url( 'blog', '/blog/' ),
			),
		);

		$categories = get_the_category( get_the_ID() );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$breadcrumb_items[] = array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => wp_specialchars_decode( wp_strip_all_tags( $categories[0]->name ), ENT_QUOTES ),
				'item'     => get_category_link( $categories[0]->term_id ),
			);
		}

		$breadcrumb_items[] = array(
			'@type'    => 'ListItem',
			'position' => count( $breadcrumb_items ) + 1,
				'name'     => dylan_journal_schema_title(),
			'item'     => get_permalink(),
		);

		echo '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $breadcrumb_items,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'dylan_journal_seo_meta', 5 );

/**
 * Do not offer a duplicate author archive for this single-author site.
 *
 * @param WP_Sitemaps_Provider|false $provider Sitemap provider.
 * @param string                     $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function dylan_journal_disable_user_sitemap( $provider, $name ) {
	return 'users' === $name && ! is_multi_author() ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'dylan_journal_disable_user_sitemap', 10, 2 );

/**
 * Apply noindex to thin navigation views while retaining normal link flow.
 *
 * @param array $robots WordPress robots directives.
 * @return array
 */
function dylan_journal_noindex_thin_archives( $robots ) {
	if ( is_singular() && post_password_required() ) {
		unset( $robots['index'] );
		$robots['noindex']   = true;
		$robots['noarchive'] = true;
		if ( empty( $robots['nofollow'] ) ) {
			$robots['follow'] = true;
		}

		return $robots;
	}

	if ( dylan_journal_should_noindex() ) {
		unset( $robots['index'] );
		$robots['noindex'] = true;
		if ( empty( $robots['nofollow'] ) ) {
			$robots['follow'] = true;
		}
	}

	return $robots;
}
add_filter( 'wp_robots', 'dylan_journal_noindex_thin_archives' );

/**
 * Keep an empty placeholder photo page out of Core's sitemap until it has
 * actual image or gallery content.
 *
 * @param array  $args      Sitemap query arguments.
 * @param string $post_type Requested post type.
 * @return array
 */
function dylan_journal_exclude_empty_photos_page_from_sitemap( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$args['post__not_in'] = dylan_journal_excluded_page_ids(
		isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array()
	);

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'dylan_journal_exclude_empty_photos_page_from_sitemap', 10, 2 );

require_once get_template_directory() . '/inc/seo-integration.php';
