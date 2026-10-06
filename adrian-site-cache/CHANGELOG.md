# Changelog

## 1.4.2

- Eigene Drop-in-Schema-Version ergänzt; die optionale frühe Auslieferung wird nicht mehr durch eine veraltete Plugin-Versionsnummer blockiert.
- Ein aktiviertes Drop-in wird nach einem Update wieder synchronisiert, nachdem der alte Cache sicher geleert wurde.
- Maschinenlesbare Endpunkte werden im frühen Cachepfad ausdrücklich ausgelassen.
- Maschinenlesbare Endpunkte werden auch im normalen Cachepfad nicht als HTML-Seiten gespeichert.

## 1.4.1

- Cache-Invalidierung für neue, bearbeitete und gelöschte Kommentare ergänzt.
- Statuswechsel von Kommentaren (zum Beispiel Freigabe oder Zurückweisung) leeren den öffentlichen Seiten-Cache ebenfalls sofort.
- Kommentarrechte, Formularverarbeitung und öffentliche Kommentarfunktion bleiben unverändert.

## 1.4.0

- Geschützten Button „Cache leeren“ in der WordPress-Adminleiste ergänzt.
- Adminleisten-Aktion mit `manage_options`, eigenem Nonce und `admin-post.php` abgesichert.
- Keine öffentliche Löschroute und keine Pfadparameter für Cache-Dateien eingeführt.
- Plugin-Oberfläche für schmale Adminansichten weiter auf klare, stapelbare Inhalte ausgerichtet.

## 1.3.0

- Optionales Cache-Aufwärmen für die Startseite und die konfigurierte Blog-Einstiegsseite ergänzt; standardmäßig deaktiviert.
- Aufwärmen akzeptiert ausschließlich same-origin-URLs ohne Query-Strings oder Fragmente, folgt keinen Weiterleitungen und speichert keine Antwortinhalte.
- Manueller Aufwärmlauf in der Wartungsansicht und über `wp adrian-cache warm` ergänzt.
- Letzten Aufwärmlauf und das Ergebnis in der Wartungsansicht ergänzt.

## 1.2.0

- Wiederholte Anfragen an unveränderte Cache-Dateien nutzen ETag- und Last-Modified-Validatoren und liefern bei einem Treffer `304 Not Modified`.
- Die öffentliche Cache-Auslieferung setzt die zugehörigen Validator- und Cache-Header auch bei einer `304`-Antwort vollständig.

## 1.1.4

- Manuelle Cache-Leerungen entfernen jetzt ebenfalls verwaiste GZIP-Neben- und alte temporäre Dateien.
- Die interne Versionssperre des optionalen Drop-ins wurde für das Update erhöht.

## 1.1.3

- Entfernt verwaiste gzip-Neben- und alte temporäre Dateien im eigenen Cache-Verzeichnis.
- Erhöht die interne Versionssperre des optionalen Drop-ins, damit kein Cache aus einer älteren Implementierung früh ausgeliefert wird.

## 1.1.2

- Administrationsoberfläche weiter beruhigt: weniger Kartenwirkung, flachere Statuszeilen und kompaktere Cache-Modus-Details.
- Cache-Modi als zugängliche native Aufklappbereiche umgesetzt; nur der aktive Modus ist zunächst geöffnet.
- Wartung zeigt den letzten externen Cronlauf direkt neben den übrigen Zeitpunkten.
- Cache-Dateien im Dashboard zählen jetzt nur echte HTML-Einträge; interne Hilfsdateien werden nicht mehr als Seiten gezählt.
- Der normale Datei-Cache nutzt vorhandene GZIP-Varianten direkt mit passenden Headern.

## 1.1.1

- Administrationsoberfläche visuell überarbeitet: ruhige blaue Akzente, klarer Aktiv-Status und kompaktere Statuskarten.
- Einstellungen, Cache-Modi und Wartung in verständliche Bereiche gegliedert.
- Aktiver Cache-Modus wird optisch hervorgehoben; Wartungsdaten werden als kompakte Fakten dargestellt.
- Responsive Darstellung für schmale Admin-Ansichten verbessert, ohne externe Schriftarten, JavaScript-Bibliotheken oder zusätzliche Abhängigkeiten.

## 1.1.0

- Eigenständiger Datei-Cache als einziger Cache-Weg; die nicht vorhandene WP-Super-Cache-Abhängigkeit wurde aus Oberfläche, Status und Wartung entfernt.
- Migration bestehender Installationen auf den nativen Cache ohne Übernahme veralteter Cache-Dateien.
- Der interne WordPress-Cron wird bei aktiviertem externem Cronjob als Information statt als pauschaler Fehler angezeigt.
- Der letzte tatsächlich registrierte Cronlauf wird bei `wp-cron.php`-Ausführungen gespeichert und in der Wartungsansicht angezeigt.
- Verwaiste eigene `advanced-cache.php`-Drop-ins werden entfernt, wenn die frühe Auslieferung deaktiviert ist.
- CLI-Status um frühe Auslieferung und letzten Cronlauf ergänzt.

## 1.0.7

- Cache-Modi „Kaum Cache“, „Leicht“, „Normal“ und „Stark“ mit verständlichen Vor- und Nachteilen ergänzt.
- Veraltete Drop-in-Konfigurationen werden beim Update nicht mehr ausgeliefert; alte native Dateien werden beim Upgrade verworfen.
- Frühe Auslieferung verweigert ungültige oder fehlende Hosts, Multisite-Konfigurationen und nicht-native Konfigurationen.
- Wechsel zu WP Super Cache wird über Aktivierungswechsel erkannt und räumt den eigenen Drop-in auf.
- Cache-Schreibvorgänge und Bereinigungen werden serialisiert; HTML- und GZIP-Dateien, Konfiguration und Drop-in werden atomar geschrieben.
- Veraltete `Pragma`- und `Expires`-Antworten sowie die Schutzdatei `index.html` werden korrekt berücksichtigt.

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
