/**
 * Admin JavaScript für JetEngine SmartSlider 3 Generator
 */
(function($) {
    'use strict';

    // Initialisieren, sobald das DOM geladen ist
    $(document).ready(function() {
        // Initialisiere JetEngine Generator Funktionalität
        initJetEngineGenerator();
    });

    /**
     * Hauptfunktion zur Initialisierung des JetEngine Generators
     */
    function initJetEngineGenerator() {
        // Nur auf den relevanten Admin-Seiten ausführen
        if (!isSmartSliderPage()) {
            return;
        }

        console.log('JetEngine SmartSlider Generator geladen');
        
        // Generator-Typ-spezifische Initialisierung
        initGeneratorTypes();
        
        // Event-Listener hinzufügen
        attachEventListeners();
        
        // UI-Verbesserungen anwenden
        enhanceUserInterface();
    }

    /**
     * Überprüft, ob wir uns auf einer Smart Slider-Seite befinden
     * @return {boolean} True, wenn auf einer Smart Slider-Seite
     */
    function isSmartSliderPage() {
        // Prüfe URL-Parameter für Smart Slider
        return window.location.href.indexOf('smart-slider') > -1 || 
               $('body').hasClass('toplevel_page_smart-slider3') ||
               $('body').hasClass('smart-slider3_page_smart-slider3');
    }

    /**
     * Initialisiert spezifische Funktionen für verschiedene Generator-Typen
     */
    function initGeneratorTypes() {
        // Warte auf Smart Slider Events
        $(document).on('SmartSlider3GeneratorSelected', function(e, generatorType) {
            if (generatorType && generatorType.indexOf('jetengine') === 0) {
                console.log('JetEngine Generator ausgewählt:', generatorType);
                
                // Je nach Generator-Typ unterschiedliche Initialisierungen
                if (generatorType.indexOf('post_type') === 0) {
                    initPostTypeGenerator();
                } else if (generatorType.indexOf('cct_') === 0) {
                    initContentTypeGenerator();
                } else if (generatorType.indexOf('relation_') === 0) {
                    initRelationGenerator();
                }
                
                // Gemeinsame Initialisierung für alle Generator-Typen
                initCommonGeneratorFields();
            }
        });
    }

    /**
     * Initialisierung für Post Type Generator
     */
    function initPostTypeGenerator() {
        // Meta-Feld-Auswahl dynamisieren
        updateMetaFieldOptions();
        
        // Taxonomie-Auswahl aktualisieren
        updateTaxonomyOptions();
        
        // Taxonomie-Abhängigkeiten
        handleTaxonomyDependencies();
    }

    /**
     * Initialisierung für Content Type Generator
     */
    function initContentTypeGenerator() {
        // CCT-spezifische Feldoptionen laden
        updateCCTFieldOptions();
    }

    /**
     * Initialisierung für Relation Generator
     */
    function initRelationGenerator() {
        // Relations-spezifisches Setup
        setupRelationDropdowns();
    }

    /**
     * Gemeinsame Initialisierung für alle Generator-Typen
     */
    function initCommonGeneratorFields() {
        // Bildquellenoptionen verwalten
        handleImageSourceOptions();
        
        // Sortieroptionen initialisieren
        initSortingOptions();
        
        // Metakey-Feld-Abhängigkeiten
        handleMetaKeyDependencies();
    }

    /**
     * Behandelt Meta-Key-Abhängigkeiten basierend auf Sortierauswahl
     */
    function handleMetaKeyDependencies() {
        // Meta-Key-Feld für Sortierung anzeigen/ausblenden
        var $orderbyField = $('#n2_generator_ordering_orderby');
        var $metaKeyField = $('#n2_generator_ordering_meta_key').closest('.n2_form__item');
        
        function updateMetaKeyVisibility() {
            var orderby = $orderbyField.val();
            if (orderby === 'meta_value' || orderby === 'meta_value_num') {
                $metaKeyField.show();
            } else {
                $metaKeyField.hide();
            }
        }
        
        // Initial ausführen
        updateMetaKeyVisibility();
        
        // Bei Änderung aktualisieren
        $orderbyField.on('change', updateMetaKeyVisibility);
    }

    /**
     * Behandelt Abhängigkeiten zwischen Taxonomien
     */
    function handleTaxonomyDependencies() {
        var $taxonomiesField = $('#n2_generator_filter_taxonomies');
        
        function updateTaxonomyTermFields() {
            var selectedTaxonomies = $taxonomiesField.val();
            
            // Alle Taxonomie-Term-Felder ausblenden
            $('[id^="n2_generator_filter_taxonomy_"]').closest('.n2_form__item').hide();
            
            // Relation-Feld zeigen, wenn mehr als eine Taxonomie ausgewählt ist
            var $relationField = $('#n2_generator_filter_taxonomies_relation').closest('.n2_form__item');
            
            if (selectedTaxonomies && selectedTaxonomies.indexOf(',') > -1) {
                $relationField.show();
            } else {
                $relationField.hide();
            }
            
            // Ausgewählte Taxonomie-Term-Felder anzeigen
            if (selectedTaxonomies) {
                var taxonomies = selectedTaxonomies.split(',');
                for (var i = 0; i < taxonomies.length; i++) {
                    $('#n2_generator_filter_taxonomy_' + taxonomies[i]).closest('.n2_form__item').show();
                }
            }
        }
        
        // Initial ausführen
        updateTaxonomyTermFields();
        
        // Bei Änderung aktualisieren
        $taxonomiesField.on('change', updateTaxonomyTermFields);
    }

    /**
     * Bildsource-Optionen verwalten
     */
    function handleImageSourceOptions() {
        var $imageSourceField = $('#n2_generator_image_image_source');
        var $imageMetaField = $('#n2_generator_image_image_meta').closest('.n2_form__item');
        
        function updateImageMetaVisibility() {
            var source = $imageSourceField.val();
            if (source === 'meta' || source === 'jet_gallery') {
                $imageMetaField.show();
            } else {
                $imageMetaField.hide();
            }
        }
        
        // Initial ausführen
        updateImageMetaVisibility();
        
        // Bei Änderung aktualisieren
        $imageSourceField.on('change', updateImageMetaVisibility);
    }

    /**
     * Initialisiert Sortieroptionen
     */
    function initSortingOptions() {
        // Meta-Feld-Optionen für Sortierung aktualisieren
        updateMetaKeyOptions();
    }

    /**
     * Aktualisiert das Dropdown mit Meta-Feld-Optionen
     */
    function updateMetaFieldOptions() {
        var generatorType = getCurrentGeneratorType();
        if (!generatorType) return;
        
        // AJAX-Anfrage, um Meta-Felder zu laden
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jetengine_smartslider_get_meta_fields',
                generator_type: generatorType,
                nonce: jetengineSmartSliderData.nonce
            },
            success: function(response) {
                if (response.success && response.data.fields) {
                    populateMetaFieldDropdown(response.data.fields);
                }
            }
        });
    }

    /**
     * Aktualisiert das Dropdown mit Taxonomie-Optionen
     */
    function updateTaxonomyOptions() {
        var generatorType = getCurrentGeneratorType();
        if (!generatorType) return;
        
        // AJAX-Anfrage, um Taxonomien zu laden
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jetengine_smartslider_get_taxonomies',
                generator_type: generatorType,
                nonce: jetengineSmartSliderData.nonce
            },
            success: function(response) {
                if (response.success && response.data.taxonomies) {
                    populateTaxonomyDropdown(response.data.taxonomies);
                }
            }
        });
    }

    /**
     * Aktualisiert das Dropdown mit CCT-Feld-Optionen
     */
    function updateCCTFieldOptions() {
        var generatorType = getCurrentGeneratorType();
        if (!generatorType) return;
        
        // AJAX-Anfrage, um CCT-Felder zu laden
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jetengine_smartslider_get_cct_fields',
                generator_type: generatorType,
                nonce: jetengineSmartSliderData.nonce
            },
            success: function(response) {
                if (response.success && response.data.fields) {
                    populateCCTFieldDropdown(response.data.fields);
                }
            }
        });
    }

    /**
     * Aktualisiert das Dropdown mit Meta-Key-Optionen für Sortierung
     */
    function updateMetaKeyOptions() {
        // Ähnlich wie updateMetaFieldOptions, aber spezifisch für Sortier-Meta-Keys
        var generatorType = getCurrentGeneratorType();
        if (!generatorType) return;
        
        // AJAX-Anfrage für Meta-Keys
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jetengine_smartslider_get_meta_keys',
                generator_type: generatorType,
                nonce: jetengineSmartSliderData.nonce
            },
            success: function(response) {
                if (response.success && response.data.keys) {
                    populateMetaKeyDropdown(response.data.keys);
                }
            }
        });
    }

    /**
     * Richtet die Relations-Dropdown-Menüs ein
     */
    function setupRelationDropdowns() {
        var generatorType = getCurrentGeneratorType();
        if (!generatorType) return;
        
        // AJAX-Anfrage für Relation-Optionen
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jetengine_smartslider_get_relation_objects',
                generator_type: generatorType,
                nonce: jetengineSmartSliderData.nonce
            },
            success: function(response) {
                if (response.success && response.data.objects) {
                    populateRelationObjectsDropdown(response.data.objects);
                }
            }
        });
    }

    /**
     * Füllt das Meta-Feld-Dropdown mit Optionen
     */
    function populateMetaFieldDropdown(fields) {
        var $dropdown = $('#n2_generator_filter_meta_name');
        $dropdown.empty();
        
        // Option für leere Auswahl
        $dropdown.append($('<option></option>').val('').text('-- ' + jetengineSmartSliderData.select_field + ' --'));
        
        // Felder hinzufügen
        $.each(fields, function(key, field) {
            $dropdown.append($('<option></option>').val(field.name).text(field.title + ' (' + field.type + ')'));
        });
    }

    /**
     * Füllt das Taxonomie-Dropdown mit Optionen
     */
    function populateTaxonomyDropdown(taxonomies) {
        var $dropdown = $('#n2_generator_filter_taxonomies');
        $dropdown.empty();
        
        // Taxonomien hinzufügen
        $.each(taxonomies, function(key, taxonomy) {
            $dropdown.append($('<option></option>').val(taxonomy.name).text(taxonomy.label));
        });
        
        // Multiple-Select aktivieren
        if ($dropdown.data('select2')) {
            $dropdown.select2('destroy');
        }
        
        $dropdown.select2({
            placeholder: jetengineSmartSliderData.select_taxonomies,
            allowClear: true,
            multiple: true
        });
    }

    /**
     * Füllt das CCT-Feld-Dropdown mit Optionen
     */
    function populateCCTFieldDropdown(fields) {
        var $dropdown = $('#n2_generator_filter_cct_field');
        $dropdown.empty();
        
        // Option für leere Auswahl
        $dropdown.append($('<option></option>').val('').text('-- ' + jetengineSmartSliderData.select_field + ' --'));
        
        // Felder hinzufügen
        $.each(fields, function(key, field) {
            $dropdown.append($('<option></option>').val(field.name).text(field.title + ' (' + field.type + ')'));
        });
    }

    /**
     * Füllt das Meta-Key-Dropdown für Sortierung
     */
    function populateMetaKeyDropdown(keys) {
        var $dropdown = $('#n2_generator_ordering_meta_key');
        $dropdown.empty();
        
        // Option für leere Auswahl
        $dropdown.append($('<option></option>').val('').text('-- ' + jetengineSmartSliderData.select_meta_key + ' --'));
        
        // Keys hinzufügen
        $.each(keys, function(key, metaKey) {
            $dropdown.append($('<option></option>').val(metaKey.name).text(metaKey.title));
        });
    }

    /**
     * Füllt das Relations-Objekte-Dropdown
     */
    function populateRelationObjectsDropdown(objects) {
        var $dropdown = $('#n2_generator_filter_parent_object');
        $dropdown.empty();
        
        // Objekte hinzufügen
        $.each(objects, function(key, object) {
            $dropdown.append($('<option></option>').val(object.value).text(object.label));
        });
    }

    /**
     * Event-Listener hinzufügen
     */
    function attachEventListeners() {
        // Smart Slider Generator-Auswahl-Event abfangen
        $(document).on('SmartSlider3GeneratorSettingsChanged', function(e, settings) {
            console.log('Generator-Einstellungen geändert:', settings);
            refreshDynamicFields();
        });
        
        // Generator-Test-Button-Klick
        $(document).on('click', '.n2_generator_preview_button', function(e) {
            preProcessGeneratorPreview(e);
        });
        
        // Zusätzliche Hilfe-Tabs
        $(document).on('click', '.jetengine-help-tab', function(e) {
            e.preventDefault();
            var tabId = $(this).data('tab');
            showHelpTab(tabId);
        });
    }

    /**
     * Aktualisiert dynamische Felder
     */
    function refreshDynamicFields() {
        var generatorType = getCurrentGeneratorType();
        if (!generatorType) return;
        
        // Abhängige Felder aktualisieren
        handleMetaKeyDependencies();
        handleTaxonomyDependencies();
        handleImageSourceOptions();
    }

    /**
     * Vorverarbeitung für Generator-Vorschau
     */
    function preProcessGeneratorPreview(e) {
        // Eigene Logik vor der Vorschau ausführen
        // Falls notwendig, e.preventDefault() verwenden, um Default-Verhalten zu überschreiben
        
        // Log Generator-Einstellungen
        console.log('Generator-Vorschau angefordert');
    }

    /**
     * UI-Verbesserungen anwenden
     */
    function enhanceUserInterface() {
        // Zusätzliche UI-Elemente hinzufügen
        addHelpTabs();
        
        // Tipps zu Feldern hinzufügen
        addFieldTips();
        
        // Feld-Label-Verbesserungen
        enhanceFieldLabels();
    }

    /**
     * Hilfetabs hinzufügen
     */
    function addHelpTabs() {
        // Nur hinzufügen, wenn die Tabs-Container existieren
        var $tabsContainer = $('.n2_generator_parameters');
        
        if ($tabsContainer.length) {
            var $helpTabs = $('<div class="jetengine-help-tabs"></div>');
            
            $helpTabs.append('<a href="#" class="jetengine-help-tab" data-tab="general">Allgemeine Hilfe</a>');
            $helpTabs.append('<a href="#" class="jetengine-help-tab" data-tab="fields">Felder & Variablen</a>');
            $helpTabs.append('<a href="#" class="jetengine-help-tab" data-tab="examples">Beispiele</a>');
            
            $tabsContainer.prepend($helpTabs);
        }
    }

    /**
     * Tipps zu Feldern hinzufügen
     */
    function addFieldTips() {
        // Meta-Feld-Tipp
        $('#n2_generator_filter_meta_name').closest('.n2_form__item').append(
            '<div class="jetengine-field-description">' + 
            jetengineSmartSliderData.meta_field_tip + 
            '</div>'
        );
        
        // Bild-Meta-Feld-Tipp
        $('#n2_generator_image_image_meta').closest('.n2_form__item').append(
            '<div class="jetengine-field-description">' + 
            jetengineSmartSliderData.image_field_tip + 
            '</div>'
        );
    }

    /**
     * Feld-Labels verbessern
     */
    function enhanceFieldLabels() {
        // JetEngine-Icon zu Labels hinzufügen
        $('[id^="n2_generator_filter_"], [id^="n2_generator_image_"], [id^="n2_generator_ordering_"]').each(function() {
            var $label = $(this).closest('.n2_form__item').find('label');
            if (!$label.find('.jetengine-icon').length) {
                $label.prepend('<span class="jetengine-icon"></span> ');
            }
        });
    }

    /**
     * Zeigt Hilfe-Tab an
     */
    function showHelpTab(tabId) {
        // Hilfe-Dialog erstellen, falls noch nicht vorhanden
        var $helpDialog = $('#jetengine-help-dialog');
        
        if (!$helpDialog.length) {
            $helpDialog = $('<div id="jetengine-help-dialog" title="JetEngine Generator Hilfe"></div>');
            $('body').append($helpDialog);
        }
        
        // Hilfe-Inhalt basierend auf Tab-ID laden
        var content = '';
        
        switch (tabId) {
            case 'general':
                content = getGeneralHelpContent();
                break;
            case 'fields':
                content = getFieldsHelpContent();
                break;
            case 'examples':
                content = getExamplesHelpContent();
                break;
            default:
                content = 'Hilfe-Inhalt nicht verfügbar.';
                break;
        }
        
        $helpDialog.html(content);
        
        // Dialog anzeigen
        if ($.fn.dialog) {
            $helpDialog.dialog({
                width: 600,
                modal: true,
                dialogClass: 'jetengine-help-dialog'
            });
        } else {
            // Fallback, falls jQuery UI Dialog nicht verfügbar
            alert('JetEngine Generator Hilfe: ' + tabId);
        }
    }

    /**
     * Gibt allgemeinen Hilfe-Inhalt zurück
     */
    function getGeneralHelpContent() {
        return '<h3>Allgemeine Hilfe</h3>' +
               '<p>Der JetEngine Generator ermöglicht es Ihnen, dynamische Slides aus JetEngine-Inhalten zu erstellen. ' +
               'Sie können Custom Post Types, Custom Content Types oder Relations als Datenquelle verwenden.</p>' +
               '<h4>Grundlegende Schritte:</h4>' +
               '<ol>' +
               '<li>Wählen Sie den JetEngine-Inhaltstyp, den Sie verwenden möchten</li>' +
               '<li>Konfigurieren Sie die Filter- und Sortieroptionen</li>' +
               '<li>Wählen Sie die Bildquelle für Ihre Slides</li>' +
               '<li>Erstellen Sie den Slider und gestalten Sie die Slide-Vorlage</li>' +
               '</ol>';
    }

    /**
     * Gibt Felder-Hilfe-Inhalt zurück
     */
    function getFieldsHelpContent() {
        return '<h3>Felder & Variablen</h3>' +
               '<p>Die folgenden Variablen sind in Ihren Slides verfügbar:</p>' +
               '<h4>Allgemeine Variablen:</h4>' +
               '<ul>' +
               '<li><code>id</code> - Element-ID</li>' +
               '<li><code>title</code> - Titel</li>' +
               '<li><code>url</code> - URL</li>' +
               '<li><code>date</code> - Erstellungsdatum</li>' +
               '<li><code>modified</code> - Änderungsdatum</li>' +
               '<li><code>image</code> - Hauptbild-URL</li>' +
               '</ul>' +
               '<h4>Meta-Feld-Variablen:</h4>' +
               '<p>Meta-Felder werden im Format <code>meta_FELDNAME</code> verfügbar gemacht.</p>' +
               '<p>Für formatierte Werte: <code>meta_FELDNAME_formatted</code></p>';
    }

    /**
     * Gibt Beispiele-Hilfe-Inhalt zurück
     */
    function getExamplesHelpContent() {
        return '<h3>Beispiele</h3>' +
               '<h4>Einfacher Produkt-Slider:</h4>' +
               '<pre>' +
               '&lt;div class="product-slide"&gt;\n' +
               '  &lt;div class="product-image"&gt;\n' +
               '    &lt;img src="{image}" alt="{title}"&gt;\n' +
               '  &lt;/div&gt;\n' +
               '  &lt;div class="product-info"&gt;\n' +
               '    &lt;h3&gt;{title}&lt;/h3&gt;\n' +
               '    &lt;p class="price"&gt;{meta_product_price}&lt;/p&gt;\n' +
               '    &lt;a href="{url}" class="btn"&gt;Details&lt;/a&gt;\n' +
               '  &lt;/div&gt;\n' +
               '&lt;/div&gt;' +
               '</pre>' +
               '<h4>Immobilien-Slider mit Meta-Feldern:</h4>' +
               '<pre>' +
               '&lt;div class="property-slide"&gt;\n' +
               '  &lt;img src="{image}" alt="{title}"&gt;\n' +
               '  &lt;div class="property-details"&gt;\n' +
               '    &lt;h3&gt;{title}&lt;/h3&gt;\n' +
               '    &lt;div class="features"&gt;\n' +
               '      &lt;span&gt;{meta_property_beds} Zimmer&lt;/span&gt;\n' +
               '      &lt;span&gt;{meta_property_baths} Bäder&lt;/span&gt;\n' +
               '      &lt;span&gt;{meta_property_area} m²&lt;/span&gt;\n' +
               '    &lt;/div&gt;\n' +
               '    &lt;p class="price"&gt;{meta_property_price_formatted}&lt;/p&gt;\n' +
               '    &lt;a href="{url}"&gt;Details ansehen&lt;/a&gt;\n' +
               '  &lt;/div&gt;\n' +
               '&lt;/div&gt;' +
               '</pre>';
    }

    /**
     * Gibt den aktuellen Generator-Typ zurück
     */
    function getCurrentGeneratorType() {
        // Versuche, den Generator-Typ aus der URL oder anderen Quellen zu ermitteln
        var match = window.location.href.match(/generator=([^&]+)/);
        if (match && match[1]) {
            return match[1];
        }
        
        // Fallback: Prüfe auf ausgewählten Generator
        var $selected = $('.n2_generator_items__item--selected');
        if ($selected.length) {
            return $selected.data('generatortype');
        }
        
        return null;
    }

})(jQuery);
