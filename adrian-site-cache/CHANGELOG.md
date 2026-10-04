# Changelog

## 1.0.6

- Nativer Datei-Cache auf Single-Site begrenzt, um gemeinsame Multisite-Cache-Dateien auszuschließen.
- Früher Drop-in prüft den konfigurierten Host gegen den aktuellen Host.
- 404-Antworten werden nicht mehr gespeichert, damit beliebige Rewrite-Pfade keine Cache-Einträge erzeugen.

## 1.0.5

- Frühe Auslieferung über `advanced-cache.php` auf eine bewusst aktivierbare Option umgestellt.
- Der sichere Standard berücksichtigt `DONOTCACHEPAGE` nach dem WordPress-Start.
- `Vary`-Header werden nur noch mit `Accept-Encoding` akzeptiert; unbekannte Varianten verhindern das Caching.

## 1.0.4

- Schlüssel für frühe und späte Cache-Auslieferung vereinheitlicht.
- `DONOTCACHEPAGE` wird bereits im frühen Drop-in berücksichtigt.
- Datei- und Speichergrenzen werden vor jedem neuen Cache-Eintrag geprüft.

## 1.0.3

- Den gesamten eigenen Cache-Ordner gegen direkten Webzugriff gesperrt.
- Den bisherigen `advanced-cache.php`-Drop-in für die Rückfalloption nicht mehr als öffentlich erreichbare Datei, sondern geschützt in einer nicht automatisch geladenen WordPress-Option gespeichert.

## 1.0.2

- Antworten mit variantenabhängigen `Vary`-Headern werden nicht als eigener Cache gespeichert.

## 1.0.1

- GZIP-Dateien für den eigenen frühen Datei-Cache ergänzt.
- Eingehende Cookies, Authentifizierungsdaten und `Vary: Cookie` werden als Cache-Ausschluss behandelt.
- Alte komprimierte Cache-Dateien werden bei Bereinigung zuverlässig entfernt.

## 1.0.0

- Erste Version mit WP-Super-Cache-Steuerung und eigenem Datei-Cache.
- Eigener `advanced-cache.php`-Drop-in für die spätere Ablösung.
