<?php
/**
 * JetEngine Relation Source
 * 
 * This class handles the Relation generator source
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

use Nextend\SmartSlider3\Generator\AbstractGenerator;
use Nextend\Framework\Form\Container\ContainerTable;
use Nextend\Framework\Form\Element\OnOff;
use Nextend\Framework\Form\Element\Select;
use Nextend\Framework\Form\Element\Text;

class JetEngine_SmartSlider_Source_Relation extends AbstractGenerator {
    
    protected $layout = 'article';
    private $relation_id;
    private $relation;
    
    /**
     * Constructor
     */
    public function __construct($group, $name, $label, $relation) {
        $this->relation_id = str_replace('relation_', '', $name);
        $this->relation = $relation;
        
        parent::__construct($group, $name, $label);
        
        jetengine_smartslider_generator()->log('Relation Source constructed for: ' . $name);
    }
    
    /**
     * Returns the description of the generator
     */
    public function getDescription() {
        return sprintf(n2_('Creates slides from "%s" JetEngine relation items.'), $this->relation['name']);
    }
    
    /**
     * Renders the generator's admin form fields
     * 
     * @param ContainerTable $container
     */
    public function renderFields($container) {
        // Filter group
        $filterGroup = new ContainerTable($container, 'filter-group', n2_('Filter'));
        
        // Parent item
        $parentRow = $filterGroup->createRow('parent-row');
        
        // Parent item selection
        new Select($parentRow, 'parent_object', n2_('Parent object'), 'children', [
            'options' => [
                'children' => n2_('Get children objects'),
                'parent'   => n2_('Get parent objects')
            ],
            'tipLabel'       => n2_('Object type'),
            'tipDescription' => n2_('Select which side of the relation to get objects from.')
        ]);
        
        // Object ID to get related items
        new Text($parentRow, 'parent_id', n2_('Object ID'), '', [
            'tipLabel'       => n2_('Object ID'),
            'tipDescription' => n2_('ID of the parent/child object to get related items for.')
        ]);
        
        // Item status
        new Select($parentRow, 'post_status', n2_('Status'), 'publish', [
            'options' => [
                'publish' => n2_('Published'),
                'draft'   => n2_('Draft'),
                'pending' => n2_('Pending'),
                'future'  => n2_('Scheduled'),
                'private' => n2_('Private'),
                'any'     => n2_('Any')
            ]
        ]);
        
        // Order Group
        $orderGroup = new ContainerTable($container, 'order-group', n2_('Order'));
        $orderRow = $orderGroup->createRow('order-row');
        
        // Order by
        new Select($orderRow, 'orderby', n2_('Order by'), 'ID', [
            'options' => [
                'ID'            => n2_('Post ID'),
                'title'         => n2_('Title'),
                'date'          => n2_('Date'),
                'modified'      => n2_('Last modified date'),
                'rand'          => n2_('Random'),
                'comment_count' => n2_('Comment count'),
                'meta_value'    => n2_('Meta value'),
                'meta_value_num' => n2_('Meta value (numeric)')
            ]
        ]);
        
        // Meta key for ordering
        new Text($orderRow, 'meta_key', n2_('Meta key for ordering'), '', [
            'tipLabel'       => n2_('Meta key for ordering'),
            'tipDescription' => n2_('If ordering by a meta value, enter the meta key here.')
        ]);
        
        // Order direction
        new Select($orderRow, 'order', n2_('Order'), 'DESC', [
            'options' => [
                'DESC' => n2_('Descending'),
                'ASC'  => n2_('Ascending')
            ]
        ]);
        
        // Image source options
        $this->renderImageOptions($container);
    }
    
    /**
     * Render image source options
     */
    private function renderImageOptions($container) {
        $imageGroup = new ContainerTable($container, 'image-options', n2_('Image options'));
        $imageRow = $imageGroup->createRow('image-row');
        
        // Image source
        new Select($imageRow, 'image_source', n2_('Image source'), 'featured', [
            'options' => [
                'featured'    => n2_('Featured image'),
                'first'       => n2_('First image from content'),
                'meta'        => n2_('Meta field'),
                'content'     => n2_('Content images'),
                'jet_gallery' => n2_('JetEngine Gallery field')
            ]
        ]);
        
        // Meta field for image
        new Text($imageRow, 'image_meta', n2_('Image meta field'), '', [
            'tipLabel'       => n2_('Image meta field'),
            'tipDescription' => n2_('Meta field containing image ID, URL or JetEngine Gallery field.')
        ]);
    }
    
    /**
     * Retrieves the data for the generator
     * 
     * @param int $count Number of items to return
     * @param int $startIndex Start index
     * @return array Generated data
     */
    protected function _getData($count, $startIndex) {
        jetengine_smartslider_generator()->log('Getting data for relation: ' . $this->relation_id);
        
        $data = [];
        
        // Check if JetEngine relations module is available
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'relations')) {
            return $data;
        }
        
        // Get relations module instance
        $relations_manager = jet_engine()->relations;
        
        if (!$relations_manager || !method_exists($relations_manager, 'get_relation_instances')) {
            return $data;
        }
        
        // Get relation instance
        $relation_instances = $relations_manager->get_relation_instances();
        
        // Find our target relation
        $relation_instance = null;
        foreach ($relation_instances as $instance) {
            if (method_exists($instance, 'get_id') && $instance->get_id() == $this->relation_id) {
                $relation_instance = $instance;
                break;
            }
        }
        
        if (!$relation_instance) {
            return $data;
        }
        
        // Get parent object ID
        $parent_id = $this->data->get('parent_id', '');
        
        if (empty($parent_id)) {
            return $data;
        }
        
        // Get related objects
        $parent_object = $this->data->get('parent_object', 'children');
        $related_items = [];
        
        // Prepare args for query
        $args = [
            'post_status' => $this->data->get('post_status', 'publish'),
            'posts_per_page' => $count,
            'offset' => $startIndex,
            'orderby' => $this->data->get('orderby', 'ID'),
            'order' => $this->data->get('order', 'DESC')
        ];
        
        // Meta ordering
        if (in_array($args['orderby'], ['meta_value', 'meta_value_num'])) {
            $meta_key = $this->data->get('meta_key', '');
            if (!empty($meta_key)) {
                $args['meta_key'] = $meta_key;
            } else {
                // Default to ID if no meta key specified
                $args['orderby'] = 'ID';
            }
        }
        
        try {
            if ($parent_object === 'children') {
                // Get children for given parent ID
                if (method_exists($relation_instance, 'get_children')) {
                    $related_items = $relation_instance->get_children($parent_id, true, $args);
                }
            } else {
                // Get parents for given child ID
                if (method_exists($relation_instance, 'get_parents')) {
                    $related_items = $relation_instance->get_parents($parent_id, true, $args);
                }
            }
        } catch (Exception $e) {
            jetengine_smartslider_generator()->log('Error getting relation data: ' . $e->getMessage());
            return $data;
        }
        
        // Process items
        if (!empty($related_items) && is_array($related_items)) {
            foreach ($related_items as $item) {
                // Skip if not a post object
                if (!is_a($item, 'WP_Post')) {
                    continue;
                }
                
                $record = $this->getPostData($item);
                
                // Add to result data
                $data[] = $record;
            }
        }
        
        return $data;
    }
    
    /**
     * Get post data for a single post
     * 
     * @param WP_Post $post Post object
     * @return array Post data
     */
    private function getPostData($post) {
        $record = [];
        
        // Basic post data
        $record['id'] = $post->ID;
        $record['title'] = $post->post_title;
        $record['content'] = $post->post_content;
        $record['url'] = get_permalink($post->ID);
        $record['author_name'] = get_the_author_meta('display_name', $post->post_author);
        $record['author_url'] = get_author_posts_url($post->post_author);
        $record['date'] = get_the_date('', $post->ID);
        $record['modified'] = get_the_modified_date('', $post->ID);
        $record['excerpt'] = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : JetEngine_SmartSlider_Helper::generate_excerpt($post->post_content);
        $record['post_type'] = $post->post_type;
        
        // Comments
        $record['comment_count'] = $post->comment_count;
        $record['comment_status'] = $post->comment_status;
        
        // Relation specific data
        $record['relation_id'] = $this->relation_id;
        $record['relation_name'] = $this->relation['name'];
        
        // Featured Image
        $this->addImageData($record, $post);
        
        // Add taxonomies data
        $this->addTaxonomiesData($record, $post);
        
        // Add meta fields data
        $this->addMetaFieldsData($record, $post);
        
        return $record;
    }
    
    /**
     * Add image data to record
     * 
     * @param array $record Record array (by reference)
     * @param WP_Post $post Post object
     */
    private function addImageData(&$record, $post) {
        $image_source = $this->data->get('image_source', 'featured');
        
        // Default image values
        $record['image'] = '';
        $record['thumbnail'] = '';
        $record['image_alt'] = '';
        $record['image_title'] = '';
        
        switch ($image_source) {
            case 'featured':
                // Get featured image
                $image_id = get_post_thumbnail_id($post->ID);
                if ($image_id) {
                    $this->addImageDataFromID($record, $image_id);
                }
                break;
                
            case 'first':
                // Get first image from content
                $first_img = JetEngine_SmartSlider_Helper::get_first_image_from_content($post->post_content);
                if (!empty($first_img)) {
                    $record['image'] = $first_img;
                    $record['thumbnail'] = $first_img;
                }
                break;
                
            case 'meta':
                // Get image from meta field
                $meta_field = $this->data->get('image_meta', '');
                if (!empty($meta_field)) {
                    $meta_value = get_post_meta($post->ID, $meta_field, true);
                    if (!empty($meta_value)) {
                        // Check if it's an image ID
                        if (is_numeric($meta_value) && wp_attachment_is_image($meta_value)) {
                            $this->addImageDataFromID($record, $meta_value);
                        } 
                        // Check if it's a URL
                        elseif (filter_var($meta_value, FILTER_VALIDATE_URL)) {
                            $record['image'] = $meta_value;
                            $record['thumbnail'] = $meta_value;
                        }
                    }
                }
                break;
                
            case 'jet_gallery':
                // Get JetEngine gallery field
                $gallery_field = $this->data->get('image_meta', '');
                if (!empty($gallery_field)) {
                    $gallery_images = get_post_meta($post->ID, $gallery_field, true);
                    if (!empty($gallery_images) && is_array($gallery_images)) {
                        // Use first image from gallery
                        $first_image = reset($gallery_images);
                        if (is_numeric($first_image) && wp_attachment_is_image($first_image)) {
                            $this->addImageDataFromID($record, $first_image);
                        }
                        
                        // Add all gallery images as separate entries
                        $i = 2;
                        foreach ($gallery_images as $image_id) {
                            if ($i === 2) { // Skip first image as it's already added
                                $i++;
                                continue;
                            }
                            
                            if (is_numeric($image_id) && wp_attachment_is_image($image_id)) {
                                $image_url = wp_get_attachment_image_url($image_id, 'full');
                                $record['image_' . $i] = $image_url;
                                $record['thumbnail_' . $i] = $image_url;
                                $record['image_alt_' . $i] = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                                
                                // Get image metadata
                                $attachment = get_post($image_id);
                                if ($attachment) {
                                    $record['image_title_' . $i] = $attachment->post_title;
                                }
                                
                                $i++;
                            }
                        }
                    }
                }
                break;
                
            case 'content':
                // Get all images from content
                $content_images = JetEngine_SmartSlider_Helper::get_all_images_from_content($post->post_content);
                if (!empty($content_images)) {
                    // Use first image for main image
                    $record['image'] = $content_images[0];
                    $record['thumbnail'] = $content_images[0];
                    
                    // Add additional images
                    for ($i = 1; $i < count($content_images); $i++) {
                        $idx = $i + 1;
                        $record['image_' . $idx] = $content_images[$i];
                        $record['thumbnail_' . $idx] = $content_images[$i];
                    }
                }
                break;
        }
    }
    
    /**
     * Add image data from attachment ID
     * 
     * @param array $record Record array (by reference)
     * @param int $image_id Attachment ID
     */
    private function addImageDataFromID(&$record, $image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        $image_data = wp_get_attachment_metadata($image_id);
        
        $record['image'] = $image_url;
        $record['thumbnail'] = $image_url;
        $record['image_alt'] = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        
        // Get image title
        $attachment = get_post($image_id);
        if ($attachment) {
            $record['image_title'] = $attachment->post_title;
        }
        
        // Add image dimensions
        if (!empty($image_data) && isset($image_data['width']) && isset($image_data['height'])) {
            $record['image_width'] = $image_data['width'];
            $record['image_height'] = $image_data['height'];
        }
    }
    
    /**
     * Add taxonomies data to record
     * 
     * @param array $record Record array (by reference)
     * @param WP_Post $post Post object
     */
    private function addTaxonomiesData(&$record, $post) {
        $taxonomies = get_object_taxonomies($post->post_type);
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post->ID, $taxonomy, array('fields' => 'all'));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                // Term names as list
                $term_names = [];
                $term_slugs = [];
                $term_urls = [];
                
                foreach ($terms as $term) {
                    $term_names[] = $term->name;
                    $term_slugs[] = $term->slug;
                    $term_urls[] = get_term_link($term);
                }
                
                $record[$taxonomy] = implode(', ', $term_names);
                $record[$taxonomy . '_slugs'] = implode(', ', $term_slugs);
                $record[$taxonomy . '_urls'] = implode(', ', $term_urls);
                
                // Add first term fields separately
                if (isset($terms[0])) {
                    $record[$taxonomy . '_name'] = $terms[0]->name;
                    $record[$taxonomy . '_slug'] = $terms[0]->slug;
                    $record[$taxonomy . '_url'] = get_term_link($terms[0]);
                }
            }
        }
    }
    
    /**
     * Add meta fields data to record
     * 
     * @param array $record Record array (by reference)
     * @param WP_Post $post Post object
     */
    private function addMetaFieldsData(&$record, $post) {
        $meta_fields = get_post_meta($post->ID);
        
        foreach ($meta_fields as $key => $values) {
            // Skip internal fields
            if (substr($key, 0, 1) === '_') {
                continue;
            }
            
            if (isset($values[0])) {
                // Get field value
                $value = $values[0];
                
                // Check if value is serialized
                if (is_serialized($value)) {
                    $value = maybe_unserialize($value);
                }
                
                // Handle arrays/objects
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                // Add to record
                $record['meta_' . $key] = $value;
                
                // Special handling for image fields
                if (is_numeric($value) && wp_attachment_is_image($value)) {
                    $image_url = wp_get_attachment_image_url($value, 'full');
                    if ($image_url) {
                        $record['meta_image_' . $key] = $image_url;
                    }
                }
            }
        }
        
        // Process JetEngine meta fields if available
        $this->processJetEngineFields($record, $post);
    }
    
    /**
     * Process JetEngine specific fields
     * 
     * @param array $record Record array (by reference)
     * @param WP_Post $post Post object
     */
    private function processJetEngineFields(&$record, $post) {
        if (!function_exists('jet_engine')) {
            return;
        }
        
        // Process JetEngine meta fields similar to PostType source
        $jet_meta_fields = JetEngine_SmartSlider_Helper::get_jet_meta_fields($post->post_type);
        
        if (!empty($jet_meta_fields)) {
            foreach ($jet_meta_fields as $field_name => $field) {
                // Skip if already processed
                if (isset($record['meta_' . $field_name . '_formatted'])) {
                    continue;
                }
                
                // Get field value
                $value = get_post_meta($post->ID, $field_name, true);
                
                if (empty($value)) {
                    continue;
                }
                
                // Process value based on field type
                $processed = JetEngine_SmartSlider_Helper::process_jet_field_value(
                    $value, 
                    $field['type'], 
                    $field_name
                );
                
                // Add formatted value
                $record['meta_' . $field_name . '_formatted'] = $processed['formatted'];
                
                // Add additional data
                foreach ($processed['additional'] as $additional_key => $additional_value) {
                    $record['meta_' . $field_name . '_' . $additional_key] = $additional_value;
                }
            }
        }
    }
}
