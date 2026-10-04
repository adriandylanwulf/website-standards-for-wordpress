# Adrian Site Cache 1.0.7

## Wartungs- und Sicherheitsupdate

Version 1.0.7 ergänzt vier verständliche Cache-Modi:

- **Kaum Cache:** 60 Sekunden, möglichst frische Inhalte.
- **Leicht:** 5 Minuten, kleine Entlastung bei gelegentlichen Änderungen.
- **Normal:** 15 Minuten, empfohlene Einstellung für eine persönliche Website.
- **Stark:** 60 Minuten, maximale Entlastung für überwiegend statische Inhalte.

Die Auswahl zeigt direkt im WordPress-Backend die jeweiligen Vorteile und Nachteile. Der Modus „Stark“ aktiviert die frühe Drop-in-Auslieferung nicht automatisch.

Zusätzlich wurden alte Drop-in-Konfigurationen beim Update abgesichert, Hosts und Multisite-Konfigurationen im frühen Pfad strenger geprüft, WP-Super-Cache-Wechsel berücksichtigt sowie Cache-Schreibvorgänge und Bereinigungen gegen parallele Zugriffe geschützt. Dateien werden atomar geschrieben.

## Wartung bei Mittwald

Das Plugin registriert die Bereinigung stündlich in WordPress. Ist WordPress-Cron deaktiviert, muss Mittwald den Endpunkt `/html/adrian-dylan-wulf/wp-cron.php` regelmäßig aufrufen. Ein Server-Cronjob alle fünf Minuten ist dafür geeignet: Er stößt fällige WordPress-Aufgaben an, führt die Bereinigung aber höchstens zum registrierten stündlichen Termin aus.

## Prüfung

- PHP-Syntaxprüfung von Plugin und Drop-in auf der Mittwald-Umgebung: erfolgreich.
- ZIP-Archivtest: erfolgreich.
- Vollständiger statischer Codex-Sicherheitscheck: keine kritischen, hohen oder mittleren Befunde; zwei niedrige, optionale/deploymentabhängige Hinweise sind in `SECURITY.md` dokumentiert.
