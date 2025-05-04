<?php
/**
 * JetEngine Generator Group
 * 
 * This class handles the main generator group that will appear in Smart Slider 3 Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

use Nextend\SmartSlider3\Generator\AbstractGeneratorGroup;
use Nextend\Framework\Pattern\GetAssetsPathTrait;

class JetEngine_SmartSlider_Generator_Group extends AbstractGeneratorGroup {
    
    protected $name = 'jetengine';
    protected $url = '';
    protected $displayName = 'JetEngine';
    
    /**
     * Generator Group constructor
     */
    public function __construct() {
        parent::__construct();
        $this->url = plugin_dir_url(dirname(__FILE__));
        
        jetengine_smartslider_generator()->log('JetEngine_SmartSlider_Generator_Group constructed');
    }
    
    /**
     * Returns label for the generator group
     * 
     * @return string Generator group label
     */
    public function getLabel() {
        return 'JetEngine';
    }
    
    /**
     * Returns group description
     * 
     * @return string Group description
     */
    public function getDescription() {
        return n2_('Creates slides from JetEngine custom post types, meta fields, taxonomies, and relations.');
    }
    
    /**
     * Returns icon for the generator
     * 
     * @return string Icon URL
     */
    public function getIcon() {
        return jetengine_smartslider_generator()->plugin_url . 'assets/images/jetengine-icon.svg';
    }
    
    /**
     * Load generator sources
     */
    protected function loadSources() {
        jetengine_smartslider_generator()->log('Loading JetEngine generator sources');
        
        $this->loadJetEnginePostTypes();
        $this->loadJetEngineCustomContentTypes();
        $this->loadJetEngineRelations();
        $this->loadJetEngineMetaBoxes();
    }
    
    /**
     * Load JetEngine post types as generator sources
     */
    private function loadJetEnginePostTypes() {
        // Get all JetEngine post types
        $jet_post_types = $this->getJetEnginePostTypes();
        
        // Get built-in post types as well
        $builtin_post_types = get_post_types(['_builtin' => true, 'public' => true], 'objects');
        
        // Merge the post types
        $post_types = array_merge($jet_post_types, $builtin_post_types);
        
        // Skip some built-in post types
        $skip_types = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'];
        
        foreach ($post_types as $post_type) {
            // Skip certain post types
            if (in_array($post_type->name, $skip_types)) {
                continue;
            }
            
            // Create generator source for post type
            if (isset($post_type->name) && isset($post_type->labels->singular_name)) {
                // Add to sources
                require_once jetengine_smartslider_generator()->plugin_path . 'includes/sources/post-type.php';
                $this->sources[$post_type->name] = new JetEngine_SmartSlider_Source_PostType(
                    $this, 
                    $post_type->name, 
                    $post_type->labels->singular_name, 
                    $post_type
                );
                
                jetengine_smartslider_generator()->log('Added generator source for post type: ' . $post_type->name);
            }
        }
    }
    
    /**
     * Load JetEngine Custom Content Types as generator sources
     */
    private function loadJetEngineCustomContentTypes() {
        // Check if JetEngine has CCT module
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || !jet_engine()->modules->is_module_active('custom-content-types')) {
            return;
        }
        
        // Get CCT module instance
        $cct_module = jet_engine()->modules->get_module('custom-content-types');
        
        if (!$cct_module || !method_exists($cct_module, 'get_content_types')) {
            return;
        }
        
        // Get all CCT types
        $content_types = $cct_module->get_content_types();
        
        if (empty($content_types)) {
            return;
        }
        
        foreach ($content_types as $content_type) {
            // Skip if no slug or name
            if (!isset($content_type->slug) || !isset($content_type->labels['name'])) {
                continue;
            }
            
            // Add to sources
            require_once jetengine_smartslider_generator()->plugin_path . 'includes/sources/content-type.php';
            $this->sources['cct_' . $content_type->slug] = new JetEngine_SmartSlider_Source_ContentType(
                $this,
                'cct_' . $content_type->slug,
                $content_type->labels['name'],
                $content_type
            );
            
            jetengine_smartslider_generator()->log('Added generator source for CCT: ' . $content_type->slug);
        }
    }
    
    /**
     * Load JetEngine Relations as generator sources
     */
    private function loadJetEngineRelations() {
        // Check if JetEngine has Relations module
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'relations')) {
            return;
        }
        
        // Get Relations module instance
        $relations_manager = jet_engine()->relations;
        
        if (!$relations_manager || !method_exists($relations_manager, 'get_active_relations')) {
            return;
        }
        
        // Get all active relations
        $relations = $relations_manager->get_active_relations();
        
        if (empty($relations)) {
            return;
        }
        
        foreach ($relations as $relation) {
            // Skip if no ID or name
            if (!isset($relation['id']) || !isset($relation['name'])) {
                continue;
            }
            
            // Add to sources
            require_once jetengine_smartslider_generator()->plugin_path . 'includes/sources/relation.php';
            $this->sources['relation_' . $relation['id']] = new JetEngine_SmartSlider_Source_Relation(
                $this,
                'relation_' . $relation['id'],
                $relation['name'],
                $relation
            );
            
            jetengine_smartslider_generator()->log('Added generator source for relation: ' . $relation['name']);
        }
    }
    
    /**
     * Load JetEngine Meta Boxes as generator sources
     */
    private function loadJetEngineMetaBoxes() {
        // The Meta Boxes will be handled by the respective post types
        // This is just a placeholder in case you want to add separate generators for Meta Boxes
    }
    
    /**
     * Get all JetEngine post types
     * 
     * @return array Array of post type objects
     */
    private function getJetEnginePostTypes() {
        $post_types = [];
        
        // Check if JetEngine exists and has the post_type module
        if (function_exists('jet_engine') && jet_engine()->cpt) {
            // Get all registered post types
            if (method_exists(jet_engine()->cpt, 'get_items')) {
                $jet_post_types = jet_engine()->cpt->get_items();
                
                if (!empty($jet_post_types)) {
                    foreach ($jet_post_types as $post_type) {
                        $slug = isset($post_type['slug']) ? $post_type['slug'] : false;
                        $name = isset($post_type['labels']['name']) ? $post_type['labels']['name'] : false;
                        $singular = isset($post_type['labels']['singular_name']) ? $post_type['labels']['singular_name'] : $name;
                        
                        if ($slug && $name) {
                            // Create object similar to WP's post type object
                            $post_type_obj = new stdClass();
                            $post_type_obj->name = $slug;
                            $post_type_obj->labels = new stdClass();
                            $post_type_obj->labels->name = $name;
                            $post_type_obj->labels->singular_name = $singular;
                            
                            $post_types[$slug] = $post_type_obj;
                        }
                    }
                }
            }
        }
        
        // Also get registered post types using WordPress function
        $registered_post_types = get_post_types(['_builtin' => false, 'public' => true], 'objects');
        
        // Merge with JetEngine post types
        foreach ($registered_post_types as $post_type) {
            if (!isset($post_types[$post_type->name])) {
                $post_types[$post_type->name] = $post_type;
            }
        }
        
        return $post_types;
    }
}
