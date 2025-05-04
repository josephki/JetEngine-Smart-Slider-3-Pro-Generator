# JetEngine Advanced Smart Slider 3 Generator

Eine fortschrittliche Integration zwischen JetEngine und Smart Slider 3 Pro, die die volle Leistungsfähigkeit der dynamischen Inhalte von JetEngine nutzt.

## Funktionen

- **Vollständige JetEngine-Integration**: Generiere dynamische Slides aus jedem JetEngine Custom Post Type, Custom Content Type oder jeder Relation
- **Erweiterte Meta-Feld-Unterstützung**: Nutze alle JetEngine-Meta-Feldtypen, einschließlich Galerien, Repeater, Maps und mehr
- **Dynamische Bildquellen**: Mehrere Optionen für Bildquellen, einschließlich Beitragsbilder, Meta-Felder, Galerie-Felder und Inhaltsbilder
- **Intelligente Taxonomie-Verarbeitung**: Filtere Beiträge nach Taxonomien mit erweiterten Beziehungsoptionen (UND/ODER)
- **Flexible Sortierung**: Sortiere Inhalte nach verschiedenen Kriterien, einschließlich Meta-Feldern
- **Umfangreiche Variablen**: Greife auf alle JetEngine-Daten in deinen Smart Slider-Slides mit korrekt formatierten Variablen zu

## Anforderungen

- WordPress 5.6 oder höher
- JetEngine 2.8.0 oder höher
- Smart Slider 3 Pro 3.5.0 oder höher

## Installation

1. Lade die Plugin-Dateien in das Verzeichnis `/wp-content/plugins/jetengine-smartslider-generator` hoch oder installiere das Plugin über den WordPress-Plugin-Bildschirm
2. Aktiviere das Plugin über den 'Plugins'-Bildschirm in WordPress
3. Gehe zu Smart Slider 3 > Slider erstellen > Dynamischer Slide, um den Generator zu nutzen

## Verwendung

### Erstellen eines dynamischen Sliders mit JetEngine-Inhalten

1. Gehe zu Smart Slider 3 > Slider erstellen > Dynamischer Slide
2. Wähle "JetEngine" aus der Generator-Liste
3. Wähle die spezifische JetEngine-Quelle aus (Custom Post Type, Custom Content Type oder Relation)
4. Konfiguriere die Filter-Einstellungen nach Bedarf:
   - Filtere nach Taxonomien
   - Setze Meta-Feld-Bedingungen
   - Gib Sortieroptionen an
   - Wähle Bildquellen
5. Klicke auf "Slider erstellen", um deinen dynamischen Slider zu generieren

### Verfügbare Quellen

#### Custom Post Types
Generiere Slides aus jedem JetEngine oder WordPress Custom Post Type mit vollständigem Zugriff auf:
- Post-Daten (Titel, Inhalt, Auszug, usw.)
- Meta-Felder (alle JetEngine-Feldtypen werden unterstützt)
- Taxonomie-Begriffe
- Beitrags- und Galeriebilder

#### Custom Content Types (CCT)
Erstelle Slides aus JetEngine Custom Content Types mit Unterstützung für:
- CCT-Elementdaten
- Alle CCT-Meta-Felder
- Galerie- und Medienfelder

#### Relations
Zeige verwandte Inhalte über JetEngine-Relations an:
- Eltern-Kind-Beziehungen
- Hole Kinder für ein Elternelement oder Eltern für ein Kindelement
- Filtere und sortiere verwandte Elemente

### Verwenden von JetEngine-Meta-Feldern

Diese Integration bietet spezielle Unterstützung für JetEngine's Meta-Feldtypen:

- **Galerie-Felder**: Greife auf alle Bilder in der Galerie zu, wobei das erste Bild als Hauptbild des Slides dient
- **Repeater-Felder**: Greife auf Repeater-Felddaten als formatierte Arrays zu
- **Datums- und Zeitfelder**: Korrekt formatierte Daten mit verschiedenen Ausgabeoptionen
- **Medienfelder**: Automatisch in nutzbare Bild-URLs umgewandelt
- **Maps**: Extrahierte und formatierte Koordinaten für die Anzeige
- **Icon Picker**: Direkter Zugriff auf Icon-Daten

## Erweiterte Verwendung

### Bildquellen

Für jeden Slide kannst du aus diesen Bildquellen wählen:

- **Beitragsbild**: Verwendet das Beitragsbild des Posts (für CPTs)
- **Meta-Feld**: Verwendet ein bestimmtes Meta-Feld als Bildquelle
- **JetEngine-Galerie**: Verwendet ein JetEngine-Galeriefeld, wobei das erste Bild als Hauptbild und andere als Variablen zugänglich sind
- **Inhaltsbilder**: Extrahiert Bilder aus dem Post-Inhalt
- **Erstes Bild aus Inhalt**: Holt nur das erste Bild aus dem Inhalt

### Meta-Feld-Filterung

Filtere deine Inhalte nach Meta-Feldwerten mit diesen Vergleichsoperatoren:
- Gleich (=)
- Ungleich (!=)
- Größer als (>)
- Größer oder gleich (>=)
- Kleiner als (<)
- Kleiner oder gleich (<=)
- Enthält (LIKE)
- Enthält nicht (NOT LIKE)
- In Liste (IN)
- Nicht in Liste (NOT IN)
- Zwischen Werten (BETWEEN)
- Nicht zwischen Werten (NOT BETWEEN)
- Existiert
- Existiert nicht

## Variablen in Slides

Beim Erstellen deiner Slide-Vorlage hast du Zugriff auf zahlreiche Variablen, abhängig von der Quelle:

### Allgemeine Variablen
- `id` - Element-ID
- `title` - Elementtitel
- `url` - Element-URL
- `date` - Erstellungsdatum
- `modified` - Änderungsdatum
- `image` - Haupt-Bild-URL
- `thumbnail` - Thumbnail-Bild-URL

### Custom Post Type spezifisch
- `content` - Post-Inhalt
- `excerpt` - Post-Auszug
- `author_name` - Autorname
- `author_url` - Autor-Archiv-URL
- `comment_count` - Anzahl der Kommentare
- `[taxonomy]` - Taxonomie-Begriffe durch Komma getrennt
- `[taxonomy]_slugs` - Taxonomie-Begriff-Slugs
- `[taxonomy]_urls` - Taxonomie-Begriff-URLs

### Meta-Feld-Variablen
- `meta_[field_name]` - Roher Meta-Feldwert
- `meta_[field_name]_formatted` - Formatierter Meta-Feldwert
- Zusätzliche spezialisierte Variablen je nach Feldtyp

## Support

Wenn du Probleme hast oder Fragen zum Plugin hast, kontaktiere bitte unser Support-Team.

## Lizenz

Dieses Plugin ist unter der GPL v2 oder höher lizenziert.

---

### Credits

- Entwickelt von [Dein Name/Unternehmen]
- Erstellt für JetEngine von Crocoblock und Smart Slider 3 Pro von Nextend