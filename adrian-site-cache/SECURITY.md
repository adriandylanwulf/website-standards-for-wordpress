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
