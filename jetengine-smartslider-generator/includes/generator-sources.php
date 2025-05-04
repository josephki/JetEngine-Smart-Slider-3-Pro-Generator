<?php
/**
 * Generator Sources Registration
 * 
 * This file registers and initializes the various generator sources for JetEngine
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Register generator sources
 */
class JetEngine_SmartSlider_Generator_Sources {
    
    /**
     * Sources container
     */
    private $sources = [];
    
    /**
     * Initialize sources
     */
    public function __construct() {
        $this->register_sources();
    }
    
    /**
     * Register all available sources
     */
    private function register_sources() {
        // Register CPT source
        $this->register_post_type_source();
        
        // Register CCT source if available
        $this->register_content_type_source();
        
        // Register Relations source if available
        $this->register_relation_source();
        
        // Register Query Builder source if available
        $this->register_query_builder_source();
    }
    
    /**
     * Register Custom Post Type source
     */
    private function register_post_type_source() {
        // Load the class file if not loaded
        if (!class_exists('JetEngine_SmartSlider_Source_PostType')) {
            require_once jetengine_smartslider_generator()->plugin_path . 'includes/sources/post-type.php';
        }
        
        // Register the source
        $this->sources['post_type'] = 'JetEngine_SmartSlider_Source_PostType';
        
        jetengine_smartslider_generator()->log('Post Type source registered');
    }
    
    /**
     * Register Custom Content Type source
     */
    private function register_content_type_source() {
        // Check if CCT module is active
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || !jet_engine()->modules->is_module_active('custom-content-types')) {
            return;
        }
        
        // Load the class file if not loaded
        if (!class_exists('JetEngine_SmartSlider_Source_ContentType')) {
            require_once jetengine_smartslider_generator()->plugin_path . 'includes/sources/content-type.php';
        }
        
        // Register the source
        $this->sources['content_type'] = 'JetEngine_SmartSlider_Source_ContentType';
        
        jetengine_smartslider_generator()->log('Custom Content Type source registered');
    }
    
    /**
     * Register Relations source
     */
    private function register_relation_source() {
        // Check if Relations is available
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'relations')) {
            return;
        }
        
        // Load the class file if not loaded
        if (!class_exists('JetEngine_SmartSlider_Source_Relation')) {
            require_once jetengine_smartslider_generator()->plugin_path . 'includes/sources/relation.php';
        }
        
        // Register the source
        $this->sources['relation'] = 'JetEngine_SmartSlider_Source_Relation';
        
        jetengine_smartslider_generator()->log('Relation source registered');
    }
    
    /**
     * Register Query Builder source
     */
    private function register_query_builder_source() {
        // Check if Query Builder module is active
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || !jet_engine()->modules->is_module_active('query-builder')) {
            return;
        }
        
        // Load the class file if not loaded
        if (!class_exists('JetEngine_SmartSlider_Source_Query')) {
            require_once jetengine_smartslider_generator()->plugin_path . 'includes/sources/query.php';
        }
        
        // Register the source
        $this->sources['query'] = 'JetEngine_SmartSlider_Source_Query';
        
        jetengine_smartslider_generator()->log('Query Builder source registered');
    }
    
    /**
     * Get all registered sources
     * 
     * @return array Array of registered sources
     */
    public function get_sources() {
        return $this->sources;
    }
    
    /**
     * Get source class
     * 
     * @param string $source_type Source type
     * @return string|null Source class name or null if not found
     */
    public function get_source_class($source_type) {
        return isset($this->sources[$source_type]) ? $this->sources[$source_type] : null;
    }
    
    /**
     * Check if source is registered
     * 
     * @param string $source_type Source type
     * @return bool True if source is registered
     */
    public function has_source($source_type) {
        return isset($this->sources[$source_type]);
    }
    
    /**
     * Get all available JetEngine post types
     * 
     * @return array Array of post types
     */
    public function get_post_types() {
        $post_types = [];
        
        // Get JetEngine post types
        $jet_post_types = JetEngine_SmartSlider_Helper::get_jet_post_types();
        
        // Get built-in post types
        $builtin_post_types = get_post_types(['_builtin' => true, 'public' => true], 'objects');
        
        // Skip some built-in post types
        $skip_types = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'];
        
        // Merge the post types
        $all_post_types = array_merge($jet_post_types, $builtin_post_types);
        
        foreach ($all_post_types as $post_type) {
            // Skip excluded post types
            if (in_array($post_type->name, $skip_types)) {
                continue;
            }
            
            // Add to result
            $post_types[$post_type->name] = $post_type;
        }
        
        return $post_types;
    }
    
    /**
     * Get all available JetEngine content types
     * 
     * @return array Array of content types
     */
    public function get_content_types() {
        // Use helper to get CCTs
        return JetEngine_SmartSlider_Helper::get_jet_ccts();
    }
    
    /**
     * Get all available JetEngine relations
     * 
     * @return array Array of relations
     */
    public function get_relations() {
        // Use helper to get relations
        return JetEngine_SmartSlider_Helper::get_jet_relations();
    }
    
    /**
     * Get all available JetEngine queries
     * 
     * @return array Array of queries
     */
    public function get_queries() {
        // Use helper to get queries
        return JetEngine_SmartSlider_Helper::get_jet_queries();
    }
    
    /**
     * Get meta fields for post type
     * 
     * @param string $post_type Post type name
     * @return array Array of meta fields
     */
    public function get_meta_fields($post_type) {
        // Use helper to get meta fields
        return JetEngine_SmartSlider_Helper::get_jet_meta_fields($post_type);
    }
    
    /**
     * Get taxonomies for post type
     * 
     * @param string $post_type Post type name
     * @return array Array of taxonomies
     */
    public function get_taxonomies($post_type) {
        $taxonomies = [];
        
        // Get taxonomies for post type
        $post_taxonomies = get_object_taxonomies($post_type, 'objects');
        
        if (!empty($post_taxonomies)) {
            foreach ($post_taxonomies as $taxonomy) {
                $taxonomies[$taxonomy->name] = [
                    'name'  => $taxonomy->name,
                    'label' => $taxonomy->labels->name
                ];
            }
        }
        
        return $taxonomies;
    }
    
    /**
     * Get terms for taxonomy
     * 
     * @param string $taxonomy Taxonomy name
     * @return array Array of terms
     */
    public function get_terms($taxonomy) {
        $terms = [];
        
        // Get terms for taxonomy
        $taxonomy_terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false
        ]);
        
        if (!is_wp_error($taxonomy_terms) && !empty($taxonomy_terms)) {
            foreach ($taxonomy_terms as $term) {
                $terms[$term->term_id] = [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count
                ];
            }
        }
        
        return $terms;
    }
}

