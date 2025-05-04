<?php
// Direkten Zugriff verhindern
if (!defined('ABSPATH')) exit;

use Nextend\SmartSlider3\Generator\AbstractGenerator;
use Nextend\SmartSlider3\Generator\AbstractGeneratorGroup;
use Nextend\Framework\Form\Container\ContainerTable;
use Nextend\Framework\Form\Element\OnOff;
use Nextend\Framework\Form\Element\Select;
use Nextend\Framework\Form\Element\Text;

// Debug-Funktion aus der Hauptdatei
if (!function_exists('jetengine_ss3_debug_log')) {
    function jetengine_ss3_debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-info"><p>JetEngine SS3 Debug: ' . esc_html($message) . '</p></div>';
            });
            error_log('JetEngine SS3 Debug: ' . $message);
        }
    }
}

jetengine_ss3_debug_log('Generator-Klassen-Datei geladen');

/**
 * JetEngine Generator-Gruppe - Direkte Integration mit Smart Slider 3 Pro
 */
class JetEngineGeneratorGroup extends AbstractGeneratorGroup {
    
    protected $name = 'jetengine';
    public $title = 'JetEngine CPT';
    
    // Nur Grundinitialisierung im Konstruktor - keine direkte Registrierung
    public function __construct() {
        parent::__construct();
        jetengine_ss3_debug_log('JetEngineGeneratorGroup konstruiert');
    }
    
    // Diese Methode ist notwendig, da sie in der AbstractGeneratorGroup als abstrakt definiert ist
    public function getLabel() {
        return 'JetEngine CPT';
    }
	
	// Bild
	public function getIcon() {
    return plugin_dir_url(__FILE__) . 'jetengine-icon.svg';
}
    
    // Diese Methode ist wichtig für die Anzeige im Dropdown
    public function getTitle() {
        return $this->title;
    }
    
    public function getDescription() {
        return 'JetEngine Custom Post Types für Smart Slider 3';
    }
    
    // Für Smart Slider 3.5 werden diese Methoden benötigt
    public function isInstalled() {
        return true;
    }
    
    public function isActive() {
        return true;
    }
    
    public function install() {
        return true;
    }
    
    protected function loadSources() {
        jetengine_ss3_debug_log('JetEngineGeneratorGroup::loadSources() aufgerufen');
        
        // Benutzerdefinierte Post Types abrufen
        $post_types = get_post_types(array(
            'public'   => true,
            '_builtin' => false
        ), 'objects');
        
        if (empty($post_types)) {
            jetengine_ss3_debug_log('Keine benutzerdefinierten Post Types gefunden');
            return;
        }
        
        // Debug: Gefundene CPTs
        $pt_names = array();
        foreach ($post_types as $post_type) {
            $pt_names[] = $post_type->name;
        }
        jetengine_ss3_debug_log('Gefundene Post Types: ' . implode(', ', $pt_names));
        
        // Für jeden Post Type einen Generator erstellen
        foreach ($post_types as $post_type) {
            $this->sources[$post_type->name] = new JetEngineCPTGenerator($this, $post_type->name, $post_type);
            jetengine_ss3_debug_log('Generator erstellt für: ' . $post_type->name);
        }
    }
}

/**
 * Generator für jeden Custom Post Type
 */
class JetEngineCPTGenerator extends AbstractGenerator {
    
    protected $layout = 'article';
    private $post_type;
    
    public function __construct($group, $name, $post_type) {
        $this->post_type = $post_type;
        parent::__construct($group, $name, $post_type->labels->name);
    }
    
    public function getDescription() {
        return 'Zeigt Einträge vom Typ "' . $this->post_type->labels->name . '" an';
    }
    
