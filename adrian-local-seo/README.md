# Adrian Local SEO

Lokale SEO-Steuerung für eine einzelne WordPress-Website. Das Plugin stellt
Titel, Meta-Beschreibungen, Canonicals, Social-Media-Metadaten und kompakte
strukturierte Daten bereit, ohne einen eigenen externen SEO-Dienst zu
benötigen.

## Funktionen

- ausdrückliche Startseitenfelder für SEO-Titel und Meta-Beschreibung
- optionale SEO-Metadaten für Beiträge und Seiten
- lokale Inhaltsprüfung für veröffentlichte Inhalte
- Erkennung leerer Kategorien, fehlender Titelbilder und langer Titel
- Schutz vor doppelter Ausgabe des eingebauten Theme-SEO
- lokale Open-Graph-, Twitter- und Schema.org-Ausgabe
- optionale, manuelle WordPress-AI-Client-Vorschläge

Die öffentliche SEO-Ausgabe wird nur aktiv, wenn kein unterstütztes externes
SEO-Plugin erkannt wurde und im Backend die lokale Ausgabe ausgewählt ist.

## Datenschutz und Sicherheit

Audit, Standardwerte und Metadaten werden lokal in WordPress verarbeitet. Das
Plugin führt selbst keine externen HTTP-Anfragen aus und speichert keine
API-Schlüssel. Die optionale WordPress-AI-Funktion ist getrennt, bleibt
manuell und veröffentlicht niemals automatisch. Ein freigegebener WordPress-
Konnektor ist zusätzlich erforderlich, bevor ein Vorschlag erzeugt werden
kann.

REST-Endpunkte und WordPress-Abilities sind auf Administratoren beschränkt.
Eingaben werden begrenzt und bereinigt; Canonical-URLs dürfen nur auf die
eigene Website zeigen.

## Installation

1. Den Ordner `adrian-local-seo` nach `wp-content/plugins/` kopieren oder die
   ZIP-Datei im WordPress-Backend hochladen.
2. Das Plugin aktivieren.
3. `Einstellungen → Adrian SEO` öffnen.
4. Die lokale Prüfung ausführen und die Startseitenfelder prüfen.

## Version

Aktuelle Version: **1.0.1**