// Initialize sources
if (!function_exists('jetengine_smartslider_generator_sources')) {
    /**
     * Get sources instance
     * 
     * @return JetEngine_SmartSlider_Generator_Sources Sources instance
     */
    function jetengine_smartslider_generator_sources() {
        static $sources = null;
        
        if (is_null($sources)) {
            $sources = new JetEngine_SmartSlider_Generator_Sources();
        }
        
        return $sources;
    }
}

// Initialize sources on plugins loaded
add_action('plugins_loaded', 'jetengine_smartslider_generator_sources', 20);

/**
 * AJAX Handlers for dynamic data
 */

// Get meta fields for post type
add_action('wp_ajax_jetengine_smartslider_get_meta_fields', 'jetengine_smartslider_ajax_get_meta_fields');
function jetengine_smartslider_ajax_get_meta_fields() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Check if generator type is set
    if (!isset($_POST['generator_type'])) {
        wp_send_json_error(['message' => 'Generator type not specified']);
    }
    
    // Get generator type
    $generator_type = sanitize_text_field($_POST['generator_type']);
    
    // Get post type from generator type
    $post_type = '';
    if (strpos($generator_type, 'post_type_') === 0) {
        $post_type = substr($generator_type, strlen('post_type_'));
    } else {
        wp_send_json_error(['message' => 'Invalid generator type']);
    }
    
    // Get meta fields
    $meta_fields = jetengine_smartslider_generator_sources()->get_meta_fields($post_type);
    
    // Send response
    wp_send_json_success(['fields' => $meta_fields]);
}

// Get taxonomies for post type
add_action('wp_ajax_jetengine_smartslider_get_taxonomies', 'jetengine_smartslider_ajax_get_taxonomies');
function jetengine_smartslider_ajax_get_taxonomies() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Check if generator type is set
    if (!isset($_POST['generator_type'])) {
        wp_send_json_error(['message' => 'Generator type not specified']);
    }
    
    // Get generator type
    $generator_type = sanitize_text_field($_POST['generator_type']);
    
    // Get post type from generator type
    $post_type = '';
    if (strpos($generator_type, 'post_type_') === 0) {
        $post_type = substr($generator_type, strlen('post_type_'));
    } else {
        wp_send_json_error(['message' => 'Invalid generator type']);
    }
    
    // Get taxonomies
    $taxonomies = jetengine_smartslider_generator_sources()->get_taxonomies($post_type);
    
    // Send response
    wp_send_json_success(['taxonomies' => $taxonomies]);
}

// Get terms for taxonomy
add_action('wp_ajax_jetengine_smartslider_get_terms', 'jetengine_smartslider_ajax_get_terms');
function jetengine_smartslider_ajax_get_terms() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Check if taxonomy is set
    if (!isset($_POST['taxonomy'])) {
        wp_send_json_error(['message' => 'Taxonomy not specified']);
    }
    
    // Get taxonomy
    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    
    // Get terms
    $terms = jetengine_smartslider_generator_sources()->get_terms($taxonomy);
    
    // Send response
    wp_send_json_success(['terms' => $terms]);
}

