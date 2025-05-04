<?php
/**
 * Plugin Name: JetEngine Advanced Smart Slider 3 Generator
 * Description: Advanced integration between JetEngine and Smart Slider 3 Pro with support for CPTs, meta fields, taxonomies, and relations
 * Version: 1.1.0
 * Author: Joseph Kisler - Webwerkstatt
 * Text Domain: jetengine-smartslider
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Main plugin class
 */
class JetEngine_SmartSlider_Generator {

    /**
     * Plugin directory path
     */
    public $plugin_path;

    /**
     * Plugin directory URL
     */
    public $plugin_url;

    /**
     * Base asset URL for icons and resources
     */
    public $asset_url;

    /**
     * Instance of the plugin
     */
    private static $instance = null;

    /**
     * Holds debug mode state
     */
    private $debug_mode = false;

    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->asset_url = $this->plugin_url . 'assets/';

        // Check if debug mode is enabled
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG === true;

        $this->register_hooks();
    }

    /**
     * Register plugin hooks
     */
    public function register_hooks() {
        // Load compatibility class first
        add_action('plugins_loaded', [$this, 'load_compatibility'], 5);
        
        // Load plugin files
        add_action('plugins_loaded', [$this, 'load_dependencies'], 10);

        // Initialize Smart Slider 3 generator
        add_action('init', [$this, 'initialize_generator'], 20);

        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);

        // Register assets
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
        
        // Admin notice for compatibility issues
        add_action('admin_notices', [$this, 'show_compatibility_notices']);
    }
    
    /**
     * Load compatibility class first
     */
    public function load_compatibility() {
        // Load compatibility helper
        require_once $this->plugin_path . 'includes/compatibility.php';
    }

    /**
     * Load plugin dependencies
     */
    public function load_dependencies() {
        // Check if compatibility class is loaded
        if (!function_exists('jetengine_smartslider_compatibility')) {
            $this->log('Compatibility class not loaded');
            return;
        }
        
        // Get compatibility status
        $compatibility = jetengine_smartslider_compatibility();
        $check_result = $compatibility->check_installation();
        
        // Wenn keine erfolgreiche Prüfung, zeige nur Warnungen an, lade aber nichts weiter
        if (!$check_result['success']) {
            $this->log('Smart Slider compatibility check failed: ' . $check_result['message']);
            return;
        }
        
        // Check if JetEngine is active
        if (!class_exists('Jet_Engine')) {
            $this->log('JetEngine is not active');
            return;
        }

        // Load classes
        require_once $this->plugin_path . 'includes/helper.php';
        require_once $this->plugin_path . 'includes/generator-sources.php';
        require_once $this->plugin_path . 'includes/generator-group.php';
        
        $this->log('Plugin dependencies loaded successfully');
    }

    /**
     * Initialize the generator
     */
    public function initialize_generator() {
        // Prüfe, ob Kompatibilitätsklasse geladen ist
        if (!function_exists('jetengine_smartslider_compatibility')) {
            return;
        }
        
        // Prüfe, ob Smart Slider 3 Pro kompatibel ist
        $compatibility = jetengine_smartslider_compatibility();
        if (!$compatibility->is_compatible()) {
            return;
        }
        
        // Prüfe, ob die notwendigen Klassen existieren
        $generator_factory_class = $compatibility->get_generator_factory_class();
        
        if (!class_exists($generator_factory_class) || !class_exists('JetEngine_SmartSlider_Generator_Group')) {
            $this->log('Required generator classes not found');
            return;
        }

        // Hookpoint for Smart Slider 3 to register new generators
        add_action('smartslider3_generator_init', [$this, 'register_generator']);

        $this->log('JetEngine SmartSlider Generator initialized');
    }

    /**
     * Register the generator with Smart Slider 3
     */
    public function register_generator() {
        try {
            // Hole kompatible Factory-Klasse
            $factory_class = jetengine_smartslider_compatibility()->get_generator_factory_class();
            
            if (class_exists($factory_class) && method_exists($factory_class, 'getInstance')) {
                $factory = call_user_func([$factory_class, 'getInstance']);
                
                // Create new instance of the generator group
                $jetengine_generator = new JetEngine_SmartSlider_Generator_Group();
                
                // Register with the factory
                if (method_exists($factory, 'addGenerator')) {
                    $factory->addGenerator($jetengine_generator);
                    $this->log('JetEngine Generator registered with Smart Slider 3');
                } else {
                    $this->log('addGenerator method not found in factory class');
                }
            } else {
                $this->log('Smart Slider 3 generator factory not available');
            }
        } catch (Exception $e) {
            $this->log('Error registering generator: ' . $e->getMessage());
        }
    }

    /**
     * Register admin assets
     */
    public function register_admin_assets($hook) {
        // Only load assets on Smart Slider pages
        if (strpos($hook, 'smart-slider') === false) {
            return;
        }

        // Register and enqueue styles
        wp_register_style(
            'jetengine-smartslider',
            $this->asset_url . 'css/admin.css',
            [],
            '1.0.0'
        );
        wp_enqueue_style('jetengine-smartslider');

        // Register and enqueue scripts
        wp_register_script(
            'jetengine-smartslider',
            $this->asset_url . 'js/admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // Lokalisiere das Script mit Übersetzungen und Nonce
        wp_localize_script('jetengine-smartslider', 'jetengineSmartSliderData', [
            'nonce' => wp_create_nonce('jetengine_smartslider_nonce'),
            'select_field' => __('Feld auswählen', 'jetengine-smartslider'),
            'select_meta_key' => __('Meta-Key auswählen', 'jetengine-smartslider'),
            'select_taxonomies' => __('Taxonomien auswählen', 'jetengine-smartslider'),
            'meta_field_tip' => __('Geben Sie den Namen des JetEngine-Meta-Felds ein, nach dem gefiltert werden soll.', 'jetengine-smartslider'),
            'image_field_tip' => __('Wählen Sie das Feld, das das Bild oder die Galerie enthält.', 'jetengine-smartslider')
        ]);
        
        wp_enqueue_script('jetengine-smartslider');
    }

    /**
     * Add plugin action links
     * 
     * @param array $links Existing action links
     * @return array Modified action links
     */
    public function add_plugin_action_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=smart-slider3') . '">' . __('Smart Slider', 'jetengine-smartslider') . '</a>',
            '<a href="' . admin_url('admin.php?page=jet-engine') . '">' . __('JetEngine', 'jetengine-smartslider') . '</a>',
        ];
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Show compatibility notices
     */
    public function show_compatibility_notices() {
        // Prüfen, ob die Kompatibilitätsklasse geladen ist
        if (!function_exists('jetengine_smartslider_compatibility')) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>JetEngine Advanced Smart Slider Generator:</strong> ' . 
                 esc_html__('Kompatibilitätsmodul konnte nicht geladen werden.', 'jetengine-smartslider') . 
                 '</p>';
            echo '</div>';
            return;
        }
        
        // Prüfe, ob wir auf der Plugin-Seite sind
        $screen = get_current_screen();
        if (!$screen || ($screen->base !== 'plugins' && $screen->base !== 'toplevel_page_jet-engine' && $screen->base !== 'toplevel_page_smart-slider3')) {
            return;
        }
        
        // Hole die Kompatibilitätsprüfung
        $check_result = jetengine_smartslider_compatibility()->check_installation();
        
        if (!$check_result['success']) {
            // Zeige Smart Slider-spezifischen Fehler
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
            return;
        }
        
        // Prüfe, ob JetEngine aktiv ist
        if (!class_exists('Jet_Engine')) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>JetEngine Advanced Smart Slider Generator:</strong> ' . 
                 esc_html__('JetEngine muss installiert und aktiviert sein, um dieses Plugin zu verwenden.', 'jetengine-smartslider') . 
                 '</p>';
            echo '</div>';
            return;
        }
    }

    /**
     * Log debug information
     * 
     * @param string $message Message to log
     */
    public function log($message) {
        if ($this->debug_mode) {
            if (is_array($message) || is_object($message)) {
                error_log('JetEngine SmartSlider Debug: ' . print_r($message, true));
            } else {
                error_log('JetEngine SmartSlider Debug: ' . $message);
            }
        }
    }

    /**
     * Get plugin instance
     * 
     * @return JetEngine_SmartSlider_Generator Plugin instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
}

/**
 * Initialize the plugin
 */
