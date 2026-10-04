# Sicherheit

- Der Cache ist ausschließlich für anonyme, parameterlose GET- und HEAD-Anfragen vorgesehen.
- Admin-, Login-, REST-, XML-RPC-, Cron-, Feed-, Such-, Vorschau- und Sitemap-Anfragen werden ausgeschlossen.
- Cache-Dateien werden mit SHA-256-Schlüsseln und atomarer Dateischreibung angelegt.
- Die frühe Auslieferung vor dem WordPress-Start ist standardmäßig deaktiviert und muss bewusst aktiviert werden.
- Einstellungen sind auf Administratoren, Nonces und `manage_options` beschränkt.
- Der eigene Cache löscht ausschließlich Dateien in seinem eigenen Verzeichnis.
- WP Super Cache wird nicht parallel als zweiter Page-Cache betrieben.
- Der Cache darf keine personalisierten Inhalte, Cookies oder Weiterleitungen speichern.
- Bereits mit Cookies oder Authentifizierungsdaten eingehende Anfragen werden nicht im eigenen Cache gespeichert.
- Antworten mit unbekannten `Vary`-Headern werden nicht gespeichert; ausschließlich `Accept-Encoding` wird als Cache-Variante zugelassen.
- Der native Datei-Cache ist auf Single-Site begrenzt; 404-Antworten und beliebige unbekannte Pfade werden nicht gespeichert.
- Der frühe Drop-in akzeptiert nur eine passende, gültige Hostangabe und verweigert veraltete Konfigurationen, Multisite-Konfigurationen sowie einen nicht-nativen Cache-Weg.
- Der Wechsel von WP Super Cache wird über Plugin-Lifecycle-Hooks erkannt; der eigene Drop-in und seine Dateien werden dann entfernt, damit nicht zwei Full-Page-Caches parallel laufen.
- Cache-Schreibvorgänge und Bereinigungen werden über eine exklusive Sperrdatei serialisiert; Nutzdateien werden erst vollständig geschrieben und anschließend atomar umbenannt.

Die Cache-Dateien liegen unter `wp-content/cache/adrian-site-cache/` und werden dort per Apache-Regel gegen direkten Zugriff geschützt. Bei Nginx- oder IIS-Betrieb sollte der Hoster zusätzlich den Zugriff auf diesen Ordner sperren; der sichere Standard dieser Version verwendet weiterhin den Controller-Modus mit WP Super Cache.
