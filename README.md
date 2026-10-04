# Website Standards for WordPress

Ein schlanker WordPress-Manager für maschinenlesbare Website-Standards.

## Enthalten

- `security.txt` nach RFC 9116 als virtueller Endpoint
- `llms.txt` und optional automatisch erzeugtes `llms-full.txt`
- `humans.txt`
- `manifest.webmanifest`
- optionale Erweiterung der WordPress-Core-Ausgabe von `robots.txt`
- vorbereitete, standardmäßig deaktivierte Endpunkte für `ai.txt`, `ai.json`, `tdmrep.json`, `ads.txt`, `app-ads.txt` und `opensearch.xml`
- lokale Konfigurationsprüfung, Konflikthinweise sowie JSON-Export und -Import
- vorbereiteter Connector-Hook mit 5 Anfragen pro Minute und 50 pro Stunde je Benutzer

Das Plugin schreibt keine Dateien in das Webroot. Die Endpunkte werden virtuell über WordPress ausgeliefert. Inhalte werden nur aus bereits veröffentlichten Beiträgen und Seiten erzeugt, wenn diese Option im geschützten Backend aktiviert wurde.

## Sicherheit

Der Editor ist an einen einzelnen WordPress-Administrator gebunden. Änderungen benötigen zusätzlich eine passende Capability und einen WordPress-Nonce. Eingaben sind begrenzt, strukturierte Inhalte werden validiert und öffentliche Antworten werden mit passenden MIME-Typen und `X-Content-Type-Options: nosniff` ausgeliefert.

Das Plugin ruft selbst keinen externen KI-Dienst auf. Die optionale Connector-Schnittstelle bleibt ohne ausdrücklich angeschlossene Integration inaktiv.

## Installation

1. Den Plugin-Ordner `adrian-site-text-files` nach `wp-content/plugins/` kopieren.
2. Das Plugin in WordPress aktivieren.
3. Unter `Einstellungen → Website-Standards` die gewünschten Endpunkte prüfen und veröffentlichen.

Experimentelle Formate und Werbedateien bleiben standardmäßig deaktiviert.

