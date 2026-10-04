# Adrian Site Cache

Ein bewusst kleines WordPress-Plugin für die persönliche Website von Adrian Dylan Wulf.

Aktuelle Version: 1.1.2

## Konzept

Das Plugin arbeitet eigenständig als schlanker Datei-Cache. Es benötigt kein zusätzliches Full-Page-Cache-Plugin und verwaltet seine Cache-Dateien, GZIP-Varianten, Begrenzungen und Leerungen selbst.

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
- vier verständliche Cache-Modi: Kaum Cache, Leicht, Normal und Stark, jeweils mit erklärten Vor- und Nachteilen;
- WP-CLI: `wp adrian-cache status`, `wp adrian-cache purge`, `wp adrian-cache gc`;
- Anzeige des letzten registrierten externen Cronlaufs, wenn der interne WordPress-Cron deaktiviert ist;
- keine externen Schriftarten, JavaScript-Bibliotheken oder Tracking-Funktionen.
- Die Statusanzeige zählt nur echte HTML-Cache-Einträge und berücksichtigt deren GZIP-Varianten.
- Der normale Datei-Cache liefert vorhandene GZIP-Varianten direkt aus, wenn der Browser sie unterstützt.

## Betrieb

Der eigene Datei-Cache ist der einzige Cache-Weg. Die frühe Auslieferung über `advanced-cache.php` bleibt zunächst deaktiviert. Sie wird erst nach einem kontrollierten Test von Startseite, Blog, Kontaktformular, Login, Datenschutz, Impressum und mobilen Ansichten aktiviert. Für diese frühe Stufe muss `WP_CACHE` in der WordPress-Konfiguration gesetzt sein.

## Wartung bei Mittwald

Das Plugin registriert einen stündlichen WordPress-Cronjob für die Bereinigung. Wenn Mittwald den Endpunkt `/html/adrian-dylan-wulf/wp-cron.php` alle fünf Minuten aufruft, werden fällige WordPress-Aufgaben automatisch ausgeführt. Die fünf Minuten sind das Prüfintervall des Server-Cronjobs; die eigentliche Cache-Bereinigung bleibt auf den registrierten stündlichen Termin begrenzt. Wenn `DISABLE_WP_CRON` gesetzt ist, zeigt die Administrationsseite den zuletzt registrierten externen Cronlauf an, statt den externen Betrieb pauschal als Fehler zu melden.

Der Modus „Normal“ ist für diese Website der empfohlene Ausgangspunkt. „Stark“ erhöht die Lebensdauer des Caches, schaltet aber niemals automatisch die frühe Drop-in-Auslieferung ein.
