# Adrian Site Cache 1.1.1

Dieses Update überarbeitet ausschließlich die Administrationsoberfläche des eigenständigen Datei-Caches.

## Was sich ändert

- Die Seite hat eine klarere Kopfzeile mit sichtbarem Cache-Status.
- Cache-Weg, Dateianzahl und Cache-Größe sind als kompakte Übersicht sofort erfassbar.
- Betrieb, Cache-Modus und Wartung sind räumlich und sprachlich sauber getrennt.
- Der aktuell ausgewählte Modus wird in der Übersicht hervorgehoben.
- Die Wartungsdaten sind auf Desktop und Mobilgeräten besser lesbar.
- Es werden weiterhin keine externen Schriftarten, JavaScript-Bibliotheken oder Tracking-Dienste geladen.

Die Cache-Logik, Sicherheitsprüfungen, Nonces, Ausschlüsse und Cron-Verarbeitung bleiben unverändert.

## Sicherheit und Betrieb

Die neue Oberfläche ändert keine Cache-Regeln. Die frühe Auslieferung bleibt standardmäßig deaktiviert. Vor einer Aktivierung sollten Startseite, Blog, Formulare, Login, Datenschutz, Impressum und mobile Ansichten kontrolliert werden.

## Prüfung

- PHP-Syntax der Plugin-Dateien geprüft.
- ZIP-Archiv auf Dateiintegrität geprüft.
- Version 1.1.1 ist als Update für die bestehende Version 1.1.0 vorgesehen.
