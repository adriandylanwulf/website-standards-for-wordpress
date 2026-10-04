# Adrian Site Cache

Ein bewusst kleines WordPress-Plugin für die persönliche Website von Adrian Dylan Wulf.

Aktuelle Version: 1.0.6

## Konzept

Das Plugin vermeidet standardmäßig einen zweiten Full-Page-Cache. Wenn WP Super Cache aktiv ist, steuert es dessen Leerung bei Inhalts-, Theme- und wichtigen Website-Änderungen. Das ist für diese Website der sichere und performante Standard.

Optional enthält das Plugin einen eigenen Datei-Cache als vollständige Ablösung. Er kann nach einer kontrollierten Umschaltung den bisherigen Full-Page-Cache ersetzen und liefert Treffer bereits über den `advanced-cache.php`-Drop-in aus, also bevor Theme und die meisten Plugins geladen werden.

## Funktionen

- sichere Cache-Leerung bei Änderungen an Beiträgen, Seiten, Menüs und Theme;
- eigener Datei-Cache für öffentliche, parameterlose GET-Seiten;
- optionale frühe Auslieferung über einen WordPress-Cache-Drop-in; standardmäßig ausgeschaltet;
- optionale GZIP-Dateien für schnelle Treffer bei komprimierungsfähigen Browsern;
- synchroner Datei- und Speichergrenzwert, damit die Cache-Größe nicht erst beim Cronlauf begrenzt wird;
- Ausschluss von Login, Backend, REST, Feeds, Sitemaps, Suche, Vorschau und eingeloggten Nutzern;
- 404-Antworten werden nicht gespeichert; der native Modus ist auf Single-Site begrenzt;
- atomare Dateischreibung und begrenzte Cache-Größe;
- Administrator-Seite mit Status, Größe, TTL und manueller Wartung;
- WP-CLI: `wp adrian-cache status`, `wp adrian-cache purge`, `wp adrian-cache gc`;
- keine externen Schriftarten, JavaScript-Bibliotheken oder Tracking-Funktionen.

## Betrieb

Auf dieser Website bleibt zunächst „WP Super Cache steuern“ aktiviert. Für die Ablösung wird zuerst ein Testfenster mit dem eigenen Datei-Cache durchgeführt: WP Super Cache deaktivieren, eigenen Cache aktivieren, Startseite, Blog, Kontaktformular, Login, Datenschutz, Impressum und mobile Ansichten prüfen und anschließend Cache-Hit/Miss sowie Formularfunktion testen. Die frühe Auslieferung wird erst danach bewusst eingeschaltet.
