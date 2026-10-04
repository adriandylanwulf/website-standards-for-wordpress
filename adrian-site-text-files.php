<?php
/**
 * Plugin Name: Website-Textdateien für Adrian Dylan Wulf
 * Description: Verwaltet maschinenlesbare Website-Standards wie security.txt, robots.txt-Erweiterungen, LLM-Kontext und Webmetadaten.
 * Version: 1.3.0
 * Author: Adrian Dylan Wulf
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: adrian-site-text-files
 *
 * @package Adrian_Site_Text_Files
 */

defined( 'ABSPATH' ) || exit;

define( 'ADRIAN_SITE_TEXT_FILES_VERSION', '1.3.0' );
define( 'ADRIAN_SITE_TEXT_FILES_OPTION', 'adrian_site_text_files_options' );
define( 'ADRIAN_SITE_TEXT_FILES_QUERY_VAR', 'adrian_site_text_file' );

/**
 * Return the immutable list of supported public files.
 *
 * Templates use a deliberately small token set. This keeps URLs, the current
 * year and the security.txt expiry date correct without overwriting text that
 * the site owner has edited in the WordPress backend.
 *
 * @return array<string, array<string, mixed>>
 */
function adrian_site_text_files_definitions() {
	return array(
		'security' => array(
			'label'       => 'Security.txt',
			'path'        => '/.well-known/security.txt',
			'mime'        => 'text/plain; charset=utf-8',
			'format'      => 'Text',
			'description' => 'Kontaktweg für verantwortungsvolle Meldungen zu Sicherheitsproblemen.',
			'enabled'     => true,
			'default'     => "Contact: mailto:privacy@adriandylanwulf.de\nExpires: {security_expires}\nPreferred-Languages: de, en\nCanonical: {site_url}/.well-known/security.txt\n",
		),
		'llms' => array(
			'label'       => 'LLMs.txt',
			'path'        => '/llms.txt',
			'mime'        => 'text/plain; charset=utf-8',
			'format'      => 'Markdown-Text',
			'description' => 'Kurze, freiwillige Orientierung für Suchsysteme, Sprachmodelle und Agents.',
			'enabled'     => true,
			'default'     => "# {site_name}\n\n> Persönliche Website mit Beiträgen über Technik, Webhosting, digitale Sicherheit, Fotos und Alltag.\n\n## Inhalte\n\n- [Blog]({site_url}/blog/): Persönliche Beiträge und technische Erfahrungen.\n- [Über mich]({site_url}/ueber-mich/): Persönlicher Hintergrund und Interessen.\n- [Fotos]({site_url}/fotos/): Eigene Fotografien und Eindrücke unterwegs.\n- [Online & Kontakte]({site_url}/online/): Öffentliche Profile und Kontaktmöglichkeiten.\n\n## Hinweise\n\n- Hauptsprache: Deutsch.\n- Die Inhalte sind persönliche Einordnungen und keine allgemeine Rechts-, Sicherheits- oder Produktberatung.\n- Für die jeweils aktuelle Fassung sind die verlinkten Seiten maßgeblich.\n",
		),
		'llms_full' => array(
			'label'       => 'LLMs-full.txt',
			'path'        => '/llms-full.txt',
			'mime'        => 'text/plain; charset=utf-8',
			'format'      => 'automatisch erzeugter Markdown-Text',
			'description' => 'Optionaler, aus veröffentlichten Inhalten erzeugter Langkontext. Wird nach neuen Beiträgen automatisch neu aufgebaut.',
			'enabled'     => false,
			'default'     => "# {site_name}\n\n{llms_full}\n",
		),
		'humans' => array(
			'label'       => 'Humans.txt',
			'path'        => '/humans.txt',
			'mime'        => 'text/plain; charset=utf-8',
			'format'      => 'Text',
			'description' => 'Technische und redaktionelle Angaben in einer einfachen, menschenlesbaren Form.',
			'enabled'     => true,
			'default'     => "/* TEAM */\nName: Adrian Dylan Wulf\nRole: Betreiber und Autor\nContact: privacy@adriandylanwulf.de\n\n/* SITE */\nName: {site_name}\nURL: {site_url}\nLanguage: de\n\n/* TECHNOLOGY */\nPlatform: WordPress\nHosting: Mittwald\nFonts: Systemschriftstapel, lokal im Browser verfügbar\n\n/* STANDARDS */\nSecurity contact: {site_url}/.well-known/security.txt\nSitemap: {site_url}/wp-sitemap.xml\n",
		),
		'manifest' => array(
			'label'       => 'Webmanifest',
			'path'        => '/manifest.webmanifest',
			'mime'        => 'application/manifest+json; charset=utf-8',
			'format'      => 'JSON',
			'description' => 'Technische Website-Metadaten für Browser und optionale Startbildschirm-Verknüpfungen.',
			'enabled'     => true,
			'default'     => "{\n  \"name\": \"{site_name}\",\n  \"short_name\": \"Adrian Dylan Wulf\",\n  \"description\": \"Persönliche Website von Adrian Dylan Wulf.\",\n  \"lang\": \"de\",\n  \"dir\": \"ltr\",\n  \"start_url\": \"{site_url}/\",\n  \"scope\": \"{site_url}/\",\n  \"display\": \"browser\",\n  \"background_color\": \"#ffffff\",\n  \"theme_color\": \"#1769aa\"\n}\n",
		),
		'tdmrep' => array(
			'label'       => 'TDMRep',
			'path'        => '/.well-known/tdmrep.json',
			'mime'        => 'application/json; charset=utf-8',
			'format'      => 'JSON',
			'description' => 'Optionale, experimentelle Erklärung zu Text- und Data-Mining-Rechten; standardmäßig deaktiviert.',
			'enabled'     => false,
			'default'     => "{\n  \"tdm-reservation\": 1\n}\n",
		),
		'ai' => array(
			'label'       => 'AI.txt',
			'path'        => '/.well-known/ai.txt',
			'mime'        => 'text/plain; charset=utf-8',
			'format'      => 'Text',
			'description' => 'Experimenteller Endpunkt ohne einheitlichen Standard; standardmäßig deaktiviert.',
			'enabled'     => false,
			'default'     => "# Dieser Endpunkt ist derzeit nicht aktiviert.\n",
		),
		'ai_json' => array(
			'label'       => 'AI.json',
			'path'        => '/.well-known/ai.json',
			'mime'        => 'application/json; charset=utf-8',
			'format'      => 'experimentelles JSON',
			'description' => 'Experimenteller Entwurf für maschinenlesbare KI-Präferenzen; standardmäßig deaktiviert.',
			'enabled'     => false,
			'default'     => "{\n  \"status\": \"experimental\",\n  \"site\": \"{site_url}\",\n  \"training\": \"not-specified\"\n}\n",
		),
		'ads' => array(
			'label'       => 'ads.txt',
			'path'        => '/ads.txt',
			'mime'        => 'text/plain; charset=utf-8',
			'format'      => 'IAB-Text',
			'description' => 'Autorisierte Werbeverkäufer; nur aktivieren, wenn tatsächlich Werbeinventar verkauft wird.',
			'enabled'     => false,
			'default'     => "# Kein Werbeinventar konfiguriert.\n",
		),
		'app_ads' => array(
			'label'       => 'app-ads.txt',
			'path'        => '/app-ads.txt',
			'mime'        => 'text/plain; charset=utf-8',
			'format'      => 'IAB-Text',
			'description' => 'Autorisierte Werbeverkäufer für eine eigene App; standardmäßig deaktiviert.',
			'enabled'     => false,
			'default'     => "# Keine eigene App mit Werbeinventar konfiguriert.\n",
		),
		'opensearch' => array(
			'label'       => 'OpenSearch.xml',
			'path'        => '/opensearch.xml',
			'mime'        => 'application/opensearchdescription+xml; charset=utf-8',
			'format'      => 'XML',
			'description' => 'Optionaler Suchanbieter für Browser; standardmäßig deaktiviert.',
			'enabled'     => false,
			'default'     => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<OpenSearchDescription xmlns=\"http://a9.com/-/spec/opensearch/1.1/\">\n  <ShortName>{site_name}</ShortName>\n  <Description>Suche auf {site_name}</Description>\n  <InputEncoding>UTF-8</InputEncoding>\n  <Url type=\"text/html\" method=\"get\" template=\"{site_url}/?s={searchTerms}\" />\n</OpenSearchDescription>\n",
		),
	);
}

/**
 * Return plugin defaults without replacing saved content.
 *
 * @return array<string, mixed>
 */
function adrian_site_text_files_default_options() {
	$files = array();

	foreach ( adrian_site_text_files_definitions() as $key => $definition ) {
		$files[ $key ] = array(
			'enabled' => (bool) $definition['enabled'],
			'content' => (string) $definition['default'],
		);
	}

	return array(
		'allowed_user_id' => 0,
		'files'          => $files,
		'generated'      => array(
			'include_posts' => true,
			'include_pages' => false,
			'max_items'    => 25,
			'max_chars'    => 100000,
		),
		'robots'         => array(
			'enabled' => false,
			'lines'   => '',
		),
		'ai_limits'      => array(
			'per_minute' => 5,
			'per_hour'   => 50,
		),
		'ai'             => array(
			'enabled'        => false,
			'provider'       => 'google',
			'auto_publish'   => false,
			'max_input_chars'=> 50000,
			'last_run'       => 0,
			'last_status'    => 'never',
			'last_message'   => '',
			'last_hash'      => '',
			'draft'          => '',
		),
	);
}

