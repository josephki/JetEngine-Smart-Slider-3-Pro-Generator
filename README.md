# JetEngine Smart Slider 3 Pro Generator

Ein WordPress-Plugin zur Integration von JetEngine Custom Post Types in Smart Slider 3 Pro.

## Beschreibung

Dieses Plugin ermöglicht es, JetEngine Custom Post Types (CPTs) direkt in Smart Slider 3 Pro zu verwenden. Es fügt einen neuen Generator zur Auswahl hinzu, mit dem du dynamische Slides aus deinen JetEngine CPTs erstellen kannst.

## Funktionen

- Automatische Erkennung und Integration von JetEngine Custom Post Types
- Unterstützung für Meta-Felder und Taxonomien
- Flexible Filterung nach Status, Taxonomien und Meta-Feldern
- Umfangreiche Sortierungsoptionen
- Vollständige Integration in die Smart Slider 3 Pro Benutzeroberfläche

## Installation

1. Lade das Plugin als ZIP-Datei herunter
2. Gehe in deinem WordPress-Dashboard zu Plugins > Neu hinzufügen > Plugin hochladen
3. Wähle die ZIP-Datei aus und klicke auf "Jetzt installieren"
4. Aktiviere das Plugin

## Voraussetzungen

- WordPress 5.0 oder höher
- JetEngine Plugin (aktiviert und konfiguriert)
- Smart Slider 3 Pro (aktiviert und installiert)

## Verwendung

1. Gehe zu Smart Slider 3 Pro > Neu hinzufügen > Dynamic Slide
2. Wähle "JetEngine CPT" als Generator
3. Wähle einen deiner Custom Post Types aus der Liste
4. Konfiguriere die Filteroptionen nach Bedarf
5. Erstelle dein Slide-Design mit den verfügbaren dynamischen Variablen

### Verfügbare Variablen

Für jeden Post sind folgende dynamische Variablen verfügbar:

- Standardfelder: id, title, url, author_name, author_url, date, modified, content, excerpt, comment_count, comment_status
- Bild-Felder: image, thumbnail, image_width, image_height, image_alt
- Taxonomie-Felder: [taxonomy_name], [taxonomy_name]_slugs, [taxonomy_name]_urls, [taxonomy_name]_name, [taxonomy_name]_slug, [taxonomy_name]_url
- Meta-Felder: meta_[field_name], meta_image_[field_name] (für Bild-IDs)

## Filter und Sortierung

Das Plugin bietet folgende Filter- und Sortierungsoptionen:

### Filter
- Post-Status (Veröffentlicht, Entwurf, Ausstehend, Privat, Geplant)
- Taxonomie-Filter (für alle mit dem Custom Post Type verbundenen Taxonomien)
- Meta-Feld-Filter (mit umfangreichen Vergleichsoperatoren)
- Passwortgeschützte Posts ein-/ausschließen

### Sortierung
- Nach Standardfeldern (ID, Autor, Titel, Datum, etc.)
- Nach Meta-Feldern (alphabetisch oder numerisch)
- Aufsteigend oder absteigend

## Entwickler-Informationen

Das Plugin wurde entwickelt von Joseph Kisler - Webwerkstatt. 

### Debug-Modus

Wenn `WP_DEBUG` aktiviert ist, protokolliert das Plugin Informationen über die Registrierung und Verwendung des Generators. Diese Informationen können bei der Fehlerbehebung hilfreich sein.

## Fehlersuche

Wenn der Generator nicht in der Smart Slider 3 Pro Benutzeroberfläche erscheint:

1. Stelle sicher, dass sowohl JetEngine als auch Smart Slider 3 Pro aktiviert sind
2. Deaktiviere und reaktiviere das Plugin
3. Leere den WordPress-Cache und den Browser-Cache
4. Überprüfe die PHP-Fehlerprotokolle auf mögliche Fehlermeldungen

## Changelog

### Version 2.3
- Verbesserte Cache-Leeren-Funktion
- Kompatibilität mit verschiedenen Smart Slider 3-Versionen
- Hinzufügen von Debug-Informationen

### Version 2.2
- Unterstützung für Taxonomie-Filter
- Erweiterte Meta-Feld-Vergleichsoperatoren

### Version 2.1
- Unterstützung für Meta-Feld-Bilder
- Verbesserter Daten-Export

### Version 2.0
- Initiale öffentliche Version

## Lizenz

Dieses Plugin ist unter der GPLv2 oder späteren Version lizenziert.

---

Bei Fragen oder Problemen kontaktieren Sie bitte den Support.