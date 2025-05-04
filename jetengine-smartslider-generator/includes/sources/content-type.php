<?php
/**
 * JetEngine Content Type Source
 * 
 * This class handles the Custom Content Type generator source
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

use Nextend\SmartSlider3\Generator\AbstractGenerator;
use Nextend\Framework\Form\Container\ContainerTable;
use Nextend\Framework\Form\Element\OnOff;
use Nextend\Framework\Form\Element\Select;
use Nextend\Framework\Form\Element\Text;
use Nextend\Framework\Form\Element\Textarea;
use Nextend\Framework\Form\Element\Mixed;
use Nextend\Framework\Form\Element\Group;

class JetEngine_SmartSlider_Source_ContentType extends AbstractGenerator {
    
    protected $layout = 'article';
    private $content_type_slug;
    private $content_type;
    
    /**
     * Constructor
     */
    public function __construct($group, $name, $label, $content_type) {
        $this->content_type_slug = str_replace('cct_', '', $name);
        $this->content_type = $content_type;
        
        parent::__construct($group, $name, $label);
        
        jetengine_smartslider_generator()->log('Content Type Source constructed for: ' . $name);
    }
    
    /**
     * Returns the description of the generator
     */
    public function getDescription() {
        return sprintf(n2_('Creates slides from "%s" JetEngine Custom Content Type items.'), $this->content_type->labels['name']);
    }
    
    /**
     * Renders the generator's admin form fields
     * 
     * @param ContainerTable $container
     */
    public function renderFields($container) {
        // Filter group
        $filterGroup = new ContainerTable($container, 'filter-group', n2_('Filter'));
        
        // Item selection
        $itemSelection = $filterGroup->createRow('item-selection');
        
        // Items by ID
        new Text($itemSelection, 'items_custom', n2_('Items by ID'), '', [
            'tipLabel'       => n2_('Items by ID'),
            'tipDescription' => sprintf(n2_('List the IDs of the %s separated by commas.'), $this->content_type->labels['name']),
            'tipLink'        => 'https://smartslider.helpscoutdocs.com/'
        ]);
        
        // Status filter
        new Select($itemSelection, 'status', n2_('Status'), 'publish', [
            'options' => [
                'publish' => n2_('Published'),
                'draft'   => n2_('Draft'),
                'any'     => n2_('Any')
            ]
        ]);
        
        // Meta field filters
        $this->renderMetaFieldsFilter($filterGroup);
        
        // Order Group
        $orderGroup = new ContainerTable($container, 'order-group', n2_('Order'));
        $orderRow = $orderGroup->createRow('order-row');
        
        // Order by
        new Select($orderRow, 'orderby', n2_('Order by'), '_ID', [
            'options' => [
                '_ID'      => n2_('ID'),
                'date'     => n2_('Date'),
                'modified' => n2_('Last modified date'),
                'rand'     => n2_('Random'),
                'meta'     => n2_('Meta value'),
                'meta_num' => n2_('Meta value (numeric)')
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
     * Render Meta Fields filter options
     */
    private function renderMetaFieldsFilter($container) {
        $metaRow = $container->createRow('meta-filter');
        
        // Meta field name
        new Text($metaRow, 'meta_name', n2_('Meta field name'), '', [
            'tipLabel'       => n2_('Meta field name'),
            'tipDescription' => n2_('JetEngine field name to filter by.')
        ]);
        
        // Meta field value
        new Text($metaRow, 'meta_value', n2_('Meta field value'), '', [
            'tipLabel'       => n2_('Meta field value'),
            'tipDescription' => n2_('Value to match in the meta field.')
        ]);
        
        // Meta comparison operator
        new Select($metaRow, 'meta_compare', n2_('Meta compare'), '=', [
            'options' => [
                '='           => 'Equal (=)',
                '!='          => 'Not equal (!=)',
                '>'           => 'Greater than (>)',
                '>='          => 'Greater than or equal (>=)',
                '<'           => 'Less than (<)',
                '<='          => 'Less than or equal (<=)',
                'LIKE'        => 'Contains (LIKE)',
                'NOT LIKE'    => 'Does not contain (NOT LIKE)',
                'IN'          => 'In list (IN)',
                'NOT IN'      => 'Not in list (NOT IN)',
                'BETWEEN'     => 'Between (BETWEEN)',
                'NOT BETWEEN' => 'Not between (NOT BETWEEN)',
                'EXISTS'      => 'Exists (EXISTS)',
                'NOT EXISTS'  => 'Does not exist (NOT EXISTS)'
            ],
            'tipLabel'       => n2_('Meta compare'),
            'tipDescription' => n2_('How to compare the meta value.')
        ]);
    }
    
    /**
     * Render image source options
     */
    private function renderImageOptions($container) {
        $imageGroup = new ContainerTable($container, 'image-options', n2_('Image options'));
        $imageRow = $imageGroup->createRow('image-row');
        
        // Image source
        new Select($imageRow, 'image_source', n2_('Image source'), 'meta', [
            'options' => [
                'meta'       => n2_('Meta field'),
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
        jetengine_smartslider_generator()->log('Getting data for CCT: ' . $this->content_type_slug);
        
        $data = [];
        
        // Check if JetEngine CCT module is active
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || !jet_engine()->modules->is_module_active('custom-content-types')) {
            return $data;
        }
        
        // Get CCT module instance
        $cct_module = jet_engine()->modules->get_module('custom-content-types');
        
        if (!$cct_module || !method_exists($cct_module, 'get_content_types_for_js')) {
            return $data;
        }
        
        // Get the CCT instance
        $cct_instance = false;
        $content_types = $cct_module->get_content_types_for_js();
        foreach ($content_types as $cct) {
            if (isset($cct['slug']) && $cct['slug'] === $this->content_type_slug) {
                $cct_instance = $cct;
                break;
            }
        }
        
        if (!$cct_instance) {
            return $data;
        }
        
        // Build query args
        $args = [
            'limit'  => $count,
            'offset' => $startIndex,
            'status' => $this->data->get('status', 'publish'),
        ];
        
        // Order settings
        $args['orderby'] = $this->data->get('orderby', '_ID');
        $args['order'] = $this->data->get('order', 'DESC');
        
        // Meta ordering
        if (in_array($args['orderby'], ['meta', 'meta_num'])) {
            $meta_key = $this->data->get('meta_key', '');
            if (!empty($meta_key)) {
                $args['meta_key'] = $meta_key;
                $args['orderby'] = ($args['orderby'] === 'meta_num') ? 'meta_num' : 'meta';
            } else {
                // Default to ID if no meta key specified
                $args['orderby'] = '_ID';
            }
        }
        
        // Custom item selection
        $custom_items = $this->data->get('items_custom', '');
        if (!empty($custom_items)) {
            $ids = array_map('intval', explode(',', $custom_items));
            $args['_id__in'] = $ids;
        }
        
        // Add meta filters
        $meta_name = $this->data->get('meta_name', '');
        if (!empty($meta_name)) {
            $args['meta_query'] = [
                [
                    'key'     => $meta_name,
                    'value'   => $this->data->get('meta_value', ''),
                    'compare' => $this->data->get('meta_compare', '=')
                ]
            ];
        }
        
        // Get CCT items
        $cct_items = [];
        
        // Get the CCT manager class
        $cct_manager = $cct_module->instance->manager;
        
        // Get items from the CCT
        if (method_exists($cct_manager, 'get_items')) {
            $cct_items = $cct_manager->get_items($this->content_type_slug, $args);
        }
        
        // Process items
        if (!empty($cct_items)) {
            foreach ($cct_items as $item) {
                $record = $this->getItemData($item);
                
                // Add to result data
                $data[] = $record;
            }
        }
        
        return $data;
    }
    
    /**
     * Get item data for a single CCT item
     * 
     * @param object $item CCT item
     * @return array Item data
     */
    private function getItemData($item) {
        $record = [];
        
        // Basic item data
        $record['id'] = $item['_ID'];
        $record['cct_type'] = $this->content_type_slug;
        $record['cct_status'] = isset($item['cct_status']) ? $item['cct_status'] : 'publish';
        $record['date'] = isset($item['cct_created']) ? $item['cct_created'] : '';
        $record['modified'] = isset($item['cct_modified']) ? $item['cct_modified'] : '';
        
        // Add all meta fields
        foreach ($item as $key => $value) {
            // Skip internal fields
            if (substr($key, 0, 1) === '_' || in_array($key, ['cct_status', 'cct_created', 'cct_modified'])) {
                continue;
            }
            
            // Add the field to the record
            $record[$key] = $value;
            
            // Process JetEngine fields if possible
            if ($this->content_type && isset($this->content_type->meta_fields)) {
                foreach ($this->content_type->meta_fields as $field) {
                    if (isset($field['name']) && $field['name'] === $key && isset($field['type'])) {
                        $processed = JetEngine_SmartSlider_Helper::process_jet_field_value($value, $field['type'], $key);
                        
                        // Add formatted value and any additional data
                        $record[$key . '_formatted'] = $processed['formatted'];
                        
                        foreach ($processed['additional'] as $additional_key => $additional_value) {
                            $record[$key . '_' . $additional_key] = $additional_value;
                        }
                        
                        break;
                    }
                }
            }
        }
        
        // Add image data
        $this->addImageData($record, $item);
        
        return $record;
    }
    
    /**
     * Add image data to record
     * 
     * @param array $record Record array (by reference)
     * @param array $item CCT item
     */
    private function addImageData(&$record, $item) {
        $image_source = $this->data->get('image_source', 'meta');
        
        // Default image values
        $record['image'] = '';
        $record['thumbnail'] = '';
        $record['image_alt'] = '';
        $record['image_title'] = '';
        
        switch ($image_source) {
            case 'meta':
                // Get image from meta field
                $meta_field = $this->data->get('image_meta', '');
                if (!empty($meta_field) && isset($item[$meta_field])) {
                    $meta_value = $item[$meta_field];
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
                if (!empty($gallery_field) && isset($item[$gallery_field])) {
                    $gallery_images = $item[$gallery_field];
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
}