/**
 * Return normalized plugin options.
 *
 * @return array<string, mixed>
 */
function adrian_site_text_files_options() {
	$defaults = adrian_site_text_files_default_options();
	$saved    = get_option( ADRIAN_SITE_TEXT_FILES_OPTION, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	$options = array(
		'allowed_user_id' => isset( $saved['allowed_user_id'] ) ? absint( $saved['allowed_user_id'] ) : 0,
		'files'          => array(),
		'generated'      => array(
			'include_posts' => ! empty( $saved['generated']['include_posts'] ),
			'include_pages' => ! empty( $saved['generated']['include_pages'] ),
			'max_items'    => isset( $saved['generated']['max_items'] ) ? min( 100, max( 1, absint( $saved['generated']['max_items'] ) ) ) : 25,
			'max_chars'    => isset( $saved['generated']['max_chars'] ) ? min( 250000, max( 10000, absint( $saved['generated']['max_chars'] ) ) ) : 100000,
		),
		'robots'         => array(
			'enabled' => ! empty( $saved['robots']['enabled'] ),
			'lines'   => isset( $saved['robots']['lines'] ) ? adrian_site_text_files_sanitize_template( $saved['robots']['lines'] ) : '',
		),
		'ai_limits'      => array(
			'per_minute' => 5,
			'per_hour'   => 50,
		),
		'ai'             => array(),
	);

	$saved_ai = isset( $saved['ai'] ) && is_array( $saved['ai'] ) ? $saved['ai'] : array();
	$provider = isset( $saved_ai['provider'] ) && is_string( $saved_ai['provider'] ) ? sanitize_key( $saved_ai['provider'] ) : 'google';
	if ( 'auto' !== $provider && ! preg_match( '/^[a-z][a-z0-9_-]{0,39}$/', $provider ) ) {
		$provider = 'google';
	}
	$options['ai'] = array(
		'enabled'         => ! empty( $saved_ai['enabled'] ),
		'provider'        => $provider,
		'auto_publish'    => ! empty( $saved_ai['auto_publish'] ),
		'max_input_chars' => isset( $saved_ai['max_input_chars'] ) ? min( 100000, max( 10000, absint( $saved_ai['max_input_chars'] ) ) ) : 50000,
		'last_run'        => isset( $saved_ai['last_run'] ) ? absint( $saved_ai['last_run'] ) : 0,
		'last_status'     => isset( $saved_ai['last_status'] ) && is_string( $saved_ai['last_status'] ) ? sanitize_key( $saved_ai['last_status'] ) : 'never',
		'last_message'    => isset( $saved_ai['last_message'] ) && is_string( $saved_ai['last_message'] ) ? sanitize_text_field( $saved_ai['last_message'] ) : '',
		'last_hash'       => isset( $saved_ai['last_hash'] ) && is_string( $saved_ai['last_hash'] ) ? sanitize_key( $saved_ai['last_hash'] ) : '',
		'draft'           => isset( $saved_ai['draft'] ) && is_string( $saved_ai['draft'] ) ? adrian_site_text_files_sanitize_template( $saved_ai['draft'] ) : '',
	);

	foreach ( $defaults['files'] as $key => $default_file ) {
		$saved_file = isset( $saved['files'][ $key ] ) && is_array( $saved['files'][ $key ] ) ? $saved['files'][ $key ] : array();
		$options['files'][ $key ] = array(
			'enabled' => isset( $saved_file['enabled'] ) ? (bool) $saved_file['enabled'] : (bool) $default_file['enabled'],
			'content' => isset( $saved_file['content'] ) && is_string( $saved_file['content'] ) ? $saved_file['content'] : $default_file['content'],
		);
	}

	return $options;
}

/**
 * Determine whether the current request belongs to the one locked editor.
 *
 * @return bool
 */
function adrian_site_text_files_can_manage() {
	$allowed_user_id = (int) adrian_site_text_files_options()['allowed_user_id'];

	return $allowed_user_id > 0 && get_current_user_id() === $allowed_user_id && current_user_can( 'manage_options' );
}

/**
 * Normalize editable plain text without allowing control characters to leak
 * into generated responses. The endpoint serves text/JSON, never HTML.
 *
 * @param mixed $value Submitted template.
 * @return string
 */
function adrian_site_text_files_sanitize_template( $value ) {
	$value = is_string( $value ) ? wp_unslash( $value ) : '';
	$value = str_replace( "\r\n", "\n", $value );
	$value = str_replace( "\r", "\n", $value );
	$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value );

	if ( ! is_string( $value ) ) {
		return '';
	}

	return substr( $value, 0, 20000 );
}

/**
 * Build the optional long LLM context from public content.
 *
 * The result is transient-cached and invalidated when a public post or page
 * changes. No external AI service is called; this is a deterministic export
 * of content already published on the website.
 *
 * @return string
 */
function adrian_site_text_files_generate_llms_full() {
	$options = adrian_site_text_files_options();
	$settings = $options['generated'];
	$cache_key = 'adrian_site_text_files_llms_' . md5( wp_json_encode( $settings ) );
	$cached    = get_transient( $cache_key );

	if ( is_string( $cached ) ) {
		return $cached;
	}

	$post_types = array( 'post' );
	if ( ! empty( $settings['include_pages'] ) ) {
		$post_types[] = 'page';
	}

	$items = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => (int) $settings['max_items'],
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$sections = array(
		'## Veröffentliche Inhalte',
		'',
		'Quelle: ' . untrailingslashit( home_url( '/' ) ),
		'Erstellt: ' . wp_date( 'Y-m-d' ),
		'',
	);
	$used_chars = strlen( implode( "\n", $sections ) );

	foreach ( $items as $item ) {
		$title = get_the_title( $item );
		$url   = get_permalink( $item );
		$body  = strip_shortcodes( (string) $item->post_content );
		$body  = wp_strip_all_tags( $body );
		$body  = preg_replace( "/[ \t]+\n/", "\n", $body );
		$body  = preg_replace( "/\n{3,}/", "\n\n", $body );
		$body  = trim( is_string( $body ) ? $body : '' );
		$entry = '### [' . $title . '](' . esc_url_raw( $url ) . ")\n\n" . $body . "\n\n";

		if ( $used_chars + strlen( $entry ) > (int) $settings['max_chars'] ) {
			break;
		}

		$sections[] = $entry;
		$used_chars += strlen( $entry );
	}

	if ( count( $items ) === 0 ) {
		$sections[] = 'Noch keine öffentlichen Beiträge oder Seiten ausgewählt.';
	}

	$result = trim( implode( "\n", $sections ) ) . "\n";
	set_transient( $cache_key, $result, 10 * MINUTE_IN_SECONDS );

	return $result;
}

/**
 * Invalidate generated content after editorial changes.
 *
 * @return void
 */
function adrian_site_text_files_invalidate_generated() {
	$settings  = adrian_site_text_files_options()['generated'];
	$cache_key = 'adrian_site_text_files_llms_' . md5( wp_json_encode( $settings ) );
	delete_transient( $cache_key );
}
add_action( 'save_post', 'adrian_site_text_files_invalidate_generated', 20 );
add_action( 'deleted_post', 'adrian_site_text_files_invalidate_generated', 20 );

/**
 * Return whether the official WordPress AI Client API is available.
 *
 * The standards plugin remains fully usable without the AI plugin. The AI
 * feature is deliberately opt-in and fails closed when the API is missing.
 *
 * @return bool
 */
function adrian_site_text_files_ai_api_available() {
	return function_exists( 'wp_ai_client_prompt' ) && function_exists( 'wp_supports_ai' ) && wp_supports_ai();
}

/**
 * Return a non-secret status summary for the configured AI provider.
 *
 * @return array<string, mixed>
 */
function adrian_site_text_files_ai_status() {
	$options  = adrian_site_text_files_options();
	$provider = $options['ai']['provider'];
	$configured = false;

	if ( adrian_site_text_files_ai_api_available() ) {
		if ( 'auto' === $provider && function_exists( 'WordPress\\AI\\has_ai_credentials' ) ) {
			$configured = (bool) \WordPress\AI\has_ai_credentials();
		} elseif ( 'auto' !== $provider && function_exists( 'WordPress\\AI\\has_connector_authentication' ) ) {
			$configured = (bool) \WordPress\AI\has_connector_authentication( $provider );
		}
	}

	return array(
		'available'   => adrian_site_text_files_ai_api_available(),
		'provider'    => $provider,
		'configured'  => $configured,
		'approval_url'=> admin_url( 'tools.php?page=ai-connector-approval' ),
	);
}

/**
 * Redact common direct contact data before public content is sent to an AI
 * connector. This is a safety baseline, not a guarantee that text contains
 * no personal data.
 *
 * @param string $text Public source text.
 * @return string
 */
function adrian_site_text_files_ai_redact_text( $text ) {
	$text = preg_replace( '/[A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,}/iu', '[E-Mail entfernt]', (string) $text );
	$text = preg_replace( '/(?<!\\d)(?:\\+49|0)[0-9 ()\\/.\\-]{7,}(?!\\d)/', '[Telefonnummer entfernt]', (string) $text );
	return is_string( $text ) ? $text : '';
}

/**
 * Build the bounded, public-only source sent to the AI connector.
 *
 * Drafts, media and legal/contact pages are excluded. The owner can choose
 * whether published pages are included in the existing LLM settings, but
 * legal pages remain excluded from the automatic connector job.
 *
 * @return string
 */
