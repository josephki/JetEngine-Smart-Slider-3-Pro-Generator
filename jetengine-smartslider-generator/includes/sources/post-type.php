<?php
/**
 * JetEngine Post Type Source
 * 
 * This class handles the post type generator source
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

class JetEngine_SmartSlider_Source_PostType extends AbstractGenerator {
    
    protected $layout = 'article';
    private $post_type;
    private $post_type_obj;
    
    /**
     * Constructor
     */
    public function __construct($group, $name, $label, $post_type_obj) {
        $this->post_type = $name;
        $this->post_type_obj = $post_type_obj;
        
        parent::__construct($group, $name, $label);
        
        jetengine_smartslider_generator()->log('Post Type Source constructed for: ' . $name);
    }
    
    /**
     * Returns the description of the generator
     */
    public function getDescription() {
        return sprintf(n2_('Creates slides from "%s" items.'), $this->post_type_obj->labels->name);
    }
    
    /**
     * Renders the generator's admin form fields
     * 
     * @param ContainerTable $container
     */
    public function renderFields($container) {
        // Filter group - post selection, taxonomies, etc.
        $filterGroup = new ContainerTable($container, 'filter-group', n2_('Filter'));
        
        // Post selection
        $postSelection = $filterGroup->createRow('post-selection');
        
        // Post included option
        new Select($postSelection, 'postsincluded', n2_('Posts included'), 'all', [
            'options' => [
                'all'    => n2_('All posts'),
                'sticky' => n2_('Only sticky posts'),
                'custom' => n2_('Posts by ID')
            ]
        ]);
        
        // Posts by ID
        new Text($postSelection, 'posts_custom', n2_('Posts by ID'), '', [
            'tipLabel'       => n2_('Posts by ID'),
            'tipDescription' => sprintf(n2_('List the IDs of the %s separated by commas.'), $this->post_type_obj->labels->name),
            'tipLink'        => 'https://smartslider.helpscoutdocs.com/'
        ]);
        
        // Post Status
        new Select($postSelection, 'post_status', n2_('Post status'), 'publish', [
            'options' => [
                'publish'    => n2_('Published'),
                'draft'      => n2_('Draft'),
                'pending'    => n2_('Pending'),
                'future'     => n2_('Scheduled'),
                'private'    => n2_('Private'),
                'any'        => n2_('Any')
            ]
        ]);
        
        // Taxonomy filters
        $taxonomies = get_object_taxonomies($this->post_type, 'objects');
        
        if (!empty($taxonomies)) {
            $taxonomyRow = $filterGroup->createRow('taxonomies-row');
            
            // Multi-select for taxonomies
            $tax_options = [];
            foreach ($taxonomies as $taxonomy) {
                if (!isset($taxonomy->name) || !isset($taxonomy->labels->name)) {
                    continue;
                }
                $tax_options[$taxonomy->name] = $taxonomy->labels->name;
            }
            
            new Select($taxonomyRow, 'taxonomies', n2_('Taxonomies'), '', [
                'options'     => $tax_options,
                'isMultiple'  => true,
                'tipLabel'    => n2_('Taxonomies'),
                'tipDescription' => n2_('Filter by taxonomies, like categories or tags.')
            ]);
            
            // Taxonomy relation (AND/OR)
            new Select($taxonomyRow, 'taxonomies_relation', n2_('Relation'), 'AND', [
                'options' => [
                    'AND' => 'AND - All conditions must be met',
                    'OR'  => 'OR - Any condition can be met'
                ]
            ]);
            
            // Create term select fields for each taxonomy
            foreach ($taxonomies as $taxonomy) {
                $terms = get_terms([
                    'taxonomy'   => $taxonomy->name,
                    'hide_empty' => false
                ]);
                
                if (empty($terms) || is_wp_error($terms)) {
                    continue;
                }
                
                $term_options = [];
                
                foreach ($terms as $term) {
                    $term_options[$term->term_id] = $term->name;
                }
                
                $taxTermRow = $filterGroup->createRow('taxonomy-' . $taxonomy->name . '-terms');
                
                new Select($taxTermRow, 'taxonomy_' . $taxonomy->name, $taxonomy->labels->name, '', [
                    'options'    => $term_options,
                    'isMultiple' => true
                ]);
            }
        }
        
        // Meta field filters
        $this->renderMetaFieldsFilter($filterGroup);
        
        // Password protected posts
        $passwordRow = $filterGroup->createRow('password-protected');
        
        new OnOff($passwordRow, 'include_password_protected', n2_('Include password protected posts'), 0);
        
        // Order Group
        $orderGroup = new ContainerTable($container, 'order-group', n2_('Order'));
        $orderRow = $orderGroup->createRow('order-row');
        
        // Order by
        new Select($orderRow, 'orderby', n2_('Order by'), 'date', [
            'options' => [
                'none'          => n2_('None'),
                'ID'            => n2_('Post ID'),
                'author'        => n2_('Author'),
                'title'         => n2_('Title'),
                'name'          => n2_('Post name (slug)'),
                'date'          => n2_('Date'),
                'modified'      => n2_('Last modified date'),
                'parent'        => n2_('Parent ID'),
                'rand'          => n2_('Random'),
                'comment_count' => n2_('Comment count'),
                'menu_order'    => n2_('Menu order'),
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
     * Render Meta Fields filter options
     */
    private function renderMetaFieldsFilter($container) {
        $metaRow = $container->createRow('meta-filter');
        
        // Meta field name
        new Text($metaRow, 'meta_name', n2_('Meta field name'), '', [
            'tipLabel'       => n2_('Meta field name'),
            'tipDescription' => n2_('JetEngine or custom meta field name to filter by.')
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
        jetengine_smartslider_generator()->log('Getting data for post type: ' . $this->post_type);
        
        $data = [];
        
        // Build query args
        $args = [
            'post_type'        => $this->post_type,
            'posts_per_page'   => $count,
            'offset'           => $startIndex,
            'post_status'      => $this->data->get('post_status', 'publish'),
            'orderby'          => $this->data->get('orderby', 'date'),
            'order'            => $this->data->get('order', 'DESC'),
            'suppress_filters' => false
        ];
        
        // Check for meta ordering
        if (in_array($args['orderby'], ['meta_value', 'meta_value_num'])) {
            $meta_key = $this->data->get('meta_key', '');
            if (!empty($meta_key)) {
                $args['meta_key'] = $meta_key;
            } else {
                // Default to date if no meta key specified
                $args['orderby'] = 'date';
            }
        }
        
        // Check for custom post selection
        $postsIncluded = $this->data->get('postsincluded', 'all');
        if ($postsIncluded == 'sticky') {
            // Only sticky posts
            $sticky = get_option('sticky_posts');
            if (!empty($sticky)) {
                $args['post__in'] = $sticky;
            } else {
                // No sticky posts, return empty
                return [];
            }
        } elseif ($postsIncluded == 'custom') {
            // Specific posts by ID
            $custom_posts = $this->data->get('posts_custom', '');
            if (!empty($custom_posts)) {
                $ids = array_map('intval', explode(',', $custom_posts));
                $args['post__in'] = $ids;
            }
        }
        
        // Password protected posts
        if (!$this->data->get('include_password_protected', 0)) {
            $args['has_password'] = false;
        }
        
        // Add taxonomy filters
        $selected_taxonomies = $this->data->get('taxonomies', '');
        if (!empty($selected_taxonomies)) {
            $tax_query = [];
            $selected_taxonomies = explode(',', $selected_taxonomies);
            
            foreach ($selected_taxonomies as $taxonomy) {
                $terms = $this->data->get('taxonomy_' . $taxonomy, '');
                if (!empty($terms)) {
                    $tax_query[] = [
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => explode(',', $terms)
                    ];
                }
            }
            
            if (count($tax_query) > 1) {
                $tax_query['relation'] = $this->data->get('taxonomies_relation', 'AND');
            }
            
            if (!empty($tax_query)) {
                $args['tax_query'] = $tax_query;
            }
        }
        
        // Add meta filters
        $meta_name = $this->data->get('meta_name', '');
        if (!empty($meta_name)) {
            $meta_query = [
                [
                    'key'     => $meta_name,
                    'value'   => $this->data->get('meta_value', ''),
                    'compare' => $this->data->get('meta_compare', '=')
                ]
            ];
            $args['meta_query'] = $meta_query;
        }
        
        // Run the query
        $posts = get_posts($args);
        jetengine_smartslider_generator()->log(count($posts) . ' posts found for: ' . $this->post_type);
        
        // Process posts
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $record = $this->getPostData($post);
                
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
        $record['excerpt'] = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : $this->generateExcerpt($post->post_content);
        
        // Comments
        $record['comment_count'] = $post->comment_count;
        $record['comment_status'] = $post->comment_status;
        
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
                $first_img = $this->getFirstImageFromContent($post->post_content);
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
                $content_images = $this->getAllImagesFromContent($post->post_content);
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
        $taxonomies = get_object_taxonomies($this->post_type);
        
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
        
        // Check if JetEngine Meta Boxes module is active
        if (jet_engine()->meta_boxes) {
            // Get all meta boxes for this post type
            $meta_boxes = jet_engine()->meta_boxes->get_meta_boxes();
            
            if (!empty($meta_boxes)) {
                foreach ($meta_boxes as $meta_box) {
                    // Skip if not for this post type
                    if (!isset($meta_box['args']['object_type']) || $meta_box['args']['object_type'] !== 'post') {
                        continue;
                    }
                    
                    // Skip if not for this specific post type
                    if (!isset($meta_box['args']['allowed_post_type']) || !in_array($this->post_type, $meta_box['args']['allowed_post_type'])) {
                        continue;
                    }
                    
                    // Process meta fields
                    if (isset($meta_box['meta_fields']) && !empty($meta_box['meta_fields'])) {
                        foreach ($meta_box['meta_fields'] as $field) {
                            // Skip if no name
                            if (!isset($field['name'])) {
                                continue;
                            }
                            
                            $field_name = $field['name'];
                            $field_type = isset($field['type']) ? $field['type'] : 'text';
                            
                            // Skip if already processed
                            if (isset($record['meta_' . $field_name])) {
                                continue;
                            }
                            
                            // Get field value
                            $value = get_post_meta($post->ID, $field_name, true);
                            
                            // Process value based on field type
                            switch ($field_type) {
                                case 'date':
                                case 'datetime-local':
                                    if (!empty($value)) {
                                        $timestamp = strtotime($value);
                                        $record['meta_' . $field_name] = $value;
                                        $record['meta_' . $field_name . '_formatted'] = date_i18n(get_option('date_format'), $timestamp);
                                        $record['meta_' . $field_name . '_timestamp'] = $timestamp;
                                    }
                                    break;
                                    
                                case 'gallery':
                                    if (!empty($value) && is_array($value)) {
                                        $gallery_urls = [];
                                        foreach ($value as $image_id) {
                                            if (is_numeric($image_id) && wp_attachment_is_image($image_id)) {
                                                $gallery_urls[] = wp_get_attachment_image_url($image_id, 'full');
                                            }
                                        }
                                        $record['meta_' . $field_name] = json_encode($value);
                                        $record['meta_' . $field_name . '_urls'] = implode(',', $gallery_urls);
                                    }
                                    break;
                                    
                                case 'media':
                                    if (!empty($value) && is_numeric($value) && wp_attachment_is_image($value)) {
                                        $record['meta_' . $field_name] = $value;
                                        $record['meta_' . $field_name . '_url'] = wp_get_attachment_image_url($value, 'full');
                                    }
                                    break;
                                    
                                case 'repeater':
                                    if (!empty($value) && is_array($value)) {
                                        $record['meta_' . $field_name] = json_encode($value);
                                    }
                                    break;
                                    
                                default:
                                    $record['meta_' . $field_name] = $value;
                                    break;
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Generate excerpt from content
     * 
     * @param string $content Post content
     * @param int $length Length of excerpt
     * @return string Generated excerpt
     */
    private function generateExcerpt($content, $length = 55) {
        // Strip shortcodes and HTML
        $excerpt = strip_shortcodes($content);
        $excerpt = wp_strip_all_tags($excerpt);
        
        // Trim to length
        $words = explode(' ', $excerpt, $length + 1);
        if (count($words) > $length) {
            array_pop($words);
            $excerpt = implode(' ', $words) . '...';
        } else {
            $excerpt = implode(' ', $words);
        }
        
        return $excerpt;
    }
    
    /**
     * Get first image from content
     * 
     * @param string $content Post content
     * @return string|null First image URL or null
     */
    private function getFirstImageFromContent($content) {
        preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
        
        if (isset($matches[1])) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get all images from content
     * 
     * @param string $content Post content
     * @return array Array of image URLs
     */
    private function getAllImagesFromContent($content) {
        $images = [];
        preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
        
        if (isset($matches[1]) && !empty($matches[1])) {
            $images = $matches[1];
        }
        
        return $images;
    }
}