    // Eingabefelder für den Generator
    public function renderFields($container) {
        // Gruppe 1: Allgemeine Einstellungen
        $general = new ContainerTable($container, 'general-group', 'Allgemeine Einstellungen');
        
        // Status-Filter
        $statuses = array(
            'publish' => 'Veröffentlicht',
            'draft'   => 'Entwurf',
            'pending' => 'Ausstehend',
            'private' => 'Privat',
            'future'  => 'Geplant'
        );
        
        new Select($general, 'post_status', 'Status', 'publish', array(
            'options' => $statuses
        ));
        
        // Beziehung (UND/ODER) für taxonomies/meta
        new Select($general, 'relation', 'Taxonomie/Meta Verknüpfung', 'AND', array(
            'options' => array(
                'AND' => 'UND - Alle Bedingungen müssen erfüllt sein',
                'OR'  => 'ODER - Mindestens eine Bedingung muss erfüllt sein'
            )
        ));
        
        // Passwortgeschützte Posts anzeigen?
        new OnOff($general, 'include_password_protected', 'Passwortgeschützte Posts einbeziehen', 0);
        
        // Gruppe 2: Taxonomie-Filter
        $taxonomyGroup = new ContainerTable($container, 'taxonomy-group', 'Taxonomien');
        $this->renderTaxonomyFields($taxonomyGroup);
        
        // Gruppe 3: Meta-Filter
        $metaGroup = new ContainerTable($container, 'meta-group', 'Meta-Felder');
        
        // Meta-Feld-Filter
        new Text($metaGroup, 'metakey', 'Meta-Feldname', '');
        new Text($metaGroup, 'metavalue', 'Meta-Feldwert', '');
        
        $compare_options = array(
            '='           => 'Gleich',
            '!='          => 'Ungleich',
            '>'           => 'Größer als',
            '>='          => 'Größer oder gleich',
            '<'           => 'Kleiner als',
            '<='          => 'Kleiner oder gleich',
            'LIKE'        => 'Enthält',
            'NOT LIKE'    => 'Enthält nicht',
            'IN'          => 'In Liste (kommagetrennt)',
            'NOT IN'      => 'Nicht in Liste (kommagetrennt)',
            'BETWEEN'     => 'Zwischen (kommagetrennt)',
            'NOT BETWEEN' => 'Nicht zwischen (kommagetrennt)',
            'EXISTS'      => 'Existiert',
            'NOT EXISTS'  => 'Existiert nicht'
        );
        
        new Select($metaGroup, 'metacompare', 'Vergleichsoperator', '=', array(
            'options' => $compare_options
        ));
        
        // Gruppe 4: Sortierung
        $orderGroup = new ContainerTable($container, 'order-group', 'Sortierung');
        
        $orderby_options = array(
            'none'          => 'Keine Sortierung',
            'ID'            => 'ID',
            'author'        => 'Autor',
            'title'         => 'Titel',
            'name'          => 'Name (Slug)',
            'date'          => 'Datum',
            'modified'      => 'Änderungsdatum',
            'parent'        => 'Eltern-ID',
            'rand'          => 'Zufällig',
            'comment_count' => 'Anzahl Kommentare',
            'menu_order'    => 'Menü-Reihenfolge',
            'meta_value'    => 'Meta-Wert (metakey erforderlich)',
            'meta_value_num' => 'Meta-Wert numerisch (metakey erforderlich)'
        );
        
        new Select($orderGroup, 'orderby', 'Sortieren nach', 'date', array(
            'options' => $orderby_options
        ));
        
        new Text($orderGroup, 'orderbymeta', 'Meta-Feldname für Sortierung', '');
        
        $order_options = array(
            'DESC' => 'Absteigend',
            'ASC'  => 'Aufsteigend'
        );
        
        new Select($orderGroup, 'order', 'Reihenfolge', 'DESC', array(
            'options' => $order_options
        ));
    }
    