function adrian_site_text_files_ai_source() {
	$options    = adrian_site_text_files_options();
	$settings   = $options['generated'];
	$max_chars  = (int) $options['ai']['max_input_chars'];
	$post_types = array( 'post' );
	if ( ! empty( $settings['include_pages'] ) ) {
		$post_types[] = 'page';
	}

	$items = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => min( 100, (int) $settings['max_items'] ),
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$parts       = array( 'Website: ' . get_bloginfo( 'name' ), 'URL: ' . untrailingslashit( home_url( '/' ) ), 'Sprache: Deutsch', '', 'Öffentliche Inhalte:' );
	$used_chars  = strlen( implode( "\\n", $parts ) );
	$excluded_slugs = array( 'impressum', 'datenschutz', 'privacy-policy', 'privacy', 'kontakt', 'kontaktformular' );

	foreach ( $items as $item ) {
		$slug = sanitize_title( (string) $item->post_name );
		if ( 'page' === $item->post_type && in_array( $slug, $excluded_slugs, true ) ) {
			continue;
		}

		$title = trim( wp_strip_all_tags( get_the_title( $item ) ) );
		$url   = esc_url_raw( get_permalink( $item ) );
		$body  = strip_shortcodes( (string) $item->post_content );
		$body  = wp_strip_all_tags( $body );
		$body  = preg_replace( "/[ \\t]+\\n/", "\\n", $body );
		$body  = preg_replace( "/\\n{3,}/", "\\n\\n", $body );
		$body  = trim( adrian_site_text_files_ai_redact_text( is_string( $body ) ? $body : '' ) );
		if ( function_exists( 'mb_substr' ) ) {
			$body = mb_substr( $body, 0, 7000 );
		} else {
			$body = substr( $body, 0, 7000 );
		}

		$entry = "\\n\\n### {$title}\\nURL: {$url}\\n{$body}";
		if ( $used_chars + strlen( $entry ) > $max_chars ) {
			break;
		}

		$parts[] = $entry;
		$used_chars += strlen( $entry );
	}

	return trim( implode( "\\n", $parts ) );
}

/**
 * Validate AI output before it can become a public machine-readable file.
 *
 * @param string $content Candidate llms.txt content.
 * @return true|WP_Error
 */
function adrian_site_text_files_validate_ai_output( $content ) {
	$content = trim( (string) $content );
	if ( '' === $content || strlen( $content ) < 40 ) {
		return new WP_Error( 'ai_output_empty', 'Die KI hat keine verwertbare llms.txt zurückgegeben.' );
	}
	if ( strlen( $content ) > 100000 ) {
		return new WP_Error( 'ai_output_too_large', 'Die KI-Ausgabe überschreitet die Sicherheitsgrenze.' );
	}
	if ( false !== stripos( $content, '<script' ) || false !== stripos( $content, 'javascript:' ) || false !== stripos( $content, '<iframe' ) ) {
		return new WP_Error( 'ai_output_unsafe', 'Die KI-Ausgabe enthält nicht erlaubte aktive Inhalte.' );
	}
	if ( ! preg_match( '/^#\\s+[^\\n]+/u', $content ) ) {
		return new WP_Error( 'ai_output_invalid', 'Die KI-Ausgabe beginnt nicht mit einer gültigen Überschrift.' );
	}

	$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	if ( preg_match_all( '/https?:\\/\\/[^\\s)]+/i', $content, $matches ) ) {
		foreach ( $matches[0] as $url ) {
			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( '' !== $host && $host !== $site_host && ( '' === $site_host || 0 !== substr_compare( $host, '.' . $site_host, -strlen( '.' . $site_host ) ) ) ) {
				return new WP_Error( 'ai_output_external_link', 'Die KI-Ausgabe enthält einen nicht erlaubten externen Link.' );
			}
		}
	}

	return true;
}

/**
 * Generate text through the official WordPress AI Client API.
 *
 * @param string $prompt   Prompt text. It is bounded before the request.
 * @param string $provider Connector ID or `auto`.
 * @return string|WP_Error
 */
function adrian_site_text_files_ai_generate_text( $prompt, $provider ) {
	if ( ! adrian_site_text_files_ai_api_available() ) {
		return new WP_Error( 'ai_unavailable', 'Die offizielle WordPress-AI-Schnittstelle ist nicht verfügbar.' );
	}
	if ( ! is_string( $prompt ) || '' === trim( $prompt ) || strlen( $prompt ) > 120000 ) {
		return new WP_Error( 'ai_prompt_invalid', 'Die KI-Anfrage ist leer oder überschreitet die Sicherheitsgrenze.' );
	}

	try {
		$builder = wp_ai_client_prompt( $prompt )
			->using_system_instruction( 'Du arbeitest als sorgfältiger Redakteur. Gib nur die angeforderte Textdatei zurück und halte dich strikt an die Quellen.' )
			->using_temperature( 0.2 )
			->using_max_tokens( 2200 );
		if ( 'auto' !== $provider ) {
			$builder = $builder->using_provider( $provider );
		}

		$result = $builder->generate_text();
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'ai_generation_failed', sanitize_text_field( substr( $result->get_error_message(), 0, 300 ) ) );
		}

		return (string) $result;
	} catch ( Throwable $exception ) {
		return new WP_Error( 'ai_generation_failed', sanitize_text_field( substr( $exception->getMessage(), 0, 300 ) ) );
	}
}

/**
 * Generate and optionally publish the machine-readable llms.txt file.
 *
 * @param bool $automatic Whether this is the hourly job.
 * @return true|WP_Error
 */
function adrian_site_text_files_ai_refresh( $automatic = false ) {
	$options = adrian_site_text_files_options();
	if ( $automatic && empty( $options['ai']['enabled'] ) ) {
		return new WP_Error( 'ai_disabled', 'Die automatische KI-Aktualisierung ist deaktiviert.' );
	}
	if ( ! adrian_site_text_files_ai_api_available() ) {
		return new WP_Error( 'ai_unavailable', 'Die offizielle WordPress-AI-Schnittstelle ist nicht verfügbar.' );
	}
	if ( $automatic && get_transient( 'adrian_site_text_files_ai_job_lock' ) ) {
		return new WP_Error( 'ai_busy', 'Eine KI-Aktualisierung läuft bereits.' );
	}
	if ( $automatic ) {
		set_transient( 'adrian_site_text_files_ai_job_lock', 1, 10 * MINUTE_IN_SECONDS );
		if ( get_transient( 'adrian_site_text_files_ai_hour_window' ) ) {
			delete_transient( 'adrian_site_text_files_ai_job_lock' );
			return new WP_Error( 'ai_rate_limited', 'Das stündliche KI-Limit wurde bereits genutzt.' );
		}
		set_transient( 'adrian_site_text_files_ai_hour_window', 1, HOUR_IN_SECONDS + 300 );
	}

	try {
		$source = adrian_site_text_files_ai_source();
		$prompt = "Erstelle die vollständige, sachliche und natürliche Markdown-Datei llms.txt für diese persönliche WordPress-Website.\\n\\n";
		$prompt .= "Regeln:\\n- Antworte ausschließlich mit Markdown-Text, ohne Codeblock und ohne Vorbemerkung.\\n";
		$prompt .= "- Verwende ausschließlich die unten gelieferten Fakten und URLs. Erfinde keine Angebote, Personen, Produkte oder Aussagen.\\n";
		$prompt .= "- Verlinke nur URLs derselben Website. Keine externen Links, E-Mail-Adressen oder Telefonnummern.\\n";
		$prompt .= "- Schreibe zurückhaltend, persönlich und klar. Die Datei ist eine Orientierung für Suchsysteme und Sprachmodelle, keine Werbung.\\n\\n";
		$prompt .= "QUELLEN:\\n" . $source;

		$content = adrian_site_text_files_ai_generate_text( $prompt, $options['ai']['provider'] );
		if ( is_wp_error( $content ) ) {
			throw new Exception( $content->get_error_message() );
		}
		$content = preg_replace( '/^```(?:markdown|md|text)?\\s*|\\s*```$/i', '', trim( (string) $content ) );
		$validation = adrian_site_text_files_validate_ai_output( $content );
		if ( is_wp_error( $validation ) ) {
			throw new Exception( $validation->get_error_message() );
		}

		$options['ai']['last_run']     = time();
		$options['ai']['last_hash']    = md5( $content );
		$options['ai']['draft']        = adrian_site_text_files_sanitize_template( $content );
		$options['ai']['last_status']  = 'draft';
		$options['ai']['last_message'] = 'Vorschlag erfolgreich erzeugt; die öffentliche Datei wurde nicht überschrieben.';

		if ( ! empty( $options['ai']['auto_publish'] ) && ! empty( $options['files']['llms']['enabled'] ) ) {
			$options['files']['llms']['content'] = adrian_site_text_files_sanitize_template( $content );
			$options['ai']['last_status']       = 'published';
			$options['ai']['last_message']      = 'Vorschlag geprüft und automatisch in llms.txt veröffentlicht.';
		}

		update_option( ADRIAN_SITE_TEXT_FILES_OPTION, $options, false );
		return true;
	} catch ( Throwable $exception ) {
		$options['ai']['last_run']     = time();
		$options['ai']['last_status']  = 'error';
		$options['ai']['last_message'] = sanitize_text_field( substr( $exception->getMessage(), 0, 300 ) );
		update_option( ADRIAN_SITE_TEXT_FILES_OPTION, $options, false );
		return new WP_Error( 'ai_refresh_failed', $options['ai']['last_message'] );
	} finally {
		if ( $automatic ) {
			delete_transient( 'adrian_site_text_files_ai_job_lock' );
		}
	}
}

