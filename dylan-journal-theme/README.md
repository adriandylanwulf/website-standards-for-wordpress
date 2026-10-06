# Adrian Dylan Wulf – persönliches Website-Template 4.3.4

Ein schlankes WordPress-Theme für die persönliche Website von Adrian Dylan Wulf. Es erhält die bestehenden Seiten, Beiträge, Kategorien und URLs; es ändert keine URLs und benötigt keine zusätzlichen Design- oder SEO-Plugins.

## Was Version 4.3.4 verbessert

- SEO-Beschreibungen werden zentral normalisiert und auf höchstens 160 Zeichen begrenzt; dadurch bleiben Such-Metadaten, Social-Previews und JSON-LD konsistent
- lange Beschreibungen werden an einer Wortgrenze gekürzt und erhalten nur dann eine Auslassung, wenn sie tatsächlich gekürzt werden
- keine URLs, Inhalte, externen Schriften oder zusätzlichen Plugins verändert

## Was Version 4.3.3 verbessert

- Social- und SEO-Vorschaubilder werden für Startseite und Fotoseite aus der aktuellen Medienreihenfolge ermittelt; die letzte feste Attachment-ID wurde entfernt
- allgemeine Seiten verwenden keine künstliche Ersatzgrafik mehr, wenn kein passendes aktuelles Bild vorhanden ist
- Startseite, Fotoseite und Online-&-Kontakt-Seite erhalten benannte Inhaltsbereiche für bessere Orientierung mit Screenreadern
- keine URLs, Inhalte, externen Schriften oder zusätzlichen Plugins verändert

## Was Version 4.3.2 verbessert

- das Hauptbild der Über-mich-Seite wird aus der zugeordneten WordPress-Medienreihenfolge verwendet und nicht mehr über eine fest eingetragene Attachment-ID in den Social-Metadaten referenziert
- dadurch bleiben Open-Graph- und Twitter-Vorschaubilder beim späteren Austausch des persönlichen Portraits automatisch konsistent
- keine URLs, Inhalte, externen Schriften oder zusätzlichen Plugins verändert

## Was Version 4.3.1 verbessert

- die dynamische Fotoseite bleibt für Suchmaschinen indexierbar, sobald ihr öffentliche Bilder zugeordnet sind; die Empty-Page-Regel greift nur noch bei einer tatsächlich leeren Fotoseite
- das mobile Menü liefert `aria-expanded="false"` bereits im HTML aus und wird anschließend von der vorhandenen Interaktion synchron gehalten
- Galerie-Bilder erhalten passend zu den tatsächlichen Spaltenbreiten eigene `sizes`-Angaben, damit Browser auf Mobilgeräten und im Desktop keine unnötig großen Varianten laden
- keine URL, kein Seiteninhalt und keine externe Schrift oder Bibliothek wurde verändert

## Was Version 4.3.0 verbessert

- den blauen Rahmen am Header und am Startseitenfoto leicht zurückgenommen, damit die Bereiche ruhiger und weniger schwer wirken
- Blogeinträge mit etwas mehr vertikalem Atem und robustem Textumbruch versehen; lange Überschriften oder URLs können das Raster nicht mehr ungewollt verbreitern
- den Footer auf Mobilgeräten lesbarer gemacht: rechtliche Hinweise sind klar getrennt und die Linkzeilen haben größere, besser erreichbare Ziele
- keine neuen Plugins, externen Schriften, Bibliotheken oder zusätzliche Customizer-CSS-Schicht eingeführt

## Was Version 4.2.0 verbessert

- Blogeinträge reagieren auf Desktop und Mobilgeräte mit einer sehr zurückhaltenden farbigen Seitenmarkierung und einem klareren Pfeil, ohne aus der Liste Karten oder künstliche UI-Elemente zu machen
- die große Portraitdarstellung auf kleinen Bildschirmen ausgewogener zugeschnitten, damit der persönliche Inhalt schneller sichtbar wird
- Galerie- und Fotolinks erhalten eine dezente, GPU-freundliche Rückmeldung; Bewegungen bleiben über `prefers-reduced-motion` vollständig abschaltbar
- lange Inhaltsbereiche können moderne Browser erst bei Bedarf layouten; das reduziert unnötige Arbeit unterhalb des sichtbaren Bereichs, ohne HTML, URLs oder Inhalte zu verändern
- Fokusdarstellung, Browser-Akzentfarbe und Ankerabstände für Tastatur, Formulare und direkt verlinkte Überschriften verfeinert
- keine externen Schriften, Bibliotheken, Plugins oder nachträgliche Customizer-CSS-Schicht eingeführt

