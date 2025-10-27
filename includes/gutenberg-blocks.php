<?php
/**
 * Gutenberg Blocks for WP Service Portfolio Manager
 * 
 * @package DevOneBC_Services
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DevOneBC_Services_Gutenberg {
    
    public function __construct() {
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_assets' ) );
    }
    
    public function enqueue_block_assets() {
        $asset_file = include plugin_dir_path( __FILE__ ) . '../assets/js/blocks.asset.php';
        
        wp_enqueue_script(
            'devonebc-services-blocks',
            plugins_url( '../assets/js/blocks.js', __FILE__ ),
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );
        
        wp_enqueue_style(
            'devonebc-services-blocks-editor',
            plugins_url( '../assets/css/blocks-editor.css', __FILE__ ),
            array( 'wp-edit-blocks' ),
            '2.0.0'
        );
        
        // Localize script for translations and data
        wp_localize_script( 'devonebc-services-blocks', 'devonebcServicesData', array(
            'services' => $this->get_services_data(),
            'taxonomies' => $this->get_taxonomies_data(),
            'imageSizes' => $this->get_image_sizes(),
        ) );
    }
    
    public function register_blocks() {
        register_block_type( 'devonebc/services-grid', array(
            'editor_script' => 'devonebc-services-blocks',
            'editor_style'  => 'devonebc-services-blocks-editor',
            'render_callback' => array( $this, 'render_services_grid' ),
            'attributes' => array(
                'count' => array(
                    'type' => 'number',
                    'default' => 6,
                ),
                'columns' => array(
                    'type' => 'number',
                    'default' => 3,
                ),
                'category' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'taxonomy' => array(
                    'type' => 'string',
                    'default' => 'services_categories',
                ),
                'showExcerpt' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'showDate' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'imageSize' => array(
                    'type' => 'string',
                    'default' => 'medium',
                ),
                'orderby' => array(
                    'type' => 'string',
                    'default' => 'date',
                ),
                'order' => array(
                    'type' => 'string',
                    'default' => 'DESC',
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => '',
                ),
            ),
        ) );
        
        register_block_type( 'devonebc/single-service', array(
            'editor_script' => 'devonebc-services-blocks',
            'editor_style'  => 'devonebc-services-blocks-editor',
            'render_callback' => array( $this, 'render_single_service' ),
            'attributes' => array(
                'serviceId' => array(
                    'type' => 'number',
                    'default' => 0,
                ),
                'showImage' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'showContent' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'layout' => array(
                    'type' => 'string',
                    'default' => 'vertical',
                ),
            ),
        ) );
    }
    
    public function render_services_grid( $attributes ) {
        $shortcode_atts = array(
            'count' => absint( $attributes['count'] ),
            'category' => sanitize_text_field( $attributes['category'] ),
            'taxonomy' => sanitize_key( $attributes['taxonomy'] ),
            'show_excerpt' => (bool) $attributes['showExcerpt'],
            'show_date' => (bool) $attributes['showDate'],
            'image_size' => sanitize_key( $attributes['imageSize'] ),
            'orderby' => sanitize_key( $attributes['orderby'] ),
            'order' => sanitize_key( $attributes['order'] ),
        );
        
        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => 'devonebc-services-grid columns-' . absint( $attributes['columns'] ) . ' ' . esc_attr( $attributes['className'] ),
        ) );
        
        $shortcode_string = '';
        foreach ( $shortcode_atts as $key => $value ) {
            if ( ! empty( $value ) ) {
                $shortcode_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
            }
        }
        
        $output = do_shortcode( '[display_services' . $shortcode_string . ']' );
        
        return sprintf(
            '<div %s>%s</div>',
            $wrapper_attributes,
            wp_kses_post( $output )
        );
    }
    
    public function render_single_service( $attributes ) {
        if ( ! $attributes['serviceId'] ) {
            return '<p>' . esc_html__( 'Please select a service to display.', 'wp-service-portfolio-manager' ) . '</p>';
        }
        
        $service = get_post( absint( $attributes['serviceId'] ) );
        if ( ! $service || $service->post_type !== 'devonebc_cpt_service' ) {
            return '<p>' . esc_html__( 'Selected service not found.', 'wp-service-portfolio-manager' ) . '</p>';
        }
        
        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => 'devonebc-single-service layout-' . esc_attr( $attributes['layout'] ),
        ) );
        
        ob_start();
        ?>
        <div <?php echo $wrapper_attributes; ?>>
            <?php if ( $attributes['showImage'] && has_post_thumbnail( $service->ID ) ) : ?>
                <div class="devonebc-service-image">
                    <?php echo get_the_post_thumbnail( $service->ID, 'large' ); ?>
                </div>
            <?php endif; ?>
            
            <div class="devonebc-service-content">
                <h3 class="devonebc-service-title"><?php echo esc_html( $service->post_title ); ?></h3>
                
                <?php if ( $attributes['showContent'] ) : ?>
                    <div class="devonebc-service-description">
                        <?php echo wp_kses_post( apply_filters( 'the_content', $service->post_content ) ); ?>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo esc_url( get_permalink( $service->ID ) ); ?>" class="devonebc-service-link">
                    <?php echo esc_html__( 'Learn More', 'wp-service-portfolio-manager' ); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_services_data() {
        $services = get_posts( array(
            'post_type' => 'devonebc_cpt_service',
            'post_status' => 'publish',
            'numberposts' => 100,
            'orderby' => 'title',
            'order' => 'ASC',
        ) );
        
        $data = array();
        foreach ( $services as $service ) {
            $data[] = array(
                'value' => absint( $service->ID ),
                'label' => esc_html( $service->post_title ),
            );
        }
        
        return $data;
    }
    
    private function get_taxonomies_data() {
        $plugin = DevOneBC_Services_Plugin::get_instance();
        $taxonomies = $plugin->get_taxonomies();
        
        $data = array();
        foreach ( $taxonomies as $slug => $args ) {
            $full_slug = 'devonebc_' . $slug;
            $terms = get_terms( array(
                'taxonomy' => $full_slug,
                'hide_empty' => false,
            ) );
            
            $term_data = array();
            foreach ( $terms as $term ) {
                $term_data[] = array(
                    'value' => esc_attr( $term->slug ),
                    'label' => esc_html( $term->name ),
                );
            }
            
            $data[ $slug ] = array(
                'label' => esc_html( $args['plural'] ),
                'terms' => $term_data,
            );
        }
        
        return $data;
    }
    
    private function get_image_sizes() {
        $sizes = get_intermediate_image_sizes();
        $data = array();
        
        foreach ( $sizes as $size ) {
            $data[] = array(
                'value' => esc_attr( $size ),
                'label' => esc_html( ucfirst( str_replace( array( '-', '_' ), ' ', $size ) ) ),
            );
        }
        
        return $data;
    }
}

// Initialize Gutenberg blocks
new DevOneBC_Services_Gutenberg();