/**
 * Run the opt-in hourly AI refresh through WordPress Cron.
 *
 * @return void
 */
function adrian_site_text_files_ai_cron() {
	$options = adrian_site_text_files_options();
	if ( empty( $options['ai']['enabled'] ) ) {
		return;
	}
	$allowed_user = get_user_by( 'id', (int) $options['allowed_user_id'] );
	if ( ! $allowed_user || ! user_can( $allowed_user, 'manage_options' ) ) {
		return;
	}
	adrian_site_text_files_ai_refresh( true );
}
add_action( 'adrian_site_text_files_ai_refresh_event', 'adrian_site_text_files_ai_cron' );

/**
 * Keep the hourly event aligned with the explicit admin setting.
 *
 * @return void
 */
function adrian_site_text_files_ai_schedule() {
	$options  = adrian_site_text_files_options();
	$next_run = wp_next_scheduled( 'adrian_site_text_files_ai_refresh_event' );
	if ( ! empty( $options['ai']['enabled'] ) && false === $next_run ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'adrian_site_text_files_ai_refresh_event' );
	} elseif ( empty( $options['ai']['enabled'] ) && false !== $next_run ) {
		wp_clear_scheduled_hook( 'adrian_site_text_files_ai_refresh_event' );
	}
}
add_action( 'admin_init', 'adrian_site_text_files_ai_schedule', 20 );

/**
 * Remove the hourly event when the plugin is deactivated.
 *
 * @return void
 */
function adrian_site_text_files_deactivation() {
	wp_clear_scheduled_hook( 'adrian_site_text_files_ai_refresh_event' );
}
register_deactivation_hook( __FILE__, 'adrian_site_text_files_deactivation' );

/**
 * Replace the supported dynamic tokens at response time.
 *
 * @param string $content Stored template.
 * @return string
 */
function adrian_site_text_files_render( $content ) {
	$site_url = untrailingslashit( home_url( '/' ) );
	$tokens   = array(
		'{site_url}'         => $site_url,
		'{site_name}'        => get_bloginfo( 'name' ),
		'{year}'             => wp_date( 'Y' ),
		'{security_expires}' => gmdate( 'Y-m-d\\TH:i:s\\Z', time() + YEAR_IN_SECONDS ),
	);

	if ( false !== strpos( (string) $content, '{llms_full}' ) ) {
		$tokens['{llms_full}'] = adrian_site_text_files_generate_llms_full();
	}

	return strtr( (string) $content, $tokens );
}

/**
 * Validate structured files after token replacement.
 *
 * @param string $key File key.
 * @param string $content Stored template.
 * @return true|WP_Error
 */
function adrian_site_text_files_validate_content( $key, $content ) {
	$definitions = adrian_site_text_files_definitions();

	if ( ! isset( $definitions[ $key ] ) ) {
		return new WP_Error( 'unknown_file', 'Unbekannte Textdatei.' );
	}

	if ( in_array( $key, array( 'manifest', 'tdmrep', 'ai_json' ), true ) ) {
		$json = json_decode( adrian_site_text_files_render( $content ), true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $json ) ) {
			return new WP_Error( 'invalid_json', $definitions[ $key ]['label'] . ' muss gültiges JSON enthalten.' );
		}
	}

	return true;
}

/**
 * Register the public virtual routes.
 *
 * @return void
 */
function adrian_site_text_files_register_routes() {
	foreach ( adrian_site_text_files_definitions() as $key => $definition ) {
		$path = ltrim( (string) $definition['path'], '/' );
		add_rewrite_rule(
			'^' . preg_quote( $path, '/' ) . '$',
			'index.php?' . ADRIAN_SITE_TEXT_FILES_QUERY_VAR . '=' . rawurlencode( $key ),
			'top'
		);
	}
}
add_action( 'init', 'adrian_site_text_files_register_routes' );

/**
 * Register the private query variable.
 *
 * @param array<int, string> $vars Query variables.
 * @return array<int, string>
 */
function adrian_site_text_files_query_vars( $vars ) {
	$vars[] = ADRIAN_SITE_TEXT_FILES_QUERY_VAR;

	return $vars;
}
add_filter( 'query_vars', 'adrian_site_text_files_query_vars' );

/**
 * Serve enabled virtual files before normal WordPress template output.
 *
 * @return void
 */
function adrian_site_text_files_serve_endpoint() {
	$key = sanitize_key( get_query_var( ADRIAN_SITE_TEXT_FILES_QUERY_VAR ) );

	if ( '' === $key ) {
		return;
	}

	$definitions = adrian_site_text_files_definitions();
	$options     = adrian_site_text_files_options();

	if ( ! isset( $definitions[ $key ], $options['files'][ $key ] ) || ! $options['files'][ $key ]['enabled'] ) {
		status_header( 404 );
		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		exit;
	}

	nocache_headers();
	header( 'Content-Type: ' . $definitions[ $key ]['mime'] );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Cache-Control: public, max-age=300, must-revalidate' );
	$content = adrian_site_text_files_render( $options['files'][ $key ]['content'] );

	// Site name and URL tokens can change after an admin save. Fail closed
	// instead of serving malformed JSON to browsers or consuming systems.
	if ( in_array( $key, array( 'manifest', 'tdmrep', 'ai_json' ), true ) ) {
		json_decode( $content, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			status_header( 500 );
			header( 'Cache-Control: no-store' );
			exit;
		}
	}

	if ( 'HEAD' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : 'GET' ) ) {
		echo $content;
	}

	exit;
}
add_action( 'template_redirect', 'adrian_site_text_files_serve_endpoint', 0 );

/**
 * Advertise the manifest only while its public endpoint is enabled.
 *
 * @return void
 */
function adrian_site_text_files_manifest_link() {
	$options = adrian_site_text_files_options();

	if ( empty( $options['files']['manifest']['enabled'] ) ) {
		return;
	}

	printf(
		"<link rel=\"manifest\" href=\"%s\">\n",
		esc_url( home_url( '/manifest.webmanifest' ) )
	);
}
add_action( 'wp_head', 'adrian_site_text_files_manifest_link', 1 );

/**
 * Extend WordPress' virtual robots.txt without replacing core output.
 *
 * @param string $output Current robots output.
 * @param bool   $public Whether the site is public.
 * @return string
 */
function adrian_site_text_files_extend_robots( $output, $public ) {
	$options = adrian_site_text_files_options();

	if ( ! $public || empty( $options['robots']['enabled'] ) || '' === trim( $options['robots']['lines'] ) ) {
		return $output;
	}

	return rtrim( $output ) . "\n\n# Website-Standards\n" . trim( $options['robots']['lines'] ) . "\n";
}
add_filter( 'robots_txt', 'adrian_site_text_files_extend_robots', 20, 2 );

/**
 * Apply bounded per-user limits to an AI connector integration.
 *
 * @param string $action Action namespace.
 * @param int    $user_id Current user ID.
 * @return true|WP_Error
 */
function adrian_site_text_files_allow_ai_request( $action, $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id < 1 ) {
		return new WP_Error( 'not_authenticated', 'Für KI-Anfragen ist eine Anmeldung erforderlich.' );
	}

	$limits      = adrian_site_text_files_options()['ai_limits'];
	$minute_key  = 'adrian_site_text_ai_m_' . $user_id . '_' . md5( $action );
	$hour_key    = 'adrian_site_text_ai_h_' . $user_id . '_' . md5( $action );
	$minute_used = (int) get_transient( $minute_key );
	$hour_used   = (int) get_transient( $hour_key );

	if ( $minute_used >= (int) $limits['per_minute'] ) {
		return new WP_Error( 'rate_limited_minute', 'Das Minutenlimit für diese KI-Funktion ist erreicht.' );
	}

	if ( $hour_used >= (int) $limits['per_hour'] ) {
		return new WP_Error( 'rate_limited_hour', 'Das Stundenlimit für diese KI-Funktion ist erreicht.' );
	}

	set_transient( $minute_key, $minute_used + 1, MINUTE_IN_SECONDS );
	set_transient( $hour_key, $hour_used + 1, HOUR_IN_SECONDS );

	return true;
}

/**
 * Connector entry point using the official WordPress AI Client API.
 *
 * A third-party integration may still replace the result through the filter,
 * but the built-in path uses only the connector selected in this plugin's
 * locked admin screen. No credentials are read by this plugin.
 *
 * @param mixed $payload Connector-specific payload.
 * @return mixed|WP_Error
 */
function adrian_site_text_files_ai_request( $payload ) {
	if ( ! adrian_site_text_files_can_manage() ) {
		return new WP_Error( 'forbidden', 'Diese KI-Funktion ist nur für den gesperrten Administrator verfügbar.' );
	}

	$allowed = adrian_site_text_files_allow_ai_request( 'default', get_current_user_id() );
	if ( is_wp_error( $allowed ) ) {
		return $allowed;
	}

	$result = apply_filters( 'adrian_site_text_files_ai_request', null, $payload, get_current_user_id() );
	if ( null !== $result ) {
		return $result;
	}

	$payload = is_array( $payload ) ? $payload : array();
	$prompt  = isset( $payload['prompt'] ) && is_string( $payload['prompt'] ) ? $payload['prompt'] : '';
	if ( '' === trim( $prompt ) ) {
		return new WP_Error( 'prompt_missing', 'Für die KI-Anfrage wurde kein Text übergeben.' );
	}

	$options = adrian_site_text_files_options();
	return adrian_site_text_files_ai_generate_text( $prompt, $options['ai']['provider'] );
}

