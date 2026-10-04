# Website Standards for WordPress

Ein schlanker WordPress-Manager für maschinenlesbare Website-Standards.

Die aktuelle Fassung enthält einen geschützten Vorlagen-Assistenten. Damit
können Startvorlagen direkt im WordPress-Backend vorbereitet werden, ohne
optionale Endpunkte automatisch zu veröffentlichen.

## Enthalten

- `security.txt` nach RFC 9116 als virtueller Endpoint
- `llms.txt` und optional automatisch erzeugtes `llms-full.txt`
- `humans.txt`
- `manifest.webmanifest`
- optionale Erweiterung der WordPress-Core-Ausgabe von `robots.txt`
- vorbereitete, standardmäßig deaktivierte Endpunkte für `ai.txt`, `ai.json`, `tdmrep.json`, `ads.txt`, `app-ads.txt` und `opensearch.xml`
- lokale Konfigurationsprüfung, Konflikthinweise sowie JSON-Export und -Import
- geschützter Vorlagen-Assistent für neue oder zurückgesetzte Dateien
- vorbereiteter Connector-Hook mit 5 Anfragen pro Minute und 50 pro Stunde je Benutzer

Das Plugin schreibt keine Dateien in das Webroot. Die Endpunkte werden virtuell über WordPress ausgeliefert. Inhalte werden nur aus bereits veröffentlichten Beiträgen und Seiten erzeugt, wenn diese Option im geschützten Backend aktiviert wurde.

## Sicherheit

Der Editor ist an einen einzelnen WordPress-Administrator gebunden. Änderungen benötigen zusätzlich eine passende Capability und einen WordPress-Nonce. Eingaben sind begrenzt, strukturierte Inhalte werden validiert und öffentliche Antworten werden mit passenden MIME-Typen und `X-Content-Type-Options: nosniff` ausgeliefert.

Der Vorlagen-Assistent übernimmt nur lokale Startwerte. Werbeformate wie
`ads.txt` und `app-ads.txt` bleiben deaktiviert, weil gültige Verkäuferzeilen
von den tatsächlich eingesetzten Werbepartnern abhängen. Es gibt dafür keine
seriöse allgemeingültige Standardeinstellung.

Das Plugin ruft selbst keinen externen KI-Dienst auf. Die optionale Connector-Schnittstelle bleibt ohne ausdrücklich angeschlossene Integration inaktiv.

## Installation

1. Den Plugin-Ordner `adrian-site-text-files` nach `wp-content/plugins/` kopieren.
2. Das Plugin in WordPress aktivieren.
3. Unter `Einstellungen → Website-Standards` die gewünschten Endpunkte prüfen und veröffentlichen.

Experimentelle Formate und Werbedateien bleiben standardmäßig deaktiviert.
