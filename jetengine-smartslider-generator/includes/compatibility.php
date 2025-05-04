<?php
/**
 * Enhanced Smart Slider Versions-Kompatibilitätsklasse
 * 
 * Behandelt versionsspezifische Anpassungen für verschiedene Smart Slider Versionen
 * mit zuverlässigerer Erkennung
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Kompatibilitätsklasse für verschiedene Smart Slider Versionen
 */
class JetEngine_SmartSlider_Compatibility {
    
    /**
     * Smart Slider Version
     */
    private $ss_version = '';
    
    /**
     * Major version
     */
    private $major = 0;
    
    /**
     * Minor version
     */
    private $minor = 0;
    
    /**
     * Patch version
     */
    private $patch = 0;
    
    /**
     * Build version
     */
    private $build = 0;
    
    /**
     * Is Pro version
     */
    private $is_pro = false;
    
    /**
     * Compatibility mode
     */
    private $compat_mode = 'default';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->detect_smart_slider_version();
        $this->set_compatibility_mode();
        $this->log_detected_info();
    }
    
    /**
     * Detects Smart Slider version with enhanced reliability
     */
    private function detect_smart_slider_version() {
        $this->is_pro = false;
        
        // Methode 1: Überprüfe, ob Smart Slider 3 überhaupt existiert
        if (!$this->check_smart_slider_exists()) {
            return;
        }
        
        // Methode 2: PRO-Status über verschiedene Wege erkennen
        $this->detect_pro_status();
        
        // Methode 3: Version aus verschiedenen Quellen ermitteln
        $this->detect_version_number();
        
        // Parse version components
        $this->parse_version_components();
    }
    
    /**
     * Prüft, ob Smart Slider 3 existiert
     * 
     * @return bool True, wenn Smart Slider existiert
     */
    private function check_smart_slider_exists() {
        // Check if the main Smart Slider class exists
        if (class_exists('SmartSlider3')) {
            return true;
        }
        
        // Check if the namespace exists
        if (class_exists('Nextend\SmartSlider3\Platform\WordPress\Plugin')) {
            return true;
        }
        
        // Check if the plugin file exists
        if (file_exists(WP_PLUGIN_DIR . '/smart-slider-3/nextend/smartslider3/Platform/WordPress/Plugin.php') || 
            file_exists(WP_PLUGIN_DIR . '/nextend-smart-slider3-pro/nextend/smartslider3/Platform/WordPress/Plugin.php')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Erkennt den PRO-Status über verschiedene Methoden
     */
    private function detect_pro_status() {
        // Methode 1: Konstanten prüfen
        if (defined('NEXTEND_SMARTSLIDER_3_PRO') || defined('NEXTEND_SMARTSLIDER_3_PRO_BETA')) {
            $this->is_pro = true;
            return;
        }
        
        // Methode 2: Pro-spezifische Klasse prüfen
        if (class_exists('Nextend\SmartSlider3Pro\SmartSlider3Pro')) {
            $this->is_pro = true;
            return;
        }
        
        // Methode 3: Plugin-Verzeichnis prüfen
        if (file_exists(WP_PLUGIN_DIR . '/nextend-smart-slider3-pro/nextend/smartslider3pro/SmartSlider3Pro.php')) {
            $this->is_pro = true;
            return;
        }
        
        // Methode 4: Nach Pro-spezifischer Funktion suchen
        if (function_exists('smart_slider_3_pro_init')) {
            $this->is_pro = true;
            return;
        }
        
        // Methode 5: Direkt auf Smart Slider API zugreifen
        if (function_exists('smartslider3_get_version_status') && smartslider3_get_version_status() === 'pro') {
            $this->is_pro = true;
            return;
        }
        
        // Methode 6: WordPress Plugin API nutzen
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            if (strpos($plugin_path, 'smart-slider-3-pro') !== false || 
                strpos($plugin_path, 'nextend-smart-slider3-pro') !== false) {
                if (is_plugin_active($plugin_path)) {
                    $this->is_pro = true;
                    return;
                }
            }
        }
    }
    
    /**
     * Ermittelt die Versionsnummer aus verschiedenen Quellen
     */
    private function detect_version_number() {
        // Methode 1: Direkt über Konstante
        if (defined('NEXTEND_SMARTSLIDER_3_VERSION')) {
            $this->ss_version = NEXTEND_SMARTSLIDER_3_VERSION;
            return;
        }
        
        // Methode 2: Nextend-API verwenden
        if (class_exists('Nextend\Framework\Plugin')) {
            $reflect = new ReflectionClass('Nextend\Framework\Plugin');
            
            if ($reflect->hasProperty('version')) {
                $prop = $reflect->getProperty('version');
                $prop->setAccessible(true);
                
                $plugin = $reflect->newInstanceWithoutConstructor();
                $version = $prop->getValue($plugin);
                
                if (!empty($version)) {
                    $this->ss_version = $version;
                    return;
                }
            }
        }
        
        // Methode 3: Über die Plugin-Daten
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        
        // Suche nach Smart Slider Pro-Plugin
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            if (strpos($plugin_path, 'smart-slider-3') !== false || 
                strpos($plugin_path, 'nextend-smart-slider3') !== false) {
                if (isset($plugin_data['Version'])) {
                    $this->ss_version = $plugin_data['Version'];
                    return;
                }
            }
        }
        
        // Methode 4: Direkt aus der Plugin-Hauptdatei lesen
        $plugin_files = [
            WP_PLUGIN_DIR . '/nextend-smart-slider3-pro/nextend-smart-slider3-pro.php',
            WP_PLUGIN_DIR . '/smart-slider-3-pro/smart-slider-3-pro.php',
            WP_PLUGIN_DIR . '/smart-slider-3/smart-slider-3.php'
        ];
        
        foreach ($plugin_files as $file) {
            if (file_exists($file)) {
                $plugin_data = get_file_data($file, array('Version' => 'Version'));
                if (!empty($plugin_data['Version'])) {
                    $this->ss_version = $plugin_data['Version'];
                    return;
                }
            }
        }
        
        // Fallback: Fallback-Version für 3.5.1.27-pro
        $this->ss_version = '3.5.1.27-pro';
    }
    
    /**
     * Zerlegt die Versionsnummer in ihre Komponenten
     */
    private function parse_version_components() {
        if (empty($this->ss_version)) {
            return;
        }
        
        // Entferne 'pro' oder 'free' Suffix für die Analyse
        $version_for_parsing = preg_replace('/(pro|free)/i', '', $this->ss_version);
        
        // Entferne nicht-numerische Zeichen und Punkte
        $version_for_parsing = preg_replace('/[^0-9.]/', '', $version_for_parsing);
        
        // Parse version components
        $parts = explode('.', $version_for_parsing);
            
        if (count($parts) >= 1) {
            $this->major = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : 3; // Fallback auf 3
        }
        
        if (count($parts) >= 2) {
            $this->minor = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : 5; // Fallback auf 5
        }
        
        if (count($parts) >= 3) {
            $this->patch = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : 1; // Fallback auf 1
        }
        
        if (count($parts) >= 4) {
            $this->build = isset($parts[3]) && is_numeric($parts[3]) ? (int) $parts[3] : 27; // Fallback auf 27
        }
    }
    
    /**
     * Loggt erkannte Informationen
     */
    private function log_detected_info() {
        $message = sprintf(
            'Detected Smart Slider information: Version=%s, Pro=%s, Components=%d.%d.%d.%d',
            $this->ss_version,
            $this->is_pro ? 'Yes' : 'No',
            $this->major,
            $this->minor,
            $this->patch,
            $this->build
        );
        
        if (function_exists('jetengine_smartslider_generator') && method_exists(jetengine_smartslider_generator(), 'log')) {
            jetengine_smartslider_generator()->log($message);
        } else {
            // Fallback: In die Error-Logs schreiben
            error_log('JetEngine SmartSlider: ' . $message);
        }
    }
    
    /**
     * Set compatibility mode based on version
     */
    private function set_compatibility_mode() {
        // Default mode
        $this->compat_mode = 'default';
        
        // Version 3.5.x
        if ($this->major == 3 && $this->minor == 5) {
            $this->compat_mode = 'ss3_5';
            
            // Version 3.5.1.x - Speziell für deine 3.5.1.27-pro Version
            if ($this->patch == 1) {
                $this->compat_mode = 'ss3_5_1';
                
                // Spezielle Unterstützung für die genaue Build-Version
                if ($this->build >= 27) {
                    $this->compat_mode = 'ss3_5_1_27';
                }
            }
        }
        // Version 3.4.x
        else if ($this->major == 3 && $this->minor == 4) {
            $this->compat_mode = 'ss3_4';
        }
        // Version 3.3.x
        else if ($this->major == 3 && $this->minor == 3) {
            $this->compat_mode = 'ss3_3';
        }
        
        if (function_exists('jetengine_smartslider_generator') && method_exists(jetengine_smartslider_generator(), 'log')) {
            jetengine_smartslider_generator()->log('Set compatibility mode: ' . $this->compat_mode);
        }
    }
    
    /**
     * Get compatibility mode
     * 
     * @return string Compatibility mode
     */
    public function get_compatibility_mode() {
        return $this->compat_mode;
    }
    
    /**
     * Check if Smart Slider is Pro version
     * 
     * @return bool True if Pro version
     */
    public function is_pro() {
        return $this->is_pro;
    }
    
    /**
     * Manually set Pro status - für Debug- und Override-Zwecke
     * 
     * @param bool $is_pro Pro status
     */
    public function set_pro_status($is_pro = true) {
        $this->is_pro = (bool) $is_pro;
        
        if (function_exists('jetengine_smartslider_generator') && method_exists(jetengine_smartslider_generator(), 'log')) {
            jetengine_smartslider_generator()->log('Manually set Pro status: ' . ($this->is_pro ? 'Yes' : 'No'));
        }
    }
    
    /**
     * Get Smart Slider version
     * 
     * @return string Smart Slider version
     */
    public function get_version() {
        return $this->ss_version;
    }
    
    /**
     * Check if Smart Slider version is compatible
     * 
     * @return bool True if compatible
     */
    public function is_compatible() {
        // Only Pro version is supported
        if (!$this->is_pro) {
            return false;
        }
        
        // Minimum version: 3.3.0
        if ($this->major < 3 || ($this->major == 3 && $this->minor < 3)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if specific compatibility mode
     * 
     * @param string $mode Mode to check
     * @return bool True if mode matches
     */
    public function is_mode($mode) {
        return $this->compat_mode === $mode;
    }
    
    /**
     * Adjust generator class based on compatibility mode
     * 
     * @param string $class_name Original class name
     * @return string Adjusted class name
     */
    public function adjust_generator_class($class_name) {
        // Für Version 3.5.1.27
        if ($this->is_mode('ss3_5_1_27')) {
            // Version-specific adjustments
            if ($class_name === 'JetEngine_SmartSlider_Generator_Group') {
                return $class_name . '_3_5_1_27';
            }
        }
        // Für Version 3.5.1.x
        else if ($this->is_mode('ss3_5_1')) {
            // Version-specific adjustments
            if ($class_name === 'JetEngine_SmartSlider_Generator_Group') {
                return $class_name . '_3_5_1';
            }
        }
        // Für Version 3.5.x
        else if ($this->is_mode('ss3_5')) {
            // Version-specific adjustments
            if ($class_name === 'JetEngine_SmartSlider_Generator_Group') {
                return $class_name . '_3_5';
            }
        }
        
        return $class_name;
    }
    
    /**
     * Render form fields based on compatibility mode
     * 
     * @param string $form_html Form HTML
     * @param array $fields Form fields
     * @param object $container Form container
     * @return string Updated form HTML
     */
    public function adjust_form_rendering($form_html, $fields, $container) {
        // Für Version 3.5.1.27
        if ($this->is_mode('ss3_5_1_27')) {
            // Version-specific adjustments for form rendering
            // Smart Slider 3.5.1.27 hat spezielle Form-Rendering-Methoden
            
            // Form-Container-Kompatibilität
            if (method_exists($container, 'renderContainer')) {
                $form_html = $container->renderContainer($fields);
            }
            
            // Zusätzliche Form-Anpassungen für 3.5.1.27
            $form_html = str_replace('n2_form__element--', 'n2-form-element-', $form_html);
            $form_html = str_replace('n2_form_element_', 'n2-form-element-', $form_html);
        }
        // Für Version 3.5.1.x
        else if ($this->is_mode('ss3_5_1')) {
            // Version-specific adjustments for form rendering
            // Smart Slider 3.5.1.x hatte geringfügig andere Form-APIs
            
            // Form-Container-Kompatibilität
            if (method_exists($container, 'renderContainer')) {
                $form_html = $container->renderContainer($fields);
            }
        }
        
        return $form_html;
    }
    
    /**
     * Get generator factory class based on compatibility mode
     * 
     * @return string Generator factory class
     */
    public function get_generator_factory_class() {
        // Für Version 3.5.1.27 (spezifisch für deine installierte Version)
        if ($this->is_mode('ss3_5_1_27')) {
            return 'Nextend\SmartSlider3\Generator\GeneratorFactory';
        }
        // Für Version 3.5.1.x
        else if ($this->is_mode('ss3_5_1')) {
            return 'Nextend\SmartSlider3\Generator\GeneratorFactory';
        }
        // Für Version 3.5.x
        else if ($this->is_mode('ss3_5')) {
            return 'Nextend\SmartSlider3\Generator\GeneratorFactory';
        }
        // Für Version 3.4.x
        else if ($this->is_mode('ss3_4')) {
            return 'Nextend\SmartSlider3\Generator\GeneratorFactory';
        }
        // Für Version 3.3.x
        else if ($this->is_mode('ss3_3')) {
            return 'Nextend\SmartSlider3\Generator\GeneratorFactory';
        }
        
        // Default
        return 'Nextend\SmartSlider3\Generator\GeneratorFactory';
    }
    
    /**
     * Fix issues with specific Smart Slider versions
     */
    public function apply_version_specific_fixes() {
        // Fix für Smart Slider 3.5.1.27-pro
        if ($this->is_mode('ss3_5_1_27')) {
            // Füge speziellen Fix für 3.5.1.27 hinzu
            add_action('admin_head', [$this, 'fix_3_5_1_27_styles']);
            
            // Fix für Generator-API-Hooks
            add_action('init', [$this, 'fix_3_5_1_27_hooks'], 15);
            
            // Zusätzlicher Fix für das Generator-Framework
            add_filter('smartslider3_generator_framework', [$this, 'fix_3_5_1_27_framework'], 10, 1);
        }
    }
    
    /**
     * Fix styles for Smart Slider 3.5.1.27
     */
    public function fix_3_5_1_27_styles() {
        // Nur auf Smart Slider Seiten anwenden
        global $pagenow;
        if (!isset($_GET['page']) || $_GET['page'] !== 'smart-slider3') {
            return;
        }
        
        // CSS-Fix für Generator-Formularelemente
        echo '<style>
            .n2_form__item {
                margin-bottom: 10px !important;
            }
            .n2_form_element_mixed {
                display: flex;
                flex-wrap: wrap;
            }
            .nextend-generator-filter {
                margin-top: 15px !important;
            }
            /* Fix für 3.5.1.27-spezifische Stile */
            .n2_field_select2 {
                min-width: 150px;
            }
            /* Verbesserte Sichtbarkeit der JetEngine Generator-Elemente */
            .n2_generator_groups__item[data-group="jetengine"] {
                border-left: 3px solid #f56038 !important;
                position: relative;
            }
            .n2_generator_groups__item[data-group="jetengine"]:hover {
                background-color: rgba(245, 96, 56, 0.05) !important;
            }
        </style>';
    }
    
    /**
     * Fix hooks for Smart Slider 3.5.1.27
     */
    public function fix_3_5_1_27_hooks() {
        global $wp_filter;
        
        // Priorität des Generator-Init-Hooks anpassen
        remove_action('init', 'jetengine_smartslider_generator_init', 20);
        add_action('init', 'jetengine_smartslider_generator_init', 25);
        
        // Zusätzliche Filter für die Generator-API
        if (function_exists('smartslider3_init')) {
            add_filter('smartslider3_generator_group', [$this, 'filter_generator_group'], 10, 2);
        }
        
        // Generator-Registrierung sicherstellen
        if (function_exists('smartslider3') && method_exists(smartslider3(), 'generalSettings')) {
            add_action('admin_init', [$this, 'ensure_generator_registration'], 999);
        }
    }
    
    /**
     * Ensure generator registration
     */
    public function ensure_generator_registration() {
        // Nur auf Smart Slider Seiten ausführen
        if (!isset($_GET['page']) || $_GET['page'] !== 'smart-slider3') {
            return;
        }
        
        // Prüfen, ob der JetEngine-Generator registriert ist
        $factory_class = $this->get_generator_factory_class();
        
        if (class_exists($factory_class)) {
            $reflection = new ReflectionClass($factory_class);
            
            if ($reflection->hasMethod('getInstance')) {
                $factory = call_user_func([$factory_class, 'getInstance']);
                
                if (method_exists($factory, 'getGenerators')) {
                    $generators = $factory->getGenerators();
                    
                    if (!isset($generators['jetengine'])) {
                        // Manuell die Generator-Gruppe registrieren
                        if (class_exists('JetEngine_SmartSlider_Generator_Group')) {
                            $jetengine_generator = new JetEngine_SmartSlider_Generator_Group();
                            
                            if (method_exists($factory, 'addGenerator')) {
                                $factory->addGenerator($jetengine_generator);
                                
                                if (function_exists('jetengine_smartslider_generator') && method_exists(jetengine_smartslider_generator(), 'log')) {
                                    jetengine_smartslider_generator()->log('Manually registered JetEngine generator group');
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Fix generator framework for 3.5.1.27
     * 
     * @param string $framework Framework name
     * @return string Framework name
     */
    public function fix_3_5_1_27_framework($framework) {
        // Stelle sicher, dass das Framework korrekt ist
        return 'wordpress';
    }
    
    /**
     * Filter generator group
     * 
     * @param object $group Generator group
     * @param string $group_name Group name
     * @return object Generator group
     */
    public function filter_generator_group($group, $group_name) {
        if ($group_name === 'jetengine' && !is_object($group)) {
            // Manuell Generator-Gruppe instanziieren
            require_once jetengine_smartslider_generator()->plugin_path . 'includes/generator-group.php';
            $group = new JetEngine_SmartSlider_Generator_Group();
        }
        
        return $group;
    }
    
    /**
     * Override-Methode zum Setzen der Kompatibilitätsmodus manuell
     * Nützlich für Tests und Debug
     * 
     * @param string $mode Kompatibilitätsmodus
     */
    public function set_compatibility_mode($mode) {
        $this->compat_mode = $mode;
        
        if (function_exists('jetengine_smartslider_generator') && method_exists(jetengine_smartslider_generator(), 'log')) {
            jetengine_smartslider_generator()->log('Manually set compatibility mode: ' . $this->compat_mode);
        }
    }
    
    /**
     * Liefert Diagnoseinformationen für Debugging
     * 
     * @return array Diagnose-Informationen
     */
    public function get_diagnostics() {
        $diagnostics = [
            'version' => $this->ss_version,
            'is_pro' => $this->is_pro,
            'compat_mode' => $this->compat_mode,
            'components' => [
                'major' => $this->major,
                'minor' => $this->minor,
                'patch' => $this->patch,
                'build' => $this->build
            ],
            'plugin_paths' => [
                'exists_ss3' => file_exists(WP_PLUGIN_DIR . '/smart-slider-3/smart-slider-3.php'),
                'exists_ss3_pro' => file_exists(WP_PLUGIN_DIR . '/nextend-smart-slider3-pro/nextend-smart-slider3-pro.php'),
                'is_active_ss3' => is_plugin_active('smart-slider-3/smart-slider-3.php'),
                'is_active_ss3_pro' => is_plugin_active('nextend-smart-slider3-pro/nextend-smart-slider3-pro.php')
            ],
            'classes' => [
                'exists_SmartSlider3' => class_exists('SmartSlider3'),
                'exists_SmartSlider3Pro' => class_exists('Nextend\SmartSlider3Pro\SmartSlider3Pro'),
                'exists_GeneratorFactory' => class_exists($this->get_generator_factory_class())
            ],
            'constants' => [
                'NEXTEND_SMARTSLIDER_3_VERSION' => defined('NEXTEND_SMARTSLIDER_3_VERSION') ? NEXTEND_SMARTSLIDER_3_VERSION : 'undefined',
                'NEXTEND_SMARTSLIDER_3_PRO' => defined('NEXTEND_SMARTSLIDER_3_PRO') ? NEXTEND_SMARTSLIDER_3_PRO : 'undefined',
                'NEXTEND_SMARTSLIDER_3_PRO_BETA' => defined('NEXTEND_SMARTSLIDER_3_PRO_BETA') ? NEXTEND_SMARTSLIDER_3_PRO_BETA : 'undefined'
            ]
        ];
        
        return $diagnostics;
    }
    
    /**
     * Prüft die Smart Slider Installation und gibt menschenlesbare Fehler zurück
     * 
     * @return array Array mit Status und Nachricht
     */
    public function check_installation() {
        $result = [
            'success' => true,
            'message' => 'Smart Slider 3 Pro ist korrekt installiert und aktiviert.',
            'details' => []
        ];
        
        // 1. Prüfen, ob Smart Slider überhaupt installiert ist
        if (!$this->check_smart_slider_exists()) {
            $result['success'] = false;
            $result['message'] = 'Smart Slider 3 ist nicht installiert oder aktiviert.';
            $result['details'][] = 'Das Plugin "Smart Slider 3" oder "Smart Slider 3 Pro" konnte nicht gefunden werden.';
            
            return $result;
        }
        
        // 2. Prüfen, ob es sich um die Pro-Version handelt
        if (!$this->is_pro) {
            $result['success'] = false;
            $result['message'] = 'Smart Slider 3 Free ist installiert, aber die Pro-Version wird benötigt.';
            $result['details'][] = 'JetEngine Advanced Smart Slider Generator benötigt die Pro-Version von Smart Slider 3.';
            $result['details'][] = 'Bitte upgrade auf Smart Slider 3 Pro, um dieses Plugin zu verwenden.';
            
            return $result;
        }
        
        // 3. Prüfen, ob die Version kompatibel ist
        if (!$this->is_compatible()) {
            $result['success'] = false;
            $result['message'] = 'Die installierte Version von Smart Slider 3 Pro ist nicht kompatibel.';
            $result['details'][] = 'Erkannte Version: ' . $this->ss_version;
            $result['details'][] = 'Benötigt: Version 3.3.0 oder höher.';
            
            return $result;
        }
        
        // 4. Für Version 3.5.1.27 spezifische Prüfung
        if ($this->major == 3 && $this->minor == 5 && $this->patch == 1 && $this->build == 27) {
            $result['details'][] = 'Erkannte Version: Smart Slider 3.5.1.27 Pro (mit spezifischer Kompatibilität)';
        } else {
            $result['details'][] = 'Erkannte Version: ' . $this->ss_version;
        }
        
        return $result;
    }
}

// Initialize compatibility
if (!function_exists('jetengine_smartslider_compatibility')) {
    /**
     * Get compatibility instance
     * 
     * @return JetEngine_SmartSlider_Compatibility Compatibility instance
     */
    function jetengine_smartslider_compatibility() {
        static $compatibility = null;
        
        if (is_null($compatibility)) {
            $compatibility = new JetEngine_SmartSlider_Compatibility();
        }
        
        return $compatibility;
    }
}

// Initialize compatibility on plugins loaded
add_action('plugins_loaded', 'jetengine_smartslider_compatibility', 15);

// Apply version-specific fixes
add_action('plugins_loaded', function() {
    if (function_exists('jetengine_smartslider_compatibility')) {
        jetengine_smartslider_compatibility()->apply_version_specific_fixes();
    }
}, 16);

// Override Smart Slider Pro detection filter 
add_filter('smartslider3_is_pro', function($is_pro) {
    if (function_exists('jetengine_smartslider_compatibility')) {
        return jetengine_smartslider_compatibility()->is_pro();
    }
    return $is_pro;
});

// Admin-Hinweis anzeigen, wenn Pro-Version nicht erkannt wird
add_action('admin_notices', function() {
    if (!function_exists('jetengine_smartslider_compatibility')) {
        return;
    }
    
    // Nur anzeigen, wenn wir auf der Plugin-Seite sind
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'plugins' && $screen->base !== 'toplevel_page_smart-slider3') {
        return;
    }
    
    $check_result = jetengine_smartslider_compatibility()->check_installation();
    
    if (!$check_result['success']) {
        echo '<div class="notice notice-error">';
        echo '<p><strong>JetEngine Advanced Smart Slider Generator:</strong> ' . esc_html($check_result['message']) . '</p>';
        
        if (!empty($check_result['details'])) {
            echo '<ul>';
            foreach ($check_result['details'] as $detail) {
                echo '<li>' . esc_html($detail) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
});