/**
 * Check the local configuration without making outbound HTTP requests.
 *
 * @return array<string, mixed>
 */
function adrian_site_text_files_validation() {
	$definitions = adrian_site_text_files_definitions();
	$options     = adrian_site_text_files_options();
	$results     = array();
	$valid       = 0;
	$warnings    = 0;
	$errors      = 0;

	foreach ( $definitions as $key => $definition ) {
		if ( empty( $options['files'][ $key ]['enabled'] ) ) {
			$results[ $key ] = array( 'status' => 'disabled', 'message' => 'Nicht veröffentlicht.' );
			continue;
		}

		$content = adrian_site_text_files_render( $options['files'][ $key ]['content'] );
		$status  = 'valid';
		$message = 'Konfiguration ist lokal gültig.';

		if ( '' === trim( $content ) ) {
			$status  = 'error';
			$message = 'Der aktivierte Endpunkt ist leer.';
		} elseif ( in_array( $key, array( 'manifest', 'tdmrep', 'ai_json' ), true ) ) {
			json_decode( $content, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				$status  = 'error';
				$message = 'Ungültiges JSON: ' . json_last_error_msg();
			}
		}

		if ( 'security' === $key ) {
			if ( ! preg_match( '/^Contact:\s*\S+/mi', $content ) || ! preg_match( '/^Expires:\s*\S+/mi', $content ) || ! preg_match( '/^Canonical:\s*' . preg_quote( untrailingslashit( home_url( '/' ) ), '/' ) . '\/\.well-known\/security\.txt\s*$/mi', $content ) ) {
				$status  = 'error';
				$message = 'Contact, Expires oder Canonical fehlen beziehungsweise passen nicht zur Website.';
			}
		}

		$physical_path = ABSPATH . ltrim( $definition['path'], '/' );
		if ( file_exists( $physical_path ) && 'error' !== $status ) {
			$status  = 'warning';
			$message = 'Zusätzlich existiert eine physische Datei am selben Pfad; bitte Doppelversorgung prüfen.';
		}

		$results[ $key ] = array( 'status' => $status, 'message' => $message );
		if ( 'valid' === $status ) {
			++$valid;
		} elseif ( 'warning' === $status ) {
			++$warnings;
		} else {
			++$errors;
		}
	}

	if ( ! empty( $options['robots']['enabled'] ) ) {
		$robots_message = 'WordPress-Core-Ausgabe bleibt erhalten; Erweiterung ist aktiv.';
		$robots_status  = 'valid';
		if ( false !== stripos( $options['robots']['lines'], 'Sitemap:' ) ) {
			$robots_status  = 'warning';
			$robots_message = 'Eine eigene Sitemap-Zeile ist eingetragen; WordPress fügt die Core-Sitemap bereits selbst hinzu.';
		}
		$results['robots'] = array( 'status' => $robots_status, 'message' => $robots_message );
		if ( 'warning' === $robots_status ) {
			++$warnings;
		} else {
			++$valid;
		}
	}

	return array(
		'results'  => $results,
		'valid'    => $valid,
		'warnings' => $warnings,
		'errors'   => $errors,
	);
}

/**
 * Set the initial lock to the only administrator when possible.
 *
 * @return void
 */
function adrian_site_text_files_activate() {
	$options = get_option( ADRIAN_SITE_TEXT_FILES_OPTION, false );

	if ( false === $options || ! is_array( $options ) ) {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => array( 'ID' ),
			)
		);
		$defaults = adrian_site_text_files_default_options();

		if ( ! empty( $admins[0]->ID ) ) {
			$defaults['allowed_user_id'] = (int) $admins[0]->ID;
		}

		update_option( ADRIAN_SITE_TEXT_FILES_OPTION, $defaults, false );
	}

	adrian_site_text_files_register_routes();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'adrian_site_text_files_activate' );

/**
 * Flush routes when the plugin is deactivated.
 *
 * @return void
 */
function adrian_site_text_files_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'adrian_site_text_files_deactivate' );

/**
 * Register the locked admin page.
 *
 * @return void
 */
function adrian_site_text_files_admin_menu() {
	if ( ! adrian_site_text_files_can_manage() ) {
		return;
	}

	add_options_page(
		'Website-Standards',
		'Website-Standards',
		'manage_options',
		'adrian-site-text-files',
		'adrian_site_text_files_render_admin_page'
	);
}
add_action( 'admin_menu', 'adrian_site_text_files_admin_menu' );

/**
 * Load a small, local-only admin stylesheet on the plugin screen.
 *
 * No frontend assets or external fonts are loaded. This keeps the public
 * website unchanged and makes the editor easier to scan on smaller screens.
 *
 * @param string $hook_suffix Current admin screen hook.
 * @return void
 */
function adrian_site_text_files_admin_styles( $hook_suffix ) {
	if ( 'settings_page_adrian-site-text-files' !== $hook_suffix || ! adrian_site_text_files_can_manage() ) {
		return;
	}

	wp_register_style( 'adrian-site-text-files-admin', false, array(), ADRIAN_SITE_TEXT_FILES_VERSION );
	wp_enqueue_style( 'adrian-site-text-files-admin' );
	wp_add_inline_style(
		'adrian-site-text-files-admin',
		'.adrian-stf-shell{max-width:1100px}.adrian-stf-intro{max-width:760px;color:#50575e;font-size:14px}.adrian-stf-summary{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}.adrian-stf-summary span{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border:1px solid #dcdcde;border-radius:999px;background:#fff}.adrian-stf-card{margin:18px 0;padding:20px 22px;border:1px solid #dcdcde;border-radius:8px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04)}.adrian-stf-card h2{margin:0 0 8px}.adrian-stf-card p{max-width:820px}.adrian-stf-card--assistant{border-top:4px solid #1769aa;background:linear-gradient(180deg,#f6faff 0,#fff 130px)}.adrian-stf-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}.adrian-stf-field{display:flex;flex-direction:column;gap:5px}.adrian-stf-field label{font-weight:600}.adrian-stf-field input,.adrian-stf-field select{max-width:100%}.adrian-stf-help{color:#50575e;font-size:13px}.adrian-stf-meta{color:#50575e}.adrian-stf-status{font-weight:600}.adrian-stf-status-valid{color:#16794c}.adrian-stf-status-warning{color:#996800}.adrian-stf-status-error{color:#b32d2e}.adrian-stf-status-disabled{color:#50575e}.adrian-stf-code{width:100%;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;line-height:1.45}.adrian-stf-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px}.adrian-stf-divider{border:0;border-top:1px solid #dcdcde;margin:22px 0}@media(max-width:782px){.adrian-stf-card{padding:16px}.adrian-stf-grid{grid-template-columns:1fr}.adrian-stf-actions{align-items:flex-start;flex-direction:column}.adrian-stf-actions .button{width:100%;text-align:center}}'
	);
}
add_action( 'admin_enqueue_scripts', 'adrian_site_text_files_admin_styles' );

/**
 * Save the explicit AI automation settings.
 *
 * @return array{type:string,message:string}|null
 */
function adrian_site_text_files_ai_save_admin_form() {
	if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return null;
	}
	$action = isset( $_POST['adrian_site_text_files_action'] ) && is_string( $_POST['adrian_site_text_files_action'] ) ? sanitize_key( wp_unslash( $_POST['adrian_site_text_files_action'] ) ) : '';
	if ( 'ai_save' !== $action ) {
		return null;
	}
	if ( ! adrian_site_text_files_can_manage() ) {
		return array( 'type' => 'error', 'message' => 'Zugriff verweigert.' );
	}
	check_admin_referer( 'adrian_site_text_files_ai_save' );

	$options   = adrian_site_text_files_options();
	$submitted = isset( $_POST['ai'] ) && is_array( $_POST['ai'] ) ? wp_unslash( $_POST['ai'] ) : array();
	$provider  = isset( $submitted['provider'] ) && is_string( $submitted['provider'] ) ? sanitize_key( $submitted['provider'] ) : 'google';
	if ( 'auto' !== $provider && ! preg_match( '/^[a-z][a-z0-9_-]{0,39}$/', $provider ) ) {
		return array( 'type' => 'error', 'message' => 'Die Connector-ID ist ungültig.' );
	}

	$enabled       = ! empty( $submitted['enabled'] );
	$auto_publish  = ! empty( $submitted['auto_publish'] );
	$confirmed     = ! empty( $_POST['ai_confirm'] );
	$max_input     = isset( $submitted['max_input_chars'] ) ? min( 100000, max( 10000, absint( $submitted['max_input_chars'] ) ) ) : 50000;

	if ( ( $enabled || $auto_publish ) && ! $confirmed ) {
		return array( 'type' => 'error', 'message' => 'Bitte bestätige ausdrücklich, dass ausgewählte veröffentlichte Inhalte an den KI-Connector übertragen werden dürfen.' );
	}
	if ( $auto_publish && empty( $options['files']['llms']['enabled'] ) ) {
		return array( 'type' => 'error', 'message' => 'Automatische Veröffentlichung ist erst möglich, wenn llms.txt veröffentlicht wird.' );
	}

	$options['ai']['enabled']         = $enabled;
	$options['ai']['provider']        = $provider;
	$options['ai']['auto_publish']    = $auto_publish;
	$options['ai']['max_input_chars'] = $max_input;
	update_option( ADRIAN_SITE_TEXT_FILES_OPTION, $options, false );
	adrian_site_text_files_ai_schedule();

	$message = $enabled ? 'Die stündliche KI-Aktualisierung ist aktiviert. Der erste automatische Lauf wird frühestens in einer Stunde gestartet.' : 'Die stündliche KI-Aktualisierung ist deaktiviert. Es werden keine Inhalte automatisch übertragen.';
	if ( $auto_publish ) {
		$message .= ' Gültige Vorschläge dürfen danach ausschließlich llms.txt aktualisieren.';
	}
	return array( 'type' => 'updated', 'message' => $message );
}