function jetengine_smartslider_generator() {
    return JetEngine_SmartSlider_Generator::instance();
}

/**
 * Start the plugin
 */
jetengine_smartslider_generator();

/**
 * Helper function to initialize Smart Slider 3 generator
 */
function jetengine_smartslider_generator_init() {
    if (function_exists('jetengine_smartslider_generator')) {
        jetengine_smartslider_generator()->initialize_generator();
    }
}

/**
 * Create required directories on plugin activation
 */
register_activation_hook(__FILE__, function() {
    // Create assets directory if it doesn't exist
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($assets_dir)) {
        mkdir($assets_dir, 0755, true);
    }
    
    // Create assets/css directory if it doesn't exist
    $css_dir = $assets_dir . '/css';
    if (!file_exists($css_dir)) {
        mkdir($css_dir, 0755, true);
    }
    
    // Create assets/js directory if it doesn't exist
    $js_dir = $assets_dir . '/js';
    if (!file_exists($js_dir)) {
        mkdir($js_dir, 0755, true);
    }
    
    // Create assets/images directory if it doesn't exist
    $images_dir = $assets_dir . '/images';
    if (!file_exists($images_dir)) {
        mkdir($images_dir, 0755, true);
    }
    
    // Create includes directory if it doesn't exist
    $includes_dir = plugin_dir_path(__FILE__) . 'includes';
    if (!file_exists($includes_dir)) {
        mkdir($includes_dir, 0755, true);
    }

    // Create basic CSS file
    $css_file = $css_dir . '/admin.css';
    if (!file_exists($css_file)) {
        file_put_contents($css_file, "/* JetEngine SmartSlider Admin Styles */\n.jetengine-smartslider-icon {\n    width: 24px;\n    height: 24px;\n}");
    }
    
    // Create basic JS file
    $js_file = $js_dir . '/admin.js';
    if (!file_exists($js_file)) {
        file_put_contents($js_file, "/* JetEngine SmartSlider Admin Scripts */\njQuery(document).ready(function($) {\n    console.log('JetEngine SmartSlider Generator loaded');\n});");
    }
});