## Was Version 2.11.1 verbessert

- Cookie-Einstellungen im Footer typografisch und vertikal exakt an die übrigen Footer-Links angeglichen
- native Browser-Abstände des Buttons entfernt, ohne die Bedienbarkeit oder den sichtbaren Fokus zu verlieren

## Was Version 2.11.0 verbessert

- Footer als ruhige, klar gegliederte Abschlussfläche mit getrennten Bereichen für Navigation und Rechtliches überarbeitet
- Footer um echte Foto- und Kontakt-Einstiege ergänzt, ohne Konto- oder Zahlungslinks in den globalen Abschluss zu legen
- auf der Über-mich-Seite einen kleinen persönlichen Ausschnitt aus der Fotoseite ergänzt
- drei weitere eigene, unveränderte Motive aus Fotos eingebunden; Ortsinformationen der neuen Exportkopie wurden nicht mit veröffentlicht
- Fotoseite sprachlich um Dortmund ergänzt und die vorhandenen lazy geladenen, responsiven Bildgrößen beibehalten
- keine zusätzlichen Plugins, externen Bibliotheken oder nachträgliche Customizer-CSS-Schicht eingeführt

## Was Version 2.10.0 verbessert

- eigene Templates für `Über mich` und `Fotos` ergänzt, damit beide Seiten als persönliche Inhalte und nicht als generische Standardseiten erscheinen
- eine unveränderte Portraitaufnahme und drei ausgewählte Reisemotive über die WordPress-Mediathek eingebunden; WordPress liefert dafür responsive Bildgrößen
- eine ruhige, spaltenbasierte Fotogalerie ohne künstliche Kartenoptik aufgebaut und Bildtexte mit sinnvollen Alternativtexten versehen
- die Texte der Über-mich-Seite natürlicher und persönlicher formuliert
- Layouts für Desktop und Mobilgerät mit derselben lokalen Theme-Datei umgesetzt; keine zusätzliche Customizer-CSS-Schicht und keine neuen Plugins

## Was Version 4.0.0 verbessert

- die warme Akzentfarbe durch eine ruhige, persönliche Blaupalette mit tiefem Petrolblau ersetzt
- Papierfläche, Footer, Codeblöcke, Linien und Fokuszustände auf das neue Farbsystem abgestimmt
- die reduzierte Typografie, den natürlichen Seitenaufbau und die schlanke Navigation beibehalten
- keine neuen Plugins, externen Designbibliotheken oder zusätzlichen Skripte eingeführt

## Was Version 2.8.1 verbessert

- Kontaktseite auf den eindeutigen Pfad `/kontaktformular/` umgestellt
- Kontaktaufruf auf der Startseite verständlicher benannt und die doppelte Kontaktverknüpfung im Footer entfernt
- Kontaktabschluss auf kleinen Bildschirmen mit weniger Leerraum und kompakterer Linknavigation gestaltet

## Was Version 2.8.0 verbessert

- Gestaltung insgesamt von der bisherigen kühlen Editorial-Anmutung auf eine wärmere, persönlichere und ruhigere Seitenfläche umgestellt
- Startseite mit natürlicherer Sprache, weniger nummerierter Navigation und ausgewogenerem Verhältnis zwischen Text und Zwischenraum
- Typografie, Farben, Linien, Bilder, Formulare und Artikelansichten auf ein gemeinsames persönliches Erscheinungsbild abgestimmt
- Footer als mittlere, helle Abschlussfläche neu aufgebaut: mehr Luft als die letzte Variante, aber ohne großen Farbblock
- bestehende WordPress-Templates, URLs, SEO-Ausgabe, Cookiebot- und Performance-Logik beibehalten

## Was Version 2.7.0 verbessert

- Header und Footer als schlankere, ruhigere Bereiche mit weniger dekorativen Beschriftungen neu geordnet
- Footer visuell als dunkler, kontrastreicher Abschluss mit kompakter Link-Navigation gestaltet; Copyright-Zeile und Navigation auf das Nötige gekürzt
- vollständiger Name statt eines technischen Kurznamens; das Monogramm im Footer wurde entfernt
- mobile Kopfzeile mit mittig gesetztem Seitennamen und kompakter, gut bedienbarer Navigation
- Startseite sprachlich persönlicher gefasst und auf klare, kurze Hinweise reduziert
- bestehende Beiträge redaktionell auf eine natürliche, persönliche Sprache geprüft; URLs und fachliche Inhalte bleiben erhalten
- zentrale Theme-Dateien weiterverwendet: keine nachträgliche Customizer-CSS-Schicht und keine neuen Plugins

