=== Adrian Local SEO ===
Contributors: adriandylanwulf
Requires at least: 6.6
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lokale SEO-Steuerung für eine einzelne WordPress-Website.

== Beschreibung ==

Adrian Local SEO bündelt grundlegende SEO-Metadaten, eine lokale Inhaltsprüfung
und eine optionale Integration in den WordPress AI Client. Das Plugin benötigt
keinen eigenen externen SEO-Dienst und speichert keine API-Schlüssel.

== Datenschutz und Sicherheit ==

* Audit und öffentliche Metadaten werden lokal in WordPress erzeugt.
* Das Plugin führt selbst keine externen HTTP-Anfragen aus.
* Die KI-Funktion ist standardmäßig ausgeschaltet.
* Wird sie ausdrücklich aktiviert und ein WordPress-Konnektor freigegeben,
  kann ein manueller Vorschlag den ausgewählten Beitrag an den konfigurierten
  Anbieter übertragen. Es gibt keinen Zeitplan und keine automatische
  Veröffentlichung.
* Einstellungen und REST/Abilities sind auf Administratoren beschränkt.
* Ein Deaktivieren oder Deinstallieren löscht keine SEO-Daten.

== AI Client und Abilities ==

Auf WordPress-Versionen mit AI Client und Abilities API registriert das Plugin
zwei eng begrenzte, authentifizierte Fähigkeiten: eine lokale SEO-Prüfung und
einen manuellen Metadaten-Vorschlag. Beide speichern oder veröffentlichen keine
Beiträge automatisch.

== Installation ==

1. ZIP-Datei im WordPress-Backend unter Plugins > Installieren > Plugin hochladen
   installieren.
2. Plugin aktivieren.
3. Einstellungen > Adrian SEO öffnen.
4. Zuerst eine lokale Prüfung starten. Die öffentliche Ausgabe bleibt nur aktiv,
   wenn kein anderer SEO-Anbieter erkannt wird.

== Changelog ==

= 1.0.1 =
* Eigene Startseitenfelder für SEO-Titel und Meta-Beschreibung ergänzt.
* Startseiten-Metadaten werden unabhängig von der Gutenberg-Metabox zuverlässig ausgegeben.

= 1.0.0 =
* Erste öffentliche Version.