/**
 * Run one explicit AI refresh from the locked admin screen.
 *
 * @return array{type:string,message:string}|null
 */
function adrian_site_text_files_ai_run_admin_form() {
	if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return null;
	}
	$action = isset( $_POST['adrian_site_text_files_action'] ) && is_string( $_POST['adrian_site_text_files_action'] ) ? sanitize_key( wp_unslash( $_POST['adrian_site_text_files_action'] ) ) : '';
	if ( 'ai_run' !== $action ) {
		return null;
	}
	if ( ! adrian_site_text_files_can_manage() ) {
		return array( 'type' => 'error', 'message' => 'Zugriff verweigert.' );
	}
	check_admin_referer( 'adrian_site_text_files_ai_run' );
	$allowed = adrian_site_text_files_allow_ai_request( 'refresh', get_current_user_id() );
	if ( is_wp_error( $allowed ) ) {
		return array( 'type' => 'error', 'message' => $allowed->get_error_message() );
	}

	$result = adrian_site_text_files_ai_refresh( false );
	if ( is_wp_error( $result ) ) {
		$message = $result->get_error_message();
		if ( false !== strpos( $message, 'not been approved' ) || false !== strpos( $message, 'nicht genehmigt' ) ) {
			$message .= ' Öffne danach Tools → Connector Approvals und gib adrian-site-text-files Zugriff auf den gewünschten Connector.';
		}
		return array( 'type' => 'error', 'message' => $message );
	}

	$options = adrian_site_text_files_options();
	return array( 'type' => 'updated', 'message' => $options['ai']['last_message'] );
}

/**
 * Apply a generated starter template from the locked admin screen.
 *
 * The generator never enables an optional endpoint implicitly. For ads.txt
 * and app-ads.txt the starter is intentionally explanatory because valid
 * seller rows depend on the actual advertising partners of the site.
 *
 * @return array{type:string,message:string}|null
 */
function adrian_site_text_files_generator_admin_form() {
	if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return null;
	}

	$action = isset( $_POST['adrian_site_text_files_action'] ) && is_string( $_POST['adrian_site_text_files_action'] ) ? sanitize_key( wp_unslash( $_POST['adrian_site_text_files_action'] ) ) : '';
	if ( 'generate' !== $action ) {
		return null;
	}

	if ( ! adrian_site_text_files_can_manage() ) {
		return array( 'type' => 'error', 'message' => 'Zugriff verweigert.' );
	}

	check_admin_referer( 'adrian_site_text_files_generate' );
	$key         = isset( $_POST['generator_key'] ) && is_string( $_POST['generator_key'] ) ? sanitize_key( wp_unslash( $_POST['generator_key'] ) ) : '';
	$definitions = adrian_site_text_files_definitions();

	if ( ! isset( $definitions[ $key ] ) ) {
		return array( 'type' => 'error', 'message' => 'Für diese Auswahl gibt es keine Vorlage.' );
	}

	$content = (string) $definitions[ $key ]['default'];

	if ( 'security' === $key ) {
		$raw_contact = isset( $_POST['generator_contact'] ) && is_string( $_POST['generator_contact'] ) ? wp_unslash( $_POST['generator_contact'] ) : '';
		$contact     = sanitize_email( $raw_contact );

		if ( '' !== trim( $raw_contact ) && ! is_email( $contact ) ) {
			return array( 'type' => 'error', 'message' => 'Bitte eine gültige E-Mail-Adresse für security.txt eingeben.' );
		}

		if ( '' !== $contact ) {
			$content = preg_replace( '/^Contact:\s*.*$/mi', 'Contact: mailto:' . $contact, $content );
		}
	}

	$validation = adrian_site_text_files_validate_content( $key, $content );
	if ( is_wp_error( $validation ) ) {
		return array( 'type' => 'error', 'message' => $validation->get_error_message() );
	}

	$options = adrian_site_text_files_options();
	$enable  = ! empty( $_POST['generator_enable'] );

	// Optional endpoint templates stay disabled unless the owner explicitly
	// enables them. Even then, ads.txt needs real seller rows before use.
	$options['files'][ $key ]['content'] = adrian_site_text_files_sanitize_template( $content );
	if ( $enable && ! in_array( $key, array( 'ads', 'app_ads', 'tdmrep', 'ai', 'ai_json', 'opensearch' ), true ) ) {
		$options['files'][ $key ]['enabled'] = true;
	}

	update_option( ADRIAN_SITE_TEXT_FILES_OPTION, $options, false );
	adrian_site_text_files_invalidate_generated();

	$message = $definitions[ $key ]['label'] . ' wurde mit der sicheren Startvorlage vorbereitet.';
	if ( in_array( $key, array( 'ads', 'app_ads' ), true ) ) {
		$message .= ' Echte Verkäuferzeilen müssen vor einer Veröffentlichung ergänzt werden.';
	}

	return array( 'type' => 'updated', 'message' => $message );
}

/**
 * Save submitted templates for the locked user only.
 *
 * @return array{type:string,message:string}|null
 */
function adrian_site_text_files_save_admin_form() {
	if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return null;
	}

	$action = isset( $_POST['adrian_site_text_files_action'] ) && is_string( $_POST['adrian_site_text_files_action'] ) ? sanitize_key( wp_unslash( $_POST['adrian_site_text_files_action'] ) ) : '';
	if ( 'save' !== $action ) {
		return null;
	}

	if ( ! adrian_site_text_files_can_manage() ) {
		return array(
			'type'    => 'error',
			'message' => 'Zugriff verweigert.',
		);
	}

	$options  = adrian_site_text_files_options();
	$generated = $options['generated'];
	if ( isset( $_POST['generated'] ) && is_array( $_POST['generated'] ) ) {
		$submitted_generated           = wp_unslash( $_POST['generated'] );
		$generated['include_posts']    = ! empty( $submitted_generated['include_posts'] );
		$generated['include_pages']    = ! empty( $submitted_generated['include_pages'] );
		$generated['max_items']        = min( 100, max( 1, absint( $submitted_generated['max_items'] ?? 25 ) ) );
		$generated['max_chars']        = min( 250000, max( 10000, absint( $submitted_generated['max_chars'] ?? 100000 ) ) );
	}

	$robots = $options['robots'];
	if ( isset( $_POST['robots'] ) && is_array( $_POST['robots'] ) ) {
		$submitted_robots = wp_unslash( $_POST['robots'] );
		$robots['enabled'] = ! empty( $submitted_robots['enabled'] );
		$robots['lines']   = adrian_site_text_files_sanitize_template( $submitted_robots['lines'] ?? '' );
	}

	check_admin_referer( 'adrian_site_text_files_save' );

	$definitions = adrian_site_text_files_definitions();
	$submitted   = isset( $_POST['files'] ) && is_array( $_POST['files'] ) ? $_POST['files'] : array();
	$errors      = array();
	$next_files  = array();

	foreach ( $definitions as $key => $definition ) {
		$entry   = isset( $submitted[ $key ] ) && is_array( $submitted[ $key ] ) ? $submitted[ $key ] : array();
		$content = adrian_site_text_files_sanitize_template( isset( $entry['content'] ) ? $entry['content'] : '' );
		$enabled = ! empty( $entry['enabled'] );

		if ( $enabled && '' === trim( $content ) ) {
			$errors[] = $definition['label'] . ' darf im aktivierten Zustand nicht leer sein.';
		}

		$validation = adrian_site_text_files_validate_content( $key, $content );
		if ( is_wp_error( $validation ) ) {
			$errors[] = $validation->get_error_message();
		}

		$next_files[ $key ] = array(
			'enabled' => $enabled,
			'content' => $content,
		);
	}

	if ( ! empty( $errors ) ) {
		return array(
			'type'    => 'error',
			'message' => implode( ' ', $errors ),
		);
	}

	$options['files']     = $next_files;
	$options['generated'] = $generated;
	$options['robots']    = $robots;
	update_option( ADRIAN_SITE_TEXT_FILES_OPTION, $options, false );
	adrian_site_text_files_invalidate_generated();

	return array(
		'type'    => 'updated',
		'message' => 'Die Website-Standards wurden gespeichert. URLs, Jahreszahl, security.txt und der optionale LLM-Langkontext aktualisieren sich automatisch.',
	);
}

/**
 * Import a previously exported configuration from a pasted JSON document.
 *
 * @return array{type:string,message:string}|null
 */