## Was Version 2.6.2 verbessert

- Cookiebot wieder frühzeitig und sichtbar im Dokumentkopf eingebunden, damit Einwilligungsbanner und Google Consent Mode tatsächlich ausgeführt werden
- Google-Analytics-Einbindung dadurch wieder consent-gesteuert nutzbar; ohne Einwilligung bleibt die Statistik weiterhin korrekt deaktiviert
- SEO-Beschreibung des Windows-11-26H2-Beitrags auf Neuerungen, Sicherheitslage und bekannte Probleme geschärft

## Was Version 2.6.1 verbessert

- Kategorienamen in BlogPosting- und Breadcrumb-JSON-LD vor der strukturierten Datenausgabe sauber dekodiert

## Was Version 2.6.0 verbessert

- Header und Footer als zusammengehörige, ruhige Editorial-Flächen neu aufgebaut
- Mobile Navigation mit sichtbarer Menübezeichnung, zentriertem Namen und klarerem Fokuszustand überarbeitet
- Persönliche Beiträge zu Mittwald und Windows 11 26H2 mit eigenen SEO-Beschreibungen vorbereitet
- Datenschutzerklärung um die neue Online-&-Kontakt-Seite und die externen Profilanbieter ergänzt

## Was Version 2.5.24 verbessert

- Passwortgeschützte Beiträge liefern keine eigenen Meta-/JSON-LD-Daten und erhalten `noindex` sowie `noarchive`

## Was Version 2.5.23 verbessert

- Tag-Sitemap-Ausnahmen werden zwischengespeichert und nach Änderungen an Tags invalidiert, damit öffentliche Sitemap-Anfragen nicht jedes Mal die vollständige Tagliste neu einlesen

## Was Version 2.5.22 verbessert

- Öffentliche Profile und Konten in die eigene Seite `Online & Kontakt` ausgelagert
- Footer auf einen ruhigen internen Link reduziert; keine Konto-URLs mehr im Footer
- Header mit zentrierter mobiler Namenszeile und klarer Nebenzeile verfeinert
- Linkliste als editierbarer Seiteninhalt vorbereitet, damit spätere Accounts ohne Theme-Umbau ergänzt werden können
- JSON-LD-Titel werden vor der Ausgabe als strukturierte Daten sauber dekodiert

Die Seite `Online & Kontakt` sollte den Seitenslug `online` erhalten und als Template **Online & Kontakt** zugewiesen werden. Die vier initialen Links werden als normale Seiteninhalte angelegt; neue öffentliche Konten können später direkt im Editor ergänzt werden.

## Aus Version 2.5.5 übernommen

- Native WordPress-Kommentare für Beiträge vorbereitet und optisch integriert
- Lesebreite für längere Texte auf 42 rem reduziert
- BreadcrumbList-Structured-Data für Beiträge ergänzt
- Site-Kit-Content-Events nur noch auf einzelnen Beiträgen geladen
- Datenschutzhinweis direkt im Kommentarformular ergänzt

## Aus Version 2.5.4 übernommen

- Site-Kit-Tracking für Contact Form 7 wird nur noch auf Seiten mit einem Kontaktformular geladen
- BlogPosting-JSON-LD nutzt ein explizites WebPage-Objekt für `mainEntityOfPage`

## Aus Version 2.5.3 übernommen

