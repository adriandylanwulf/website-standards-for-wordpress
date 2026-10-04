# Adrian Site Cache 1.1.2

Dieses Update verfeinert die Administrationsoberfläche und verbessert die Ausgabe des normalen Datei-Caches.

## Gestaltung

- Die Oberfläche verwendet weniger abgerundete Flächen und weniger Schatten.
- Cache-Modi erscheinen als ruhige, native Aufklappzeilen statt als vier große Karten.
- Der aktive Modus wird direkt markiert; weitere Erklärungen bleiben auf Wunsch aufklappbar.
- Die Wartungsübersicht zeigt Leerung, nächsten Termin und externen Cronlauf in einer gemeinsamen Zeile.
- Die Darstellung bleibt ohne externe Schriftarten, JavaScript-Bibliotheken oder Tracking.

## Cache und Sicherheit

- Die Statusanzeige zählt nur echte HTML-Cache-Einträge und die zugehörigen GZIP-Dateien.
- Der normale Cache liefert GZIP-Dateien aus, wenn der Browser dies unterstützt.
- Die Cache-Regeln, Ausschlüsse, Nonces, Berechtigungen und Drop-in-Sicherheitsprüfungen bleiben erhalten.
- Ein vollständiger statischer Sicherheitscheck des Plugin-Quellstands ergab keine kritischen, hohen oder mittleren Befunde.