function adrian_site_text_files_import_admin_form() {
	if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return null;
	}

	$action = isset( $_POST['adrian_site_text_files_action'] ) && is_string( $_POST['adrian_site_text_files_action'] ) ? sanitize_key( wp_unslash( $_POST['adrian_site_text_files_action'] ) ) : '';
	if ( 'import' !== $action ) {
		return null;
	}

	if ( ! adrian_site_text_files_can_manage() ) {
		return array( 'type' => 'error', 'message' => 'Zugriff verweigert.' );
	}

	check_admin_referer( 'adrian_site_text_files_import' );
	$raw = isset( $_POST['import_json'] ) && is_string( $_POST['import_json'] ) ? wp_unslash( $_POST['import_json'] ) : '';

	if ( strlen( $raw ) > 1000000 ) {
		return array( 'type' => 'error', 'message' => 'Der Import ist zu groß.' );
	}

	$import = json_decode( $raw, true );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $import ) ) {
		return array( 'type' => 'error', 'message' => 'Der Import enthält kein gültiges JSON.' );
	}

	$options     = adrian_site_text_files_options();
	$definitions = adrian_site_text_files_definitions();
	$files       = isset( $import['files'] ) && is_array( $import['files'] ) ? $import['files'] : array();
	$errors      = array();

	foreach ( $definitions as $key => $definition ) {
		if ( ! isset( $files[ $key ] ) || ! is_array( $files[ $key ] ) ) {
			continue;
		}

		$content = isset( $files[ $key ]['content'] ) && is_string( $files[ $key ]['content'] ) ? adrian_site_text_files_sanitize_template( wp_slash( $files[ $key ]['content'] ) ) : $options['files'][ $key ]['content'];
		$enabled = ! empty( $files[ $key ]['enabled'] );
		$check   = adrian_site_text_files_validate_content( $key, $content );

		if ( $enabled && '' === trim( $content ) ) {
			$errors[] = $definition['label'] . ' darf im aktivierten Zustand nicht leer sein.';
			continue;
		}

		if ( is_wp_error( $check ) ) {
			$errors[] = $check->get_error_message();
			continue;
		}

		$options['files'][ $key ] = array( 'enabled' => $enabled, 'content' => $content );
	}

	if ( ! empty( $import['generated'] ) && is_array( $import['generated'] ) ) {
		$options['generated']['include_posts'] = ! empty( $import['generated']['include_posts'] );
		$options['generated']['include_pages'] = ! empty( $import['generated']['include_pages'] );
		$options['generated']['max_items']     = min( 100, max( 1, absint( $import['generated']['max_items'] ?? 25 ) ) );
		$options['generated']['max_chars']     = min( 250000, max( 10000, absint( $import['generated']['max_chars'] ?? 100000 ) ) );
	}

	if ( ! empty( $import['robots'] ) && is_array( $import['robots'] ) ) {
		$options['robots']['enabled'] = ! empty( $import['robots']['enabled'] );
		$options['robots']['lines']   = isset( $import['robots']['lines'] ) && is_string( $import['robots']['lines'] ) ? adrian_site_text_files_sanitize_template( wp_slash( $import['robots']['lines'] ) ) : '';
	}

	if ( ! empty( $errors ) ) {
		return array( 'type' => 'error', 'message' => implode( ' ', $errors ) );
	}

	update_option( ADRIAN_SITE_TEXT_FILES_OPTION, $options, false );
	adrian_site_text_files_invalidate_generated();

	return array( 'type' => 'updated', 'message' => 'Die Konfiguration wurde importiert. Der gesperrte Nutzer wurde nicht verändert.' );
}

/**
 * Download the current configuration as a JSON backup.
 *
 * @return void
 */
