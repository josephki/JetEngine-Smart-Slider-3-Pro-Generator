<?php
/**
 * Helper class for the JetEngine SmartSlider integration
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

class JetEngine_SmartSlider_Helper {
    
    /**
     * Get JetEngine Post Types
     * 
     * @return array Array of JetEngine post types
     */
    public static function get_jet_post_types() {
        $post_types = [];
        
        // Check if JetEngine exists and has the post_type module
        if (!function_exists('jet_engine') || !jet_engine()->cpt) {
            return $post_types;
        }
        
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
        
        return $post_types;
    }
    
    /**
     * Get JetEngine meta fields for post type
     * 
     * @param string $post_type Post type name
     * @return array Array of meta fields
     */
    public static function get_jet_meta_fields($post_type) {
        $meta_fields = [];
        
        // Check if JetEngine exists and has the meta_boxes module
        if (!function_exists('jet_engine') || !jet_engine()->meta_boxes) {
            return $meta_fields;
        }
        
        // Get all meta boxes
        $meta_boxes = jet_engine()->meta_boxes->get_meta_boxes();
        
        if (empty($meta_boxes)) {
            return $meta_fields;
        }
        
        foreach ($meta_boxes as $meta_box) {
            // Check if meta box is for posts
            if (!isset($meta_box['args']['object_type']) || $meta_box['args']['object_type'] !== 'post') {
                continue;
            }
            
            // Check if meta box is for this post type
            if (!isset($meta_box['args']['allowed_post_type']) || !in_array($post_type, $meta_box['args']['allowed_post_type'])) {
                continue;
            }
            
            // Get meta fields
            if (isset($meta_box['meta_fields']) && !empty($meta_box['meta_fields'])) {
                foreach ($meta_box['meta_fields'] as $field) {
                    // Skip if no name
                    if (!isset($field['name'])) {
                        continue;
                    }
                    
                    $field_name = $field['name'];
                    $field_type = isset($field['type']) ? $field['type'] : 'text';
                    $field_title = isset($field['title']) ? $field['title'] : $field_name;
                    
                    $meta_fields[$field_name] = [
                        'name'  => $field_name,
                        'type'  => $field_type,
                        'title' => $field_title
                    ];
                }
            }
        }
        
        return $meta_fields;
    }
    
    /**
     * Get JetEngine taxonomies
     * 
     * @return array Array of JetEngine taxonomies
     */
    public static function get_jet_taxonomies() {
        $taxonomies = [];
        
        // Check if JetEngine exists and has the taxonomies module
        if (!function_exists('jet_engine') || !jet_engine()->taxonomies) {
            return $taxonomies;
        }
        
        // Get all registered taxonomies
        if (method_exists(jet_engine()->taxonomies, 'get_items')) {
            $jet_taxonomies = jet_engine()->taxonomies->get_items();
            
            if (!empty($jet_taxonomies)) {
                foreach ($jet_taxonomies as $taxonomy) {
                    $slug = isset($taxonomy['slug']) ? $taxonomy['slug'] : false;
                    $name = isset($taxonomy['labels']['name']) ? $taxonomy['labels']['name'] : false;
                    $singular = isset($taxonomy['labels']['singular_name']) ? $taxonomy['labels']['singular_name'] : $name;
                    
                    if ($slug && $name) {
                        // Create object similar to WP's taxonomy object
                        $taxonomy_obj = new stdClass();
                        $taxonomy_obj->name = $slug;
                        $taxonomy_obj->labels = new stdClass();
                        $taxonomy_obj->labels->name = $name;
                        $taxonomy_obj->labels->singular_name = $singular;
                        
                        $taxonomies[$slug] = $taxonomy_obj;
                    }
                }
            }
        }
        
        return $taxonomies;
    }
    
    /**
     * Get JetEngine relations
     * 
     * @return array Array of JetEngine relations
     */
    public static function get_jet_relations() {
        $relations = [];
        
        // Check if JetEngine exists and has the relations module
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'relations')) {
            return $relations;
        }
        
        // Get relations module instance
        $relations_manager = jet_engine()->relations;
        
        if (!$relations_manager || !method_exists($relations_manager, 'get_active_relations')) {
            return $relations;
        }
        
        // Get all active relations
        $active_relations = $relations_manager->get_active_relations();
        
        if (empty($active_relations)) {
            return $relations;
        }
        
        foreach ($active_relations as $relation) {
            // Skip if no ID or name
            if (!isset($relation['id']) || !isset($relation['name'])) {
                continue;
            }
            
            $relations[$relation['id']] = $relation;
        }
        
        return $relations;
    }
    
    /**
     * Get JetEngine Custom Content Types
     * 
     * @return array Array of JetEngine CCTs
     */
    public static function get_jet_ccts() {
        $ccts = [];
        
        // Check if JetEngine has CCT module
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || !jet_engine()->modules->is_module_active('custom-content-types')) {
            return $ccts;
        }
        
        // Get CCT module instance
        $cct_module = jet_engine()->modules->get_module('custom-content-types');
        
        if (!$cct_module || !method_exists($cct_module, 'get_content_types')) {
            return $ccts;
        }
        
        // Get all CCT types
        $content_types = $cct_module->get_content_types();
        
        if (empty($content_types)) {
            return $ccts;
        }
        
        foreach ($content_types as $content_type) {
            // Skip if no slug or name
            if (!isset($content_type->slug) || !isset($content_type->labels['name'])) {
                continue;
            }
            
            $ccts[$content_type->slug] = $content_type;
        }
        
        return $ccts;
    }
    
    /**
     * Get image data from attachment ID
     * 
     * @param int $attachment_id Attachment ID
     * @return array Image data
     */
    public static function get_image_data($attachment_id) {
        $image_data = [
            'url'    => '',
            'alt'    => '',
            'title'  => '',
            'width'  => 0,
            'height' => 0
        ];
        
        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            return $image_data;
        }
        
        $image_url = wp_get_attachment_image_url($attachment_id, 'full');
        if (!$image_url) {
            return $image_data;
        }
        
        $image_data['url'] = $image_url;
        $image_data['alt'] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        $attachment = get_post($attachment_id);
        if ($attachment) {
            $image_data['title'] = $attachment->post_title;
        }
        
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!empty($metadata) && isset($metadata['width']) && isset($metadata['height'])) {
            $image_data['width'] = $metadata['width'];
            $image_data['height'] = $metadata['height'];
        }
        
        return $image_data;
    }
    
    /**
     * Process JetEngine field value based on field type
     * 
     * @param mixed $value Field value
     * @param string $field_type Field type
     * @param string $field_name Field name
     * @return array Processed value with additional data
     */
    public static function process_jet_field_value($value, $field_type, $field_name) {
        $processed = [
            'raw'       => $value,
            'formatted' => $value,
            'additional' => []
        ];
        
        if (empty($value)) {
            return $processed;
        }
        
        switch ($field_type) {
            case 'date':
            case 'datetime-local':
                $timestamp = strtotime($value);
                $processed['formatted'] = date_i18n(get_option('date_format'), $timestamp);
                $processed['additional']['timestamp'] = $timestamp;
                break;
                
            case 'time':
                $timestamp = strtotime($value);
                $processed['formatted'] = date_i18n(get_option('time_format'), $timestamp);
                $processed['additional']['timestamp'] = $timestamp;
                break;
                
            case 'gallery':
                if (is_array($value)) {
                    $gallery_urls = [];
                    $gallery_data = [];
                    
                    foreach ($value as $image_id) {
                        if (is_numeric($image_id) && wp_attachment_is_image($image_id)) {
                            $image_data = self::get_image_data($image_id);
                            $gallery_urls[] = $image_data['url'];
                            $gallery_data[] = $image_data;
                        }
                    }
                    
                    $processed['formatted'] = implode(', ', $gallery_urls);
                    $processed['additional']['urls'] = $gallery_urls;
                    $processed['additional']['data'] = $gallery_data;
                }
                break;
                
            case 'media':
                if (is_numeric($value) && wp_attachment_is_image($value)) {
                    $image_data = self::get_image_data($value);
                    $processed['formatted'] = $image_data['url'];
                    $processed['additional'] = $image_data;
                }
                break;
                
            case 'repeater':
                if (is_array($value)) {
                    $processed['formatted'] = json_encode($value);
                    $processed['additional']['count'] = count($value);
                    $processed['additional']['items'] = $value;
                }
                break;
                
            case 'checkbox':
                if (is_array($value)) {
                    $processed['formatted'] = implode(', ', $value);
                    $processed['additional']['items'] = $value;
                    $processed['additional']['count'] = count($value);
                }
                break;
                
            case 'switcher':
                $processed['formatted'] = $value ? __('Yes', 'jetengine-smartslider') : __('No', 'jetengine-smartslider');
                $processed['additional']['bool'] = (bool) $value;
                break;
                
            case 'radio':
            case 'select':
                // No special processing needed for these types
                break;
                
            case 'number':
                $processed['additional']['number'] = floatval($value);
                break;
                
            case 'wysiwyg':
            case 'textarea':
                $processed['additional']['plain'] = wp_strip_all_tags($value);
                $processed['additional']['excerpt'] = self::generate_excerpt($value);
                break;
                
            case 'text':
                $processed['additional']['plain'] = $value;
                break;
                
            case 'colorpicker':
                $processed['additional']['color'] = $value;
                break;
                
            case 'posts':
                if (is_array($value)) {
                    $post_titles = [];
                    foreach ($value as $post_id) {
                        $post_titles[] = get_the_title($post_id);
                    }
                    $processed['formatted'] = implode(', ', $post_titles);
                    $processed['additional']['post_ids'] = $value;
                    $processed['additional']['post_titles'] = $post_titles;
                    $processed['additional']['count'] = count($value);
                } elseif (is_numeric($value)) {
                    $processed['formatted'] = get_the_title($value);
                    $processed['additional']['post_id'] = $value;
                }
                break;
                
            case 'map':
                if (is_array($value)) {
                    $coordinates = [];
                    if (isset($value['lat']) && isset($value['lng'])) {
                        $coordinates[] = $value['lat'] . ',' . $value['lng'];
                    }
                    $processed['formatted'] = implode('; ', $coordinates);
                    $processed['additional'] = $value;
                }
                break;
                
            case 'iconpicker':
                $processed['additional']['icon'] = $value;
                break;
                
            case 'html':
                $processed['additional']['plain'] = wp_strip_all_tags($value);
                break;
                
            // Additional field types for JetEngine 3.0+
            case 'datetime':
                if (!empty($value) && isset($value['date']) && isset($value['time'])) {
                    $date_str = $value['date'] . ' ' . $value['time'];
                    $timestamp = strtotime($date_str);
                    $processed['formatted'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
                    $processed['additional']['timestamp'] = $timestamp;
                    $processed['additional']['date'] = isset($value['date']) ? $value['date'] : '';
                    $processed['additional']['time'] = isset($value['time']) ? $value['time'] : '';
                }
                break;
                
            case 'file':
                if (is_array($value) && !empty($value)) {
                    $file_urls = [];
                    foreach ($value as $file_id) {
                        $file_url = wp_get_attachment_url($file_id);
                        if ($file_url) {
                            $file_urls[] = $file_url;
                        }
                    }
                    $processed['formatted'] = implode(', ', $file_urls);
                    $processed['additional']['urls'] = $file_urls;
                    $processed['additional']['ids'] = $value;
                } elseif (is_numeric($value)) {
                    $file_url = wp_get_attachment_url($value);
                    if ($file_url) {
                        $processed['formatted'] = $file_url;
                        $processed['additional']['url'] = $file_url;
                        $processed['additional']['id'] = $value;
                    }
                }
                break;
                
            case 'relationship':
                if (is_array($value) && !empty($value)) {
                    $item_titles = [];
                    $item_ids = [];
                    
                    foreach ($value as $item) {
                        if (is_array($item) && isset($item['item_id'])) {
                            $item_ids[] = $item['item_id'];
                            $item_titles[] = isset($item['item_label']) ? $item['item_label'] : get_the_title($item['item_id']);
                        } elseif (is_numeric($item)) {
                            $item_ids[] = $item;
                            $item_titles[] = get_the_title($item);
                        }
                    }
                    
                    $processed['formatted'] = implode(', ', $item_titles);
                    $processed['additional']['ids'] = $item_ids;
                    $processed['additional']['titles'] = $item_titles;
                    $processed['additional']['count'] = count($value);
                }
                break;
                
            case 'dynamic_calendar':
                if (is_array($value) && !empty($value)) {
                    $dates = [];
                    $timestamps = [];
                    
                    if (isset($value['dates']) && is_array($value['dates'])) {
                        foreach ($value['dates'] as $date) {
                            $timestamp = strtotime($date);
                            $dates[] = date_i18n(get_option('date_format'), $timestamp);
                            $timestamps[] = $timestamp;
                        }
                    }
                    
                    $processed['formatted'] = implode(', ', $dates);
                    $processed['additional']['dates'] = $dates;
                    $processed['additional']['timestamps'] = $timestamps;
                    $processed['additional']['count'] = count($dates);
                    
                    // Add other calendar data
                    if (isset($value['recurring_rule'])) {
                        $processed['additional']['recurring_rule'] = $value['recurring_rule'];
                    }
                    
                    if (isset($value['is_recurring']) && $value['is_recurring']) {
                        $processed['additional']['is_recurring'] = true;
                    }
                }
                break;
                
            case 'dimension':
                if (is_array($value) && !empty($value)) {
                    $formatted_dimensions = [];
                    
                    foreach ($value as $dim_key => $dim_value) {
                        $unit = isset($value['unit']) ? $value['unit'] : '';
                        if ($dim_key !== 'unit' && !empty($dim_value)) {
                            $formatted_dimensions[] = $dim_value . $unit;
                        }
                    }
                    
                    $processed['formatted'] = implode(' × ', $formatted_dimensions);
                    $processed['additional'] = $value;
                }
                break;
        }
        
        return $processed;
    }
    
    /**
     * Generate excerpt from content
     * 
     * @param string $content Content to excerpt
     * @param int $length Length of excerpt in words
     * @param string $more More text
     * @return string Excerpt
     */
    public static function generate_excerpt($content, $length = 55, $more = '...') {
        // Strip shortcodes and HTML
        $excerpt = strip_shortcodes($content);
        $excerpt = wp_strip_all_tags($excerpt);
        
        // Trim to length
        $words = explode(' ', $excerpt, $length + 1);
        if (count($words) > $length) {
            array_pop($words);
            $excerpt = implode(' ', $words) . $more;
        } else {
            $excerpt = implode(' ', $words);
        }
        
        return $excerpt;
    }
    
    /**
     * Get first image from content
     * 
     * @param string $content Content to search in
     * @return string|null Image URL or null
     */
    public static function get_first_image_from_content($content) {
        preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
        
        if (isset($matches[1])) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get all images from content
     * 
     * @param string $content Content to search in
     * @return array Array of image URLs
     */
    public static function get_all_images_from_content($content) {
        $images = [];
        preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
        
        if (isset($matches[1]) && !empty($matches[1])) {
            $images = $matches[1];
        }
        
        return $images;
    }
    
    /**
     * Format date based on site settings
     * 
     * @param string|int $date Date string or timestamp
     * @param string $format Optional format string
     * @return string Formatted date
     */
    public static function format_date($date, $format = '') {
        if (empty($date)) {
            return '';
        }
        
        if (empty($format)) {
            $format = get_option('date_format');
        }
        
        if (is_numeric($date)) {
            $timestamp = $date;
        } else {
            $timestamp = strtotime($date);
        }
        
        return date_i18n($format, $timestamp);
    }
    
    /**
     * Format time based on site settings
     * 
     * @param string|int $time Time string or timestamp
     * @param string $format Optional format string
     * @return string Formatted time
     */
    public static function format_time($time, $format = '') {
        if (empty($time)) {
            return '';
        }
        
        if (empty($format)) {
            $format = get_option('time_format');
        }
        
        if (is_numeric($time)) {
            $timestamp = $time;
        } else {
            $timestamp = strtotime($time);
        }
        
        return date_i18n($format, $timestamp);
    }
    
    /**
     * Check if a post type is from JetEngine
     * 
     * @param string $post_type Post type name
     * @return bool True if post type is from JetEngine
     */
    public static function is_jet_post_type($post_type) {
        $jet_post_types = self::get_jet_post_types();
        
        return isset($jet_post_types[$post_type]);
    }
    
    /**
     * Check if a taxonomy is from JetEngine
     * 
     * @param string $taxonomy Taxonomy name
     * @return bool True if taxonomy is from JetEngine
     */
    public static function is_jet_taxonomy($taxonomy) {
        $jet_taxonomies = self::get_jet_taxonomies();
        
        return isset($jet_taxonomies[$taxonomy]);
    }
    
    /**
     * Check if a CCT is from JetEngine
     * 
     * @param string $cct CCT name
     * @return bool True if CCT is from JetEngine
     */
    public static function is_jet_cct($cct) {
        $jet_ccts = self::get_jet_ccts();
        
        return isset($jet_ccts[$cct]);
    }
    
    /**
     * Check if meta field is from JetEngine
     * 
     * @param string $field_name Field name
     * @param string $post_type Post type name
     * @return bool True if field is from JetEngine
     */
    public static function is_jet_meta_field($field_name, $post_type) {
        $jet_meta_fields = self::get_jet_meta_fields($post_type);
        
        return isset($jet_meta_fields[$field_name]);
    }
    
    /**
     * Get JetEngine custom queries
     * 
     * @return array Array of custom queries
     */
    public static function get_jet_queries() {
        $queries = [];
        
        // Check if JetEngine has query builder module
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || !jet_engine()->modules->is_module_active('query-builder')) {
            return $queries;
        }
        
        // Get query builder module instance
        $query_builder = jet_engine()->modules->get_module('query-builder');
        
        if (!$query_builder || !isset($query_builder->instance) || !method_exists($query_builder->instance, 'get_queries')) {
            return $queries;
        }
        
        // Get all queries
        $all_queries = $query_builder->instance->get_queries();
        
        if (empty($all_queries)) {
            return $queries;
        }
        
        foreach ($all_queries as $query) {
            if (isset($query['id']) && isset($query['name'])) {
                $queries[$query['id']] = $query;
            }
        }
        
        return $queries;
    }
    
    /**
     * Execute JetEngine custom query
     * 
     * @param int $query_id Query ID
     * @param array $args Additional arguments
     * @return array Query results
     */
    public static function execute_jet_query($query_id, $args = []) {
        $results = [];
        
        // Check if JetEngine has query builder module
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || !jet_engine()->modules->is_module_active('query-builder')) {
            return $results;
        }
        
        // Get query builder module instance
        $query_builder = jet_engine()->modules->get_module('query-builder');
        
        if (!$query_builder || !isset($query_builder->instance) || !method_exists($query_builder->instance, 'get_query_by_id')) {
            return $results;
        }
        
        // Get query by ID
        $query = $query_builder->instance->get_query_by_id($query_id);
        
        if (!$query) {
            return $results;
        }
        
        // Execute query
        $query->setup_query($args);
        
        // Get query results
        $results = $query->get_items();
        
        return $results;
    }
    
    /**
     * Get post formats list
     * 
     * @return array Array of post formats
     */
    public static function get_post_formats() {
        $formats = get_theme_support('post-formats');
        
        if (!$formats) {
            return [];
        }
        
        $formats = $formats[0];
        $formats = array_combine($formats, $formats);
        
        // Add 'standard' format
        $formats = array_merge(['standard' => 'standard'], $formats);
        
        return $formats;
    }
    
    /**
     * Get all registered image sizes
     * 
     * @return array Array of image sizes
     */
    public static function get_image_sizes() {
        $sizes = get_intermediate_image_sizes();
        $result = [];
        
        foreach ($sizes as $size) {
            $result[$size] = $size;
        }
        
        return array_merge(['full' => 'full'], $result);
    }
}
