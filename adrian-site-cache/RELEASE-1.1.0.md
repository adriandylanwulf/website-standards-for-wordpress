# Adrian Site Cache 1.1.0

## Eigenständiger Cache-Betrieb

Version 1.1.0 ist für Installationen ohne WP Super Cache ausgelegt. Der eigene Datei-Cache ist jetzt der einzige Cache-Weg und wird bei bestehenden Installationen automatisch als native Strategie übernommen.

Die Administrationsseite zeigt nicht mehr den nicht vorhandenen WP-Super-Cache-Weg an. Stattdessen erklärt sie getrennt:

- den nächsten internen Bereinigungstermin des Plugins;
- den externen Cronbetrieb bei deaktiviertem WordPress-Cron;
- den zuletzt registrierten Cronlauf;
- die Voraussetzungen für die optionale frühe Auslieferung.

## Wartung

Der Mittwald-Cronjob kann weiterhin alle fünf Minuten `/html/adrian-dylan-wulf/wp-cron.php` ausführen. Das Plugin speichert bei einer tatsächlichen Cronausführung einen Zeitstempel. Dadurch wird die Situation „interner Cron deaktiviert, externer Cron aktiv“ nicht mehr als pauschaler Fehler dargestellt.

## Sicherheit

Beim Update werden alte native Cache-Dateien verworfen. Ein eigenes `advanced-cache.php`-Drop-in wird nur bei aktivierter früher Auslieferung verwendet und bei deren Deaktivierung entfernt. Die bestehende Prüfung auf öffentliche GET-Seiten, Cookies, Query-Strings, Login-/API-Pfade, Host und TTL bleibt erhalten.
