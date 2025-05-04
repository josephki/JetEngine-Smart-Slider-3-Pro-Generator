<?php
/**
 * Plugin Name: JetEngine Smart Slider 3 Pro Generator
 * Description: Generator für Smart Slider 3 Pro, der JetEngine CPTs unterstützt
 * Version: 2.3
 * Author: Joseph Kisler - Webwerkstatt
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) exit;

// Debug-Protokoll-Funktion
function jetengine_ss3_debug_log($message) {
    // Debug in die Admin-Hinweise ausgeben
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-info"><p>JetEngine SS3 Debug: ' . esc_html($message) . '</p></div>';
        });
        
        // Zusätzlich in die Fehlerprotokoll-Datei schreiben
        error_log('JetEngine SS3 Debug: ' . $message);
    }
}

// Nachträgliche Aktivierung des Plugins
register_activation_hook(__FILE__, function() {
    jetengine_ss3_debug_log('Plugin aktiviert');
});

// Erst später initialisieren, um sicherzustellen, dass alle Smart Slider Klassen geladen sind
add_action('init', function() {
    // Prüfen, ob die benötigten Klassen vorhanden sind
    if (!class_exists('Nextend\SmartSlider3\Generator\AbstractGeneratorGroup')) {
        jetengine_ss3_debug_log('Smart Slider Generator-Klassen noch nicht verfügbar');
        return;
    }
    
    jetengine_ss3_debug_log('Smart Slider Generator-Klassen gefunden, lade Generator...');
    
    // Generator-Klassen laden
    require_once(plugin_dir_path(__FILE__) . 'class-jetengine-generator.php');
    
    // Generator direkt bei der Factory registrieren
    try {
        if (class_exists('Nextend\SmartSlider3\Generator\GeneratorFactory')) {
            $factory = \Nextend\SmartSlider3\Generator\GeneratorFactory::getInstance();
            
            // Neue Instanz des Generators erstellen
            $jetengine_generator = new JetEngineGeneratorGroup();
            
            // Bei der Factory registrieren
            $factory->addGenerator($jetengine_generator);
            
            jetengine_ss3_debug_log('JetEngine Generator direkt bei der Factory registriert');
        }
    } catch (Exception $e) {
        jetengine_ss3_debug_log('Fehler bei der Registrierung: ' . $e->getMessage());
    }
}, 50); // Mittlere Priorität

// Spätes Laden für den Admin-Bereich
add_action('admin_init', function() {
    // Prüfen, ob wir uns auf einer Smart Slider Seite befinden
    $is_smartslider_page = isset($_GET['page']) && ($_GET['page'] === 'smart-slider3' || strpos($_GET['page'], 'nextend-smart-slider') !== false);
    
    if ($is_smartslider_page && class_exists('Nextend\SmartSlider3\Generator\GeneratorFactory')) {
        jetengine_ss3_debug_log('Smart Slider Admin-Seite erkannt');
        
        try {
            // Prüfen, ob der Generator bereits registriert ist
            $factory = \Nextend\SmartSlider3\Generator\GeneratorFactory::getInstance();
            $reflection = new ReflectionClass($factory);
            
            if ($reflection->hasProperty('generators')) {
                $generators_prop = $reflection->getProperty('generators');
                $generators_prop->setAccessible(true);
                $generators = $generators_prop->getValue($factory);
                
                // Ist unser Generator bereits registriert?
                if (isset($generators['jetengine'])) {
                    jetengine_ss3_debug_log('JetEngine Generator bereits registriert');
                } else {
                    jetengine_ss3_debug_log('JetEngine Generator noch nicht registriert, registriere jetzt...');
                    
                    // Generator-Klassen laden, falls noch nicht geschehen
                    if (!class_exists('JetEngineGeneratorGroup')) {
                        require_once(plugin_dir_path(__FILE__) . 'class-jetengine-generator.php');
                    }
                    
                    // Neue Instanz des Generators erstellen und registrieren
                    $jetengine_generator = new JetEngineGeneratorGroup();
                    $factory->addGenerator($jetengine_generator);
                    
                    jetengine_ss3_debug_log('JetEngine Generator auf Admin-Seite registriert');
                }
            }
            
            // Vorsichtig versuchen, den Cache zu leeren
            try {
                // Version 1: ApplicationTypeFrontend::clearCache()
                if (class_exists('Nextend\SmartSlider3\Application\Frontend\ApplicationTypeFrontend')) {
                    if (method_exists('Nextend\SmartSlider3\Application\Frontend\ApplicationTypeFrontend', 'clearCache')) {
                        \Nextend\SmartSlider3\Application\Frontend\ApplicationTypeFrontend::clearCache();
                        jetengine_ss3_debug_log('Cache über ApplicationTypeFrontend geleert');
                    }
                }
                
                // Version 2: SmartSlider3Platform::clearCache()
                if (class_exists('Nextend\SmartSlider3\Platform\WordPress\SmartSlider3Platform')) {
                    if (method_exists('Nextend\SmartSlider3\Platform\WordPress\SmartSlider3Platform', 'clearCache')) {
                        \Nextend\SmartSlider3\Platform\WordPress\SmartSlider3Platform::clearCache();
                        jetengine_ss3_debug_log('Cache über SmartSlider3Platform geleert');
                    }
                }
                
                // Alternatives Cache-Leeren via Admin-URL
                global $wpdb;
                $table_prefix = $wpdb->prefix;
                
                // Cache-Tabellen-Ansätze
                $cache_tables = [
                    $table_prefix . 'nextend2_section_storage',
                    $table_prefix . 'nextend2_smartslider3_generators_cache',
                ];
                
                foreach ($cache_tables as $table) {
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                        $wpdb->query("DELETE FROM $table WHERE `application` LIKE '%smartslider%' AND `section` LIKE '%cache%'");
                        jetengine_ss3_debug_log('Cache aus DB-Tabelle ' . $table . ' geleert');
                    }
                }
                
                // Browser-Cache-Kontrolle für diese Seite deaktivieren
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Pragma: no-cache");
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
            } catch (Exception $e) {
                jetengine_ss3_debug_log('Cache leeren fehlgeschlagen: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            jetengine_ss3_debug_log('Fehler auf Admin-Seite: ' . $e->getMessage());
        }
    }
}, 999); // Sehr späte Priorität

// Struktur eines bestehenden Generators anzeigen
add_action('admin_footer', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'smart-slider') !== false && 
        isset($_GET['ss3analyzer']) && $_GET['ss3analyzer'] == '1') {
        
        if (class_exists('Nextend\SmartSlider3\Generator\GeneratorFactory')) {
            try {
                $factory = \Nextend\SmartSlider3\Generator\GeneratorFactory::getInstance();
                $reflection = new ReflectionClass($factory);
                
                if ($reflection->hasProperty('generators')) {
                    $generators_prop = $reflection->getProperty('generators');
                    $generators_prop->setAccessible(true);
                    $generators = $generators_prop->getValue($factory);
                    
                    echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
                    echo '<h3>Generator-Analyse:</h3>';
                    
                    // Registrierte Generatoren auflisten
                    echo '<h4>Registrierte Generatoren:</h4>';
                    echo '<ul>';
                    foreach ($generators as $key => $generator) {
                        echo '<li>' . esc_html($key) . ' (Klasse: ' . get_class($generator) . ')</li>';
                    }
                    echo '</ul>';
                    
                    // Posts Generator analysieren
                    if (isset($generators['posts'])) {
                        $posts_generator = $generators['posts'];
                        echo '<h4>Posts Generator Struktur:</h4>';
                        echo '<pre>';
                        $vars = get_object_vars($posts_generator);
                        foreach ($vars as $prop => $value) {
                            echo esc_html($prop) . ': ';
                            if (is_scalar($value)) {
                                echo esc_html(var_export($value, true));
                            } else {
                                echo gettype($value);
                            }
                            echo "\n";
                        }
                        
                        echo '</pre>';
                    }
                    
                    // Unser Generator
                    if (isset($generators['jetengine'])) {
                        $our_generator = $generators['jetengine'];
                        echo '<h4>JetEngine Generator Struktur:</h4>';
                        echo '<pre>';
                        $vars = get_object_vars($our_generator);
                        foreach ($vars as $prop => $value) {
                            echo esc_html($prop) . ': ';
                            if (is_scalar($value)) {
                                echo esc_html(var_export($value, true));
                            } else {
                                echo gettype($value);
                            }
                            echo "\n";
                        }
                        echo '</pre>';
                    }
                    
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #f00;">';
                echo '<h3>Fehler bei Generator-Analyse:</h3>';
                echo '<pre>' . esc_html($e->getMessage()) . '</pre>';
                echo '</div>';
            }
        }
    }
});

// Versuche, den Generator direkt im Frontend zu registrieren
add_action('admin_footer', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'smart-slider') !== false) {
        echo '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            // Warte auf das JavaScript-Framework von Smart Slider
            var checkExistence = setInterval(function() {
                if (window.N2Classes && window.N2Classes.Form && window.N2Classes.Form.Element && window.N2Classes.Form.Element.GeneratorPicker) {
                    clearInterval(checkExistence);
                    console.log("Smart Slider JS-Framework gefunden, registriere JetEngine Generator...");
                    
                    // Original-Methode überschreiben
                    var originalGetGenerators = window.N2Classes.Form.Element.GeneratorPicker.prototype.getGenerators;
                    window.N2Classes.Form.Element.GeneratorPicker.prototype.getGenerators = function() {
                        var generators = originalGetGenerators.apply(this, arguments);
                        if (generators && !generators.jetengine) {
                            console.log("JetEngine zu Generator-Liste hinzufügen");
                            generators.jetengine = {"title": "JetEngine CPT"};
                        }
                        console.log("Verfügbare Generatoren:", Object.keys(generators));
                        return generators;
                    };
                }
            }, 500);
        });
        </script>';
    }
});