// Get CCT fields
add_action('wp_ajax_jetengine_smartslider_get_cct_fields', 'jetengine_smartslider_ajax_get_cct_fields');
function jetengine_smartslider_ajax_get_cct_fields() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Check if generator type is set
    if (!isset($_POST['generator_type'])) {
        wp_send_json_error(['message' => 'Generator type not specified']);
    }
    
    // Get generator type
    $generator_type = sanitize_text_field($_POST['generator_type']);
    
    // Get CCT slug from generator type
    $cct_slug = '';
    if (strpos($generator_type, 'cct_') === 0) {
        $cct_slug = substr($generator_type, strlen('cct_'));
    } else {
        wp_send_json_error(['message' => 'Invalid generator type']);
    }
    
    // Get CCTs
    $ccts = jetengine_smartslider_generator_sources()->get_content_types();
    
    // Check if CCT exists
    if (!isset($ccts[$cct_slug])) {
        wp_send_json_error(['message' => 'Content type not found']);
    }
    
    // Get CCT
    $cct = $ccts[$cct_slug];
    
    // Get CCT fields
    $fields = [];
    if (isset($cct->meta_fields)) {
        foreach ($cct->meta_fields as $field) {
            if (isset($field['name']) && isset($field['type'])) {
                $fields[] = [
                    'name'  => $field['name'],
                    'type'  => $field['type'],
                    'title' => isset($field['title']) ? $field['title'] : $field['name']
                ];
            }
        }
    }
    
    // Send response
    wp_send_json_success(['fields' => $fields]);
}

// Get relation objects
add_action('wp_ajax_jetengine_smartslider_get_relation_objects', 'jetengine_smartslider_ajax_get_relation_objects');
function jetengine_smartslider_ajax_get_relation_objects() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Check if generator type is set
    if (!isset($_POST['generator_type'])) {
        wp_send_json_error(['message' => 'Generator type not specified']);
    }
    
    // Get generator type
    $generator_type = sanitize_text_field($_POST['generator_type']);
    
    // Get relation ID from generator type
    $relation_id = '';
    if (strpos($generator_type, 'relation_') === 0) {
        $relation_id = substr($generator_type, strlen('relation_'));
    } else {
        wp_send_json_error(['message' => 'Invalid generator type']);
    }
    
    // Get relations
    $relations = jetengine_smartslider_generator_sources()->get_relations();
    
    // Check if relation exists
    if (!isset($relations[$relation_id])) {
        wp_send_json_error(['message' => 'Relation not found']);
    }
    
    // Get relation
    $relation = $relations[$relation_id];
    
    // Get relation objects
    $objects = [
        ['value' => 'children', 'label' => __('Get children objects', 'jetengine-smartslider')],
        ['value' => 'parent', 'label' => __('Get parent objects', 'jetengine-smartslider')]
    ];
    
    // Send response
    wp_send_json_success(['objects' => $objects]);
}

// Get meta keys for ordering
add_action('wp_ajax_jetengine_smartslider_get_meta_keys', 'jetengine_smartslider_ajax_get_meta_keys');
function jetengine_smartslider_ajax_get_meta_keys() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Check if generator type is set
    if (!isset($_POST['generator_type'])) {
        wp_send_json_error(['message' => 'Generator type not specified']);
    }
    
    // Get generator type
    $generator_type = sanitize_text_field($_POST['generator_type']);
    
    // Get source type and ID
    $source_type = '';
    $source_id = '';
    
    // Parse generator type
    if (strpos($generator_type, 'post_type_') === 0) {
        $source_type = 'post_type';
        $source_id = substr($generator_type, strlen('post_type_'));
    } elseif (strpos($generator_type, 'cct_') === 0) {
        $source_type = 'cct';
        $source_id = substr($generator_type, strlen('cct_'));
    } elseif (strpos($generator_type, 'relation_') === 0) {
        $source_type = 'relation';
        $source_id = substr($generator_type, strlen('relation_'));
    } else {
        wp_send_json_error(['message' => 'Invalid generator type']);
    }
    
    // Get meta keys
    $meta_keys = [];
    
    if ($source_type === 'post_type') {
        // Get meta fields for post type
        $meta_fields = jetengine_smartslider_generator_sources()->get_meta_fields($source_id);
        
        foreach ($meta_fields as $field_name => $field) {
            $meta_keys[] = [
                'name'  => $field_name,
                'title' => isset($field['title']) ? $field['title'] : $field_name
            ];
        }
    } elseif ($source_type === 'cct') {
        // Get CCTs
        $ccts = jetengine_smartslider_generator_sources()->get_content_types();
        
        // Check if CCT exists
        if (isset($ccts[$source_id]) && isset($ccts[$source_id]->meta_fields)) {
            foreach ($ccts[$source_id]->meta_fields as $field) {
                if (isset($field['name'])) {
                    $meta_keys[] = [
                        'name'  => $field['name'],
                        'title' => isset($field['title']) ? $field['title'] : $field['name']
                    ];
                }
            }
        }
    }
    
    // Send response
    wp_send_json_success(['keys' => $meta_keys]);
}
