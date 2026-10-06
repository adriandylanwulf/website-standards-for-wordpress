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
- geschützter Endpunkt-Test für aktivierte Dateien mit Status-, Content-Type- und Cache-Header-Prüfung
- Ablaufprüfung für `security.txt`, damit kein abgelaufenes Meldungsziel veröffentlicht wird
- optionaler KI-Vorschlagsgenerator für ausgewählte Dateien mit lokaler Prüfung und manueller Übernahme
- experimentelle `ai-safety.txt`-Vorlage, standardmäßig deaktiviert und ausdrücklich als Entwurf gekennzeichnet
- offizielle WordPress-AI-Client-Anbindung für einen optionalen, stündlichen `llms.txt`-Vorschlag
- sparsames Gemini-/Freitier-Profil mit kleinerem Quellenumfang und kürzerer Ausgabe
- Connector-Hook mit 5 Anfragen pro Minute und 50 pro Stunde je Benutzer

Das Plugin schreibt keine Dateien in das Webroot. Die Endpunkte werden virtuell über WordPress ausgeliefert. Inhalte werden nur aus bereits veröffentlichten Beiträgen und Seiten erzeugt, wenn diese Option im geschützten Backend aktiviert wurde.

## Sicherheit

Der Editor ist an einen einzelnen WordPress-Administrator gebunden. Änderungen benötigen zusätzlich eine passende Capability und einen WordPress-Nonce. Eingaben sind begrenzt, strukturierte Inhalte werden validiert und öffentliche Antworten werden mit passenden MIME-Typen und `X-Content-Type-Options: nosniff` ausgeliefert.

Der Vorlagen-Assistent übernimmt nur lokale Startwerte. Werbeformate wie
`ads.txt` und `app-ads.txt` bleiben deaktiviert, weil gültige Verkäuferzeilen
von den tatsächlich eingesetzten Werbepartnern abhängen. Es gibt dafür keine
seriöse allgemeingültige Standardeinstellung.

Die KI-Funktion ist standardmäßig deaktiviert. Sie nutzt ausschließlich die offizielle
WordPress-AI-Client-Schnittstelle und einen im WordPress-Backend konfigurierten
Connector, ohne API-Schlüssel selbst auszulesen. Automatische Läufe berücksichtigen
nur veröffentlichte Inhalte, schließen Entwürfe, Medien und rechtliche Seiten aus und
redigieren erkannte E-Mail-Adressen sowie Telefonnummern. Standardmäßig wird nur ein
Vorschlag gespeichert; die automatische Veröffentlichung ist eine separate, explizite
Option und betrifft ausschließlich `llms.txt`. `llms-full.txt` kann unabhängig von KI
aus veröffentlichten Inhalten neu aufgebaut werden. Beiträge und Seiten selbst werden
nicht automatisch umgeschrieben.

Eine gespeicherte Datenfreigabe ist zusätzlich erforderlich. Fehlt sie, bleiben manuelle
und automatische KI-Anfragen blockiert. Sie kann für einen einzelnen, manuell geprüften
Dateivorschlag erteilt werden, ohne die stündliche Automatik zu aktivieren. Importe
übernehmen diese Freigabe niemals.

Das optionale Gemini-/Freitier-Profil reduziert den Quellenumfang auf höchstens 20.000
Zeichen und die Ausgabe auf 1.400 Tokens. Es aktiviert keinen Zugang, ändert keine
Abrechnung und hebt keine Anbieterlimits auf.

Ist die WordPress-Experimentfunktion „Connector-Freigabe“ aktiv, muss der Administrator
`adrian-site-text-files` unter `Tools → Connector Approvals` für den gewünschten
Connector freigeben. Das Plugin umgeht diese Sperre nicht. Die stündliche Ausführung
setzt außerdem einen funktionierenden WordPress-/Server-Cron voraus.

## Installation

1. Den Plugin-Ordner `adrian-site-text-files` nach `wp-content/plugins/` kopieren.
2. Das Plugin in WordPress aktivieren.
3. Unter `Einstellungen → Website-Standards` die gewünschten Endpunkte prüfen und veröffentlichen.

Experimentelle Formate und Werbedateien bleiben standardmäßig deaktiviert.

