# Changelog

## 1.6.0

- Geschützten KI-Vorschlagsgenerator für ausgewählte Website-Standards ergänzt.
- KI-Vorschläge bleiben zunächst als Entwurf gespeichert und werden erst nach erneuter ausdrücklicher Bestätigung übernommen; die Veröffentlichungsoption des jeweiligen Endpunkts bleibt unverändert.
- `security.txt`, `ads.txt` und `app-ads.txt` bleiben bewusst vom KI-Generator ausgeschlossen, damit keine Kontaktwege oder Verkäufer-IDs erfunden werden.
- Vorschläge werden auf Dateigröße, aktive Inhalte, JSON-Struktur und erlaubte URL-Hosts geprüft.
- Den experimentellen, standardmäßig deaktivierten Endpunkt `/.well-known/ai-safety.txt` als klar gekennzeichnete Vorlage ergänzt.
- Die Datenfreigabe für einen einmaligen Dateivorschlag kann gespeichert werden, ohne die stündliche KI-Automatik einzuschalten.

## 1.5.0

- Geschützten Endpunkt-Test für aktivierte Website-Standards ergänzt; geprüft werden nur HTTP-Status, Content-Type und vorhandene Cache-Header.
- Der Test ist auf den eigenen Website-Host begrenzt, folgt keinen Weiterleitungen und speichert keine Antwortinhalte.
- `security.txt` wird beim Speichern und in der Statusprüfung auf ein gültiges, zukünftiges Ablaufdatum geprüft.

## 1.4.0

- Öffentliche Standarddateien senden ETags und beantworten unveränderte Wiederholungsanfragen mit `304 Not Modified`.
- `security.txt` erhält ein tagesstabiles Ablaufdatum, damit Browser und Caches nicht bei jeder Sekunde einen neuen Validator sehen.
- Die kurze öffentliche Cache-Dauer erlaubt jetzt eine schonende Hintergrundaktualisierung über `stale-while-revalidate`.

## 1.3.3

- Öffentliche Textdatei-Endpunkte senden keine widersprüchlichen No-Cache-Header mehr und können die vorgesehene kurze öffentliche Cache-Dauer nutzen.
- Die manuelle KI-Aktualisierung prüft die geschützte Administrationsberechtigung jetzt zusätzlich direkt in der Kernfunktion.
- KI-Quelltexte verwenden echte Zeilenumbrüche; dadurch bleibt der begrenzte Kontext für den Connector lesbar und korrekt.
- KI-Ausgaben dürfen nur noch exakt auf die konfigurierte Website-Hostadresse verweisen; Subdomains werden nicht stillschweigend zugelassen.
- CSRF-Prüfungen erfolgen vor der Verarbeitung gespeicherter Admin-Eingaben.

## 1.3.2

- Persistente Datenfreigabe als zusätzliche Fail-closed-Sperre ergänzt.
- Bestehende oder importierte KI-Automatik läuft erst nach erneuter ausdrücklicher Bestätigung.
- Statusanzeige für die Datenfreigabe ergänzt.

## 1.3.1

- Option „Kostenlosen Gemini-Tarif berücksichtigen“ ergänzt.
- Sparsames KI-Profil mit maximal 20.000 Quellzeichen und 1.400 Ausgabetokens ergänzt.
- Prompt im Plugin korrigiert, sodass Zeilenumbrüche tatsächlich als solche übertragen werden.
- `llms-full.txt` berücksichtigt die Auswahl für Beiträge und schließt Rechts- sowie Kontaktseiten aus.
- Import und Export der nicht geheimen Freitier-Einstellung ergänzt.

## 1.3.0

- Offizielle WordPress-AI-Client-Anbindung über `wp_ai_client_prompt()` ergänzt.
- Optionaler, stündlicher KI-Vorschlag für `llms.txt`; standardmäßig deaktiviert.
- Vorschau-Modus als Standard; automatische Veröffentlichung betrifft ausschließlich `llms.txt`.
- Nur veröffentlichte Beiträge und ausdrücklich gewählte Seiten werden als Quelle genutzt; rechtliche Seiten, Medien und Entwürfe bleiben ausgeschlossen.
- Eingabegrenze, Rate-Limits, Lock gegen parallele Läufe, Redaction direkter Kontaktangaben und Ausgabeprüfung ergänzt.
- Connector-Freigabe von WordPress bleibt maßgeblich und wird nicht umgangen.

## 1.2.0

- Geschützter Vorlagen-Assistent für die unterstützten Website-Standards.
- Security.txt kann mit einem geprüften Kontaktwert vorbereitet werden.
- Werbe- und experimentelle Endpunkte bleiben beim Assistenten bewusst deaktiviert.
- Lokale, responsive Admin-Oberfläche ohne externe Assets oder Schriften.
- Laufzeitprüfung verhindert die Ausgabe fehlerhaft gewordener JSON-Endpunkte.
- Fehler beim Speichern der Generator-/LLM-Einstellungen behoben.

## 1.1.1

- Erweiterter Standards-Manager mit automatischem `llms-full.txt`.
- Sichere Erweiterung von `robots.txt` über den WordPress-Core-Filter.
- Lokale Validierung, Konflikthinweise sowie Export und Import.
- Optionale Endpunkte für OpenSearch, AI, TDMRep und Werbedateien.
- Vorbereiteter, rate-limitierter Connector-Hook ohne automatische externe Datenübertragung.
- Importe mit leeren aktivierten Endpunkten werden abgewiesen.

## 1.0.1

- Erste stabile Version mit `security.txt`, `llms.txt`, `humans.txt` und Webmanifest.
- Einzelbenutzer-Sperre für den WordPress-Editor.
- Dynamische URL-, Jahres- und Ablaufdatum-Platzhalter.