function adrian_site_text_files_export() {
	if ( ! adrian_site_text_files_can_manage() ) {
		wp_die( esc_html__( 'Du hast keine Berechtigung für diesen Export.', 'adrian-site-text-files' ), 403 );
	}

	check_admin_referer( 'adrian_site_text_files_export' );
	$options = adrian_site_text_files_options();
	unset( $options['allowed_user_id'] );
	$ai_export = array(
		'enabled'         => $options['ai']['enabled'],
		'provider'        => $options['ai']['provider'],
		'auto_publish'    => $options['ai']['auto_publish'],
		'max_input_chars' => $options['ai']['max_input_chars'],
	);

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="website-standards-export.json"' );
	echo wp_json_encode(
		array(
			'plugin_version' => ADRIAN_SITE_TEXT_FILES_VERSION,
			'exported_at'    => gmdate( 'c' ),
			'files'          => $options['files'],
			'generated'      => $options['generated'],
			'robots'         => $options['robots'],
			'ai'             => $ai_export,
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	exit;
}
add_action( 'admin_post_adrian_site_text_files_export', 'adrian_site_text_files_export' );

/**
 * Render the private admin page.
 *
 * @return void
 */
function adrian_site_text_files_render_admin_page() {
	if ( ! adrian_site_text_files_can_manage() ) {
		wp_die( esc_html__( 'Du hast keine Berechtigung für diese Seite.', 'adrian-site-text-files' ), 403 );
	}

	$notice      = adrian_site_text_files_ai_save_admin_form();
	if ( ! is_array( $notice ) ) {
		$notice = adrian_site_text_files_ai_run_admin_form();
	}
	if ( ! is_array( $notice ) ) {
		$notice = adrian_site_text_files_import_admin_form();
	}
	if ( ! is_array( $notice ) ) {
		$notice = adrian_site_text_files_save_admin_form();
	}
	$definitions = adrian_site_text_files_definitions();
	$options     = adrian_site_text_files_options();
	$validation  = adrian_site_text_files_validation();
	$ai_status   = adrian_site_text_files_ai_status();
	$export_url  = wp_nonce_url( admin_url( 'admin-post.php?action=adrian_site_text_files_export' ), 'adrian_site_text_files_export' );
	$allowed_user = get_user_by( 'id', (int) $options['allowed_user_id'] );
	?>
	<div class="wrap adrian-stf-shell">
		<h1><?php echo esc_html__( 'Website-Standards', 'adrian-site-text-files' ); ?></h1>
		<p class="adrian-stf-intro">Ein zentraler Manager für kleine, maschinenlesbare Website-Standards. Die Seite ist ausschließlich für den festgelegten WordPress-Administrator freigeschaltet.</p>
		<?php if ( is_array( $notice ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
		<?php endif; ?>
		<p class="adrian-stf-meta"><strong>Gesperrter Nutzer:</strong> <?php echo $allowed_user ? esc_html( $allowed_user->user_login ) : esc_html__( 'Noch nicht festgelegt', 'adrian-site-text-files' ); ?></p>
		<p class="adrian-stf-help">Verfügbare Platzhalter: <code>{site_url}</code>, <code>{site_name}</code>, <code>{year}</code>, <code>{security_expires}</code> und <code>{llms_full}</code>. Sie werden erst bei der Ausgabe ersetzt; dadurch bleiben die Dateien aktuell, ohne deine Texte zu überschreiben.</p>
		<div class="adrian-stf-summary" aria-label="Prüfzusammenfassung">
			<span><strong><?php echo esc_html( $validation['valid'] ); ?></strong> gültig</span>
			<span><strong><?php echo esc_html( $validation['warnings'] ); ?></strong> Hinweise</span>
			<span><strong><?php echo esc_html( $validation['errors'] ); ?></strong> Fehler</span>
			<a class="button button-secondary" href="<?php echo esc_url( $export_url ); ?>">Konfiguration exportieren</a>
		</div>
		<div class="notice notice-info inline">
			<p><strong>KI-Privatsphäre:</strong> Die KI-Funktion ist standardmäßig deaktiviert. Ohne deine ausdrückliche Aktivierung und die WordPress-Connector-Freigabe werden keine Website-Inhalte nach außen übertragen. Seiten, Entwürfe, Medien, rechtliche Seiten sowie erkannte E-Mail-Adressen und Telefonnummern bleiben beim automatischen Lauf außen vor.</p>
		</div>

		<?php $ai = $options['ai']; ?>
		<section class="adrian-stf-card adrian-stf-card--assistant" aria-labelledby="adrian-stf-ai-title">
			<h2 id="adrian-stf-ai-title">Optionale KI-Aktualisierung</h2>
			<p>WordPress nutzt dafür die offizielle AI-Client-Schnittstelle. Der stündliche Lauf erstellt nur einen neuen Vorschlag für <code>llms.txt</code>. Sichtbare Seiten, Beiträge, Theme-Dateien und Einstellungen werden niemals automatisch verändert.</p>
			<p class="adrian-stf-meta"><strong>API:</strong> <?php echo $ai_status['available'] ? esc_html__( 'verfügbar', 'adrian-site-text-files' ) : esc_html__( 'nicht verfügbar', 'adrian-site-text-files' ); ?> · <strong>Connector:</strong> <code><?php echo esc_html( $ai_status['provider'] ); ?></code> · <strong>Zugang:</strong> <?php echo $ai_status['configured'] ? esc_html__( 'konfiguriert', 'adrian-site-text-files' ) : esc_html__( 'nicht erkannt', 'adrian-site-text-files' ); ?></p>
			<p class="adrian-stf-help">Falls WordPress die Anfrage blockiert, musst du einmalig unter <a href="<?php echo esc_url( $ai_status['approval_url'] ); ?>">Tools → Connector Approvals</a> das Plugin <code>adrian-site-text-files</code> für den Connector freigeben. Die Freigabe bleibt eine WordPress-Sicherheitsentscheidung und wird nicht von diesem Plugin umgangen.</p>
			<form method="post">
				<?php wp_nonce_field( 'adrian_site_text_files_ai_save' ); ?>
				<input type="hidden" name="adrian_site_text_files_action" value="ai_save">
				<div class="adrian-stf-grid">
					<div class="adrian-stf-field">
						<label for="adrian-stf-ai-provider">Connector-ID</label>
						<select id="adrian-stf-ai-provider" name="ai[provider]">
							<option value="google" <?php selected( 'google', $ai['provider'] ); ?>>Google / Gemini</option>
							<option value="auto" <?php selected( 'auto', $ai['provider'] ); ?>>WordPress automatisch auswählen</option>
						</select>
					</div>
					<div class="adrian-stf-field">
						<label for="adrian-stf-ai-max-input">Maximale Eingabezeichen</label>
						<input id="adrian-stf-ai-max-input" type="number" min="10000" max="100000" step="1000" name="ai[max_input_chars]" value="<?php echo esc_attr( $ai['max_input_chars'] ); ?>">
					</div>
				</div>
				<p><label><input type="checkbox" name="ai[enabled]" value="1" <?php checked( $ai['enabled'] ); ?>> Stündliche Aktualisierung aktivieren</label></p>
				<p><label><input type="checkbox" name="ai[auto_publish]" value="1" <?php checked( $ai['auto_publish'] ); ?>> Gültige Vorschläge automatisch in die öffentliche <code>llms.txt</code> übernehmen</label></p>
				<p class="adrian-stf-help"><label><input type="checkbox" name="ai_confirm" value="1"> Ich bestätige, dass ausgewählte veröffentlichte Inhalte an den gewählten KI-Connector übertragen werden dürfen.</label></p>
				<div class="adrian-stf-actions">
					<?php submit_button( 'KI-Einstellungen speichern', 'primary', 'submit', false ); ?>
					<span class="adrian-stf-help">Standardmäßig bleibt die automatische Veröffentlichung ausgeschaltet.</span>
				</div>
			</form>
			<hr class="adrian-stf-divider">
			<p><strong>Letzter Lauf:</strong> <?php echo $ai['last_run'] ? esc_html( wp_date( 'd.m.Y H:i', $ai['last_run'] ) ) : esc_html__( 'noch nicht ausgeführt', 'adrian-site-text-files' ); ?> · <strong>Status:</strong> <?php echo esc_html( $ai['last_status'] ); ?><?php if ( '' !== $ai['last_message'] ) : ?> · <?php echo esc_html( $ai['last_message'] ); ?><?php endif; ?></p>
			<form method="post">
				<?php wp_nonce_field( 'adrian_site_text_files_ai_run' ); ?>
				<input type="hidden" name="adrian_site_text_files_action" value="ai_run">
				<?php submit_button( 'Einmalig prüfen und Vorschlag erzeugen', 'secondary', 'submit', false ); ?>
				<span class="adrian-stf-help">Manuelle Läufe unterliegen einem Limit von 5 pro Minute und 50 pro Stunde.</span>
			</form>
			<?php if ( '' !== $ai['draft'] ) : ?>
				<label for="adrian-stf-ai-draft"><strong>Letzter KI-Vorschlag (nur Vorschau)</strong></label>
				<textarea id="adrian-stf-ai-draft" class="adrian-stf-code" rows="10" readonly spellcheck="false"><?php echo esc_textarea( $ai['draft'] ); ?></textarea>
			<?php endif; ?>
		</section>

		<section class="adrian-stf-card adrian-stf-card--assistant" aria-labelledby="adrian-stf-generator-title">
			<h2 id="adrian-stf-generator-title">Vorlagen-Assistent</h2>
			<p>Wähle eine Datei aus, übernimm die geprüfte Startvorlage und passe sie danach im Editor an. Das Anwenden schaltet optionale Endpunkte nicht automatisch frei.</p>
			<form method="post">
				<?php wp_nonce_field( 'adrian_site_text_files_generate' ); ?>
				<input type="hidden" name="adrian_site_text_files_action" value="generate">
				<div class="adrian-stf-grid">
					<div class="adrian-stf-field">
						<label for="adrian-stf-generator-key">Datei</label>
						<select id="adrian-stf-generator-key" name="generator_key">
							<?php foreach ( $definitions as $generator_key => $generator_definition ) : ?>
								<option value="<?php echo esc_attr( $generator_key ); ?>"><?php echo esc_html( $generator_definition['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="adrian-stf-field">
						<label for="adrian-stf-generator-contact">Security-Kontakt <span class="adrian-stf-help">(optional)</span></label>
						<input id="adrian-stf-generator-contact" type="email" name="generator_contact" placeholder="security@example.org" autocomplete="email">
					</div>
				</div>
				<p class="adrian-stf-help"><label><input type="checkbox" name="generator_enable" value="1"> Nach dem Anwenden veröffentlichen, sofern es sich nicht um einen optionalen Experiment- oder Werbe-Endpunkt handelt.</label></p>
				<div class="adrian-stf-actions">
					<?php submit_button( 'Startvorlage übernehmen', 'primary', 'submit', false ); ?>
					<span class="adrian-stf-help">Bei <code>ads.txt</code> und <code>app-ads.txt</code> sind echte Verkäuferzeilen erforderlich; dafür gibt es keine allgemeingültige Website-Vorlage.</span>
				</div>
			</form>
		</section>

		<form method="post">
			<?php wp_nonce_field( 'adrian_site_text_files_save' ); ?>
			<input type="hidden" name="adrian_site_text_files_action" value="save">
			<?php foreach ( $definitions as $key => $definition ) : ?>
				<?php $file = $options['files'][ $key ]; ?>
				<section class="adrian-stf-card">
					<h2 style="margin-top: 0;"><?php echo esc_html( $definition['label'] ); ?></h2>
					<p class="adrian-stf-meta"><code><?php echo esc_html( $definition['path'] ); ?></code> · <?php echo esc_html( $definition['format'] ); ?></p>
					<p><?php echo esc_html( $definition['description'] ); ?></p>
					<?php if ( isset( $validation['results'][ $key ] ) ) : ?>
						<p class="adrian-stf-status adrian-stf-status-<?php echo esc_attr( sanitize_html_class( $validation['results'][ $key ]['status'] ) ); ?>"><strong>Status:</strong> <?php echo esc_html( $validation['results'][ $key ]['status'] ); ?> – <?php echo esc_html( $validation['results'][ $key ]['message'] ); ?></p>
					<?php endif; ?>
					<p>
						<label>
							<input type="checkbox" name="files[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $file['enabled'] ); ?>>
							Veröffentlichen
						</label>
						<?php if ( $file['enabled'] ) : ?>
							· <a href="<?php echo esc_url( home_url( ltrim( $definition['path'], '/' ) ) ); ?>" target="_blank" rel="noopener noreferrer">Endpunkt öffnen</a>
						<?php endif; ?>
					</p>
						<textarea class="adrian-stf-code" name="files[<?php echo esc_attr( $key ); ?>][content]" rows="12" spellcheck="false"><?php echo esc_textarea( $file['content'] ); ?></textarea>
					</section>
			<?php endforeach; ?>
			<section class="adrian-stf-card">
				<h2 style="margin-top: 0;">LLMs-full automatisch erzeugen</h2>
				<p>Der Langkontext wird nur aus bereits veröffentlichten Inhalten gebaut und nach Änderungen automatisch neu erzeugt. Entwürfe, Papierkorb und Medien werden nicht einbezogen.</p>
					<p><label><input type="checkbox" name="generated[include_posts]" value="1" <?php checked( $options['generated']['include_posts'] ); ?>> Beiträge einbeziehen</label></p>
				<p><label><input type="checkbox" name="generated[include_pages]" value="1" <?php checked( $options['generated']['include_pages'] ); ?>> Seiten einbeziehen (einschließlich rechtlicher Seiten nur nach deiner ausdrücklichen Auswahl)</label></p>
				<p><label>Maximale Anzahl Inhalte <input type="number" min="1" max="100" name="generated[max_items]" value="<?php echo esc_attr( $options['generated']['max_items'] ); ?>"></label>
				<label style="margin-left: 1rem;">Maximale Zeichen <input type="number" min="10000" max="250000" step="1000" name="generated[max_chars]" value="<?php echo esc_attr( $options['generated']['max_chars'] ); ?>"></label></p>
			</section>
			<section class="adrian-stf-card">
				<h2 style="margin-top: 0;">robots.txt erweitern</h2>
				<p>Die WordPress-Core-Ausgabe wird nicht ersetzt. Eingetragene Zeilen werden nur angehängt. Die Sitemap wird von WordPress bereits automatisch ergänzt.</p>
				<p><label><input type="checkbox" name="robots[enabled]" value="1" <?php checked( $options['robots']['enabled'] ); ?>> Erweiterung veröffentlichen</label></p>
				<textarea class="adrian-stf-code" name="robots[lines]" rows="6" spellcheck="false"><?php echo esc_textarea( $options['robots']['lines'] ); ?></textarea>
			</section>
			<?php submit_button( 'Textdateien speichern' ); ?>
		</form>

		<section class="adrian-stf-card">
			<h2 style="margin-top: 0;">Konfiguration importieren</h2>
			<p>Importe ändern niemals den gesperrten Benutzer. Ungültiges JSON oder ungültige strukturierte Dateien werden vollständig abgewiesen.</p>
			<form method="post">
				<?php wp_nonce_field( 'adrian_site_text_files_import' ); ?>
				<input type="hidden" name="adrian_site_text_files_action" value="import">
				<textarea class="adrian-stf-code" name="import_json" rows="8" placeholder="Hier einen Export einfügen …" spellcheck="false"></textarea>
				<?php submit_button( 'Konfiguration importieren', 'secondary', 'submit', false ); ?>
			</form>
		</section>
	</div>
	<?php
}
