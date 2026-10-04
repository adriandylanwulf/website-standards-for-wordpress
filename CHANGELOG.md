# Changelog

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