    // Taxonomie-Felder rendern
    protected function renderTaxonomyFields($container) {
        $taxonomies = get_object_taxonomies($this->post_type->name, 'objects');
        
        if (empty($taxonomies)) {
            return;
        }
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy'   => $taxonomy->name,
                'hide_empty' => false
            ));
            
            if (empty($terms)) {
                continue;
            }
            
            $options = array(
                0 => '-- Alle --'
            );
            
            foreach ($terms as $term) {
                $options[$term->term_id] = $term->name;
            }
            
            new Select($container, 'tax_' . $taxonomy->name, $taxonomy->labels->name, 0, array(
                'options'    => $options,
                'isMultiple' => true
            ));
            
            // Tax_relation nur hinzufügen, wenn mehr als eine Taxonomy
            if (count($taxonomies) > 1) {
                new Select($container, 'tax_relation', 'Taxonomie-Verknüpfung', 'AND', array(
                    'options' => array(
                        'AND' => 'UND - Alle Bedingungen müssen erfüllt sein',
                        'OR'  => 'ODER - Mindestens eine Bedingung muss erfüllt sein'
                    )
                ));
            }
        }
    }
    
    // Daten für den Generator abrufen
    protected function _getData($count, $startIndex) {
        jetengine_ss3_debug_log('Daten werden abgerufen für ' . $this->post_type->name);
        
        $data = array();
        
        // WP_Query-Argumente
        $args = array(
            'post_type'        => $this->post_type->name,
            'posts_per_page'   => $count,
            'offset'           => $startIndex,
            'post_status'      => $this->data->get('post_status', 'publish'),
            'orderby'          => $this->data->get('orderby', 'date'),
            'order'            => $this->data->get('order', 'DESC'),
            'suppress_filters' => false
        );
        
        // Meta-Feld für Sortierung
        if (in_array($args['orderby'], array('meta_value', 'meta_value_num'))) {
            $orderbymeta = $this->data->get('orderbymeta', '');
            if (!empty($orderbymeta)) {
                $args['meta_key'] = $orderbymeta;
            } else {
                // Wenn kein Meta-Key angegeben, auf Datum zurückfallen
                $args['orderby'] = 'date';
            }
        }
        
        // Passwortgeschützte Posts
        if (!$this->data->get('include_password_protected', 0)) {
            $args['has_password'] = false;
        }
        
        // Taxonomie-Filter
        $tax_query = array();
        $taxonomies = get_object_taxonomies($this->post_type->name);
        
        foreach ($taxonomies as $taxonomy) {
            $terms = $this->data->get('tax_' . $taxonomy, '');
            if (!empty($terms)) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => explode(',', $terms)
                );
            }
        }
        
        if (count($tax_query) > 1) {
            $tax_query['relation'] = $this->data->get('tax_relation', 'AND');
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        // Meta-Filter
        $meta_key = $this->data->get('metakey', '');
        if (!empty($meta_key)) {
            $meta_query = array(
                array(
                    'key'     => $meta_key,
                    'value'   => $this->data->get('metavalue', ''),
                    'compare' => $this->data->get('metacompare', '=')
                )
            );
            $args['meta_query'] = $meta_query;
        }
        
        // Taxonomie/Meta Verknüpfung
        if (!empty($args['tax_query']) && !empty($args['meta_query'])) {
            $args['relation'] = $this->data->get('relation', 'AND');
        }
        
        // Posts abfragen
        $posts = get_posts($args);
        jetengine_ss3_debug_log(count($posts) . ' Posts gefunden für ' . $this->post_type->name);
        
        // Daten für jeden Post aufbereiten
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $record = array();
                
                // Standardfelder
                $record['id'] = $post->ID;
                $record['title'] = $post->post_title;
                $record['url'] = get_permalink($post->ID);
                $record['author_name'] = get_the_author_meta('display_name', $post->post_author);
                $record['author_url'] = get_author_posts_url($post->post_author);
                $record['date'] = get_the_date('', $post->ID);
                $record['modified'] = get_the_modified_date('', $post->ID);
                
                // Inhalt
                $record['content'] = $post->post_content;
                $record['excerpt'] = get_the_excerpt($post->ID);
                
                // Kommentare
                $record['comment_count'] = $post->comment_count;
                $record['comment_status'] = $post->comment_status;
                
                // Beitragsbild
                $image_id = get_post_thumbnail_id($post->ID);
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'full');
                    $image_data = wp_get_attachment_metadata($image_id);
                    
                    $record['image'] = $image_url;
                    $record['thumbnail'] = $image_url;
                    
                    if (!empty($image_data) && isset($image_data['width']) && isset($image_data['height'])) {
                        $record['image_width'] = $image_data['width'];
                        $record['image_height'] = $image_data['height'];
                        $record['image_alt'] = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                    }
                } else {
                    $record['image'] = '';
                    $record['thumbnail'] = '';
                }
                
                // Taxonomien
                $taxonomies = get_object_taxonomies($this->post_type->name);
                foreach ($taxonomies as $taxonomy) {
                    $terms = wp_get_post_terms($post->ID, $taxonomy, array('fields' => 'all'));
                    
                    if (!empty($terms) && !is_wp_error($terms)) {
                        // Term-Namen als Liste
                        $term_names = array();
                        $term_slugs = array();
                        $term_urls = array();
                        
                        foreach ($terms as $term) {
                            $term_names[] = $term->name;
                            $term_slugs[] = $term->slug;
                            $term_urls[] = get_term_link($term);
                        }
                        
                        $record[$taxonomy] = implode(', ', $term_names);
                        $record[$taxonomy . '_slugs'] = implode(', ', $term_slugs);
                        $record[$taxonomy . '_urls'] = implode(', ', $term_urls);
                        
                        // Einzelnen Term speichern (ersten)
                        if (isset($terms[0])) {
                            $record[$taxonomy . '_name'] = $terms[0]->name;
                            $record[$taxonomy . '_slug'] = $terms[0]->slug;
                            $record[$taxonomy . '_url'] = get_term_link($terms[0]);
                        }
                    }
                }
                
                // Benutzerdefinierte Felder (Meta)
                $meta_fields = get_post_meta($post->ID);
                foreach ($meta_fields as $key => $values) {
                    if (isset($values[0])) {
                        // Standardformat: meta_KEY
                        $record['meta_' . $key] = $values[0];
                        
                        // Bilder in Meta-Feldern erkennen und hinzufügen
                        if (is_numeric($values[0]) && wp_attachment_is_image($values[0])) {
                            $meta_image = wp_get_attachment_image_url($values[0], 'full');
                            if ($meta_image) {
                                $record['meta_image_' . $key] = $meta_image;
                            }
                        }
                    }
                }
                
                $data[] = $record;
            }
        }
        
        return $data;
    }
}