- ein klarer, kompakter Header ohne Monogramm oder altes Bildlogo
- eine responsive Hauptnavigation mit nativen Browsermitteln statt Menü-JavaScript
- ein reduzierter Footer mit Cookiebot-kompatiblem Link
- eine einzelne, versionsgebundene lokale CSS-Datei statt einer veralteten Minify-Kopie plus Overrides
- gezieltes Laden von Contact Form 7 nur auf der Kontaktseite beziehungsweise auf Seiten mit einem Formular
- begrenzte, nicht-rekursive Formulardetektion für robuste Seitenladezeiten
- klarere Startseiten-Überschrift, bessere Lesebreite und zugängliche Fokuszustände
- individuelle Meta-Beschreibungen, Open Graph, JSON-LD und sinnvolle Sitemap-/Noindex-Regeln ohne SEO-Plugin
- selbstreferenzierende Canonicals für Beitrags- und Kategoriearchive
- passende `CollectionPage`-Strukturdaten für Übersichtsseiten und konsistentere Seitentitel
- bessere mobile Textdarstellung und größere Footer-Touch-Ziele
- vollständiger Autorenname in Beiträgen statt des technischen WordPress-Benutzernamens
- kompakteres Kontaktformular mit zweispaltigem Desktop-Einstieg und einspaltiger Mobilansicht
- präzisere Meta-Beschreibungen für aktuelle Beiträge

Die Seite verwendet weiterhin den WordPress-Block-Editor für Inhalte. Das klassische PHP-Theme bleibt absichtlich erhalten: Dadurch können die vorhandenen Seitenlayouts, die Navigation und die derzeitigen URLs ohne riskante Datenmigration weiterlaufen.

## Voraussetzungen

- WordPress 7.1 oder neuer
- PHP 8.5 oder neuer
- HTTPS
- ein zugewiesenes Menü für die Position **Hauptnavigation**

Es gibt keine Abhängigkeit von Elementor, Divi, Webfonts oder einem Icon-Dienst.

## Installation

1. Die Datei `dylan-journal-wordpress-theme-v4.3.3.zip` unter **Design → Themes → Theme hinzufügen → Theme hochladen** hochladen.
2. Das Theme zunächst über die Vorschau kontrollieren und erst danach aktivieren.
3. Unter **Design → Menüs** das bestehende Hauptmenü der Position **Hauptnavigation** zuweisen.
4. Unter **Einstellungen → Lesen** die Seite `Startseite` als Startseite und `Blog` als Beitragsseite bestätigen.
5. Startseite, Blog, einen Beitrag, Kontakt, Impressum und Datenschutzerklärung auf Desktop und Mobilgerät prüfen.

Die bestehende Theme-Version bleibt beim Upload in ihrem eigenen Ordner erhalten. Ein Wechsel zurück ist daher jederzeit über **Design → Themes** möglich.

## Kontakt, Cookiebot und Google Analytics

Das Theme lädt selbst weder Google Analytics noch Cookiebot. Diese Dienste bleiben bei ihren jeweiligen WordPress-Plugins, damit Einwilligung und Datenschutz an einer Stelle konfiguriert werden.

- Das Cookie-Einstellungen-Element im Footer ruft bei verfügbarer Cookiebot-Installation `Cookiebot.renew()` auf.
- Contact Form 7 wird auf der Kontaktseite nicht verändert. Die bestehende Pflicht-Checkbox für die Datenschutzerklärung muss dort aktiv bleiben.
- Nach jeder Änderung an Analytics oder Cookiebot muss geprüft werden, dass Statistik-Skripte erst entsprechend der gewählten Einwilligungslogik arbeiten.

## SEO und Performance

Das Theme erzeugt nur dann eigene Meta-Angaben und JSON-LD, wenn kein SEO-Plugin wie Yoast SEO, Rank Math, All in One SEO, SEOPress oder The SEO Framework aktiv ist. Mit einem solchen Plugin übernimmt dieses die Meta-Angaben, sodass nichts doppelt ausgegeben wird.

Bei echten Nutzerdaten bleiben Cache, Bilder und externe Dienste die wichtigsten Leistungshebel. Das Theme ersetzt keinen Server-Cache. Für Mittwald sollten die REST-/Loopback-503-Warnungen aus dem WordPress-Site-Health-Bericht separat mit dem Hosting geprüft werden.

## Sicherheit und Wartung

- WordPress, PHP, Plugins und Themes regelmäßig aktualisieren.
- Administrator-Konten mit einem eigenen starken Passwort und Zwei-Faktor-Anmeldung schützen.
- Backups regelmäßig in einer getrennten Testumgebung wiederherstellen.
- XML-RPC oder REST nicht allein aus Sicherheitsgründen pauschal deaktivieren; erst prüfen, ob andere Funktionen sie benötigen.

Dieses Theme ist keine Rechtsberatung. Impressum, Datenschutzerklärung, Cookiebot- und Analytics-Konfiguration müssen immer den tatsächlich eingesetzten Diensten entsprechen.
