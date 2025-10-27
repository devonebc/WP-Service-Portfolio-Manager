<?php
/**
 * Plugin Name: WP Service Portfolio Manager
 * Description: Create and manage unlimited custom post types and taxonomies through an easy admin interface.
 * Author: DevOneBC
 * Author URI:https://github.com/devonebc
 * Version: 2.0.5
 * Text Domain: wp-service-portfolio-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DevOneBC_Portfolio_Manager {

	private static $instance = null;
	private $custom_post_types = array();
	private $custom_taxonomies = array();
	private $option_name = 'devonebc_portfolio_settings';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Register activation hook to set up default post type
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// Load saved settings
		$this->load_settings();
	}

	public function activate() {
		// Set up a default "Services" post type if no post types exist
		$settings = get_option( $this->option_name, array() );

		if ( empty( $settings['post_types'] ) ) {
			$default_settings = array(
				'post_types' => array(
					'services' => array(
						'singular'    => __( 'Service', 'wp-service-portfolio-manager' ),
						'plural'      => __( 'Services', 'wp-service-portfolio-manager' ),
						'slug'        => 'services',
						'menu_icon'   => 'dashicons-superhero',
						'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
						'has_archive' => true,
						'public'      => true,
						'show_in_rest' => true,
					)
				),
				'taxonomies' => array(
					'services' => array(
						'services_categories' => array(
							'singular'          => __( 'Category', 'wp-service-portfolio-manager' ),
							'plural'            => __( 'Categories', 'wp-service-portfolio-manager' ),
							'slug'              => 'services-categories',
							'hierarchical'      => true,
							'public'            => true,
							'show_in_nav_menus' => true,
							'show_in_rest'      => true,
						)
					)
				)
			);

			update_option( $this->option_name, $default_settings );
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-service-portfolio-manager',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	private function load_settings() {
		$settings = get_option( $this->option_name, array() );
		$this->custom_post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array();
		$this->custom_taxonomies = isset( $settings['taxonomies'] ) ? $settings['taxonomies'] : array();
	}

	private function save_settings() {
		$settings = array(
			'post_types' => $this->custom_post_types,
			'taxonomies' => $this->custom_taxonomies,
		);
		update_option( $this->option_name, $settings );
	}

	public function init() {
		$this->register_custom_post_types();
		$this->register_taxonomies();
		$this->register_shortcodes();
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Portfolio Manager', 'wp-service-portfolio-manager' ),
			__( 'Portfolio Manager', 'wp-service-portfolio-manager' ),
			'manage_options',
			'devonebc-portfolio-manager',
			array( $this, 'admin_page' ),
			'dashicons-portfolio',
			58
		);
	}

	public function admin_init() {
		// Handle form submissions
		if ( isset( $_POST['devonebc_add_post_type'] ) && check_admin_referer( 'devonebc_add_post_type' ) ) {
			$this->handle_add_post_type();
		}

		if ( isset( $_POST['devonebc_add_taxonomy'] ) && check_admin_referer( 'devonebc_add_taxonomy' ) ) {
			$this->handle_add_taxonomy();
		}

		if ( isset( $_GET['delete_post_type'] ) && check_admin_referer( 'devonebc_delete_post_type' ) ) {
			$this->handle_delete_post_type();
		}

		if ( isset( $_GET['delete_taxonomy'] ) && check_admin_referer( 'devonebc_delete_taxonomy' ) ) {
			$this->handle_delete_taxonomy();
		}
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'devonebc-portfolio-manager' ) !== false ) {
			wp_enqueue_script( 'devonebc-admin', plugins_url( 'assets/admin.js', __FILE__ ), array( 'jquery' ), '2.0.5', true );
			wp_enqueue_style( 'devonebc-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), '2.0.5' );

			// Add some inline JavaScript for better UX
			wp_add_inline_script( 'devonebc-admin', '
				jQuery(document).ready(function($) {
					// Auto-generate slug from singular name
					$("#singular_name").on("blur", function() {
						if ($("#post_type_slug").val() === "") {
							var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^-|-$/g, "");
							$("#post_type_slug").val(slug);
						}
					});

					// Auto-generate plural name
					$("#singular_name").on("blur", function() {
						if ($("#plural_name").val() === "") {
							var singular = $(this).val();
							var plural = singular + "s"; // Simple pluralization
							$("#plural_name").val(plural);
						}
					});

					// Auto-generate taxonomy slug from singular name
					$("#taxonomy_singular_name").on("blur", function() {
						if ($("#taxonomy_slug").val() === "") {
							var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^-|-$/g, "");
							$("#taxonomy_slug").val(slug);
						}
					});

					// Auto-generate taxonomy plural name
					$("#taxonomy_singular_name").on("blur", function() {
						if ($("#taxonomy_plural_name").val() === "") {
							var singular = $(this).val();
							var plural = singular + "s"; // Simple pluralization
							$("#taxonomy_plural_name").val(plural);
						}
					});

					// Confirm deletion
					$(".delete-post-type, .delete-taxonomy").on("click", function(e) {
						if (!confirm("' . esc_js( __( 'Are you sure you want to delete this? This action cannot be undone.', 'wp-service-portfolio-manager' ) ) . '")) {
							e.preventDefault();
						}
					});
				});
			' );
		}
	}

	private function handle_add_post_type() {
		$post_type_slug = sanitize_key( $_POST['post_type_slug'] );
		$singular_name = sanitize_text_field( $_POST['singular_name'] );
		$plural_name = sanitize_text_field( $_POST['plural_name'] );
		$menu_icon = sanitize_text_field( $_POST['menu_icon'] );

		if ( empty( $post_type_slug ) || empty( $singular_name ) || empty( $plural_name ) ) {
			add_settings_error( 'devonebc_portfolio', 'missing_fields', __( 'Please fill in all required fields.', 'wp-service-portfolio-manager' ) );
			return;
		}

		// Check if post type already exists
		if ( isset( $this->custom_post_types[ $post_type_slug ] ) ) {
			add_settings_error( 'devonebc_portfolio', 'post_type_exists', __( 'A post type with this slug already exists.', 'wp-service-portfolio-manager' ) );
			return;
		}

		$this->custom_post_types[ $post_type_slug ] = array(
			'singular'    => $singular_name,
			'plural'      => $plural_name,
			'slug'        => ! empty( $_POST['slug'] ) ? sanitize_title( $_POST['slug'] ) : $post_type_slug,
			'menu_icon'   => $menu_icon ?: 'dashicons-portfolio',
			'supports'    => isset( $_POST['supports'] ) ? array_map( 'sanitize_key', $_POST['supports'] ) : array( 'title', 'editor', 'thumbnail' ),
			'has_archive' => isset( $_POST['has_archive'] ),
			'public'      => isset( $_POST['public'] ),
			'show_in_rest' => isset( $_POST['show_in_rest'] ),
		);

		$this->save_settings();

		// Force immediate registration of the new post type
		$this->register_single_post_type( $post_type_slug, $this->custom_post_types[ $post_type_slug ] );

		// Flush rewrite rules
		flush_rewrite_rules();

		// Add success message
		add_settings_error( 'devonebc_portfolio', 'post_type_added',
			sprintf( __( 'Post type "%s" has been added successfully. The admin menu should refresh automatically.', 'wp-service-portfolio-manager' ), $plural_name ),
			'success'
		);

		// Force page refresh to update admin menu
		wp_redirect( add_query_arg( array( 'page' => 'devonebc-portfolio-manager', 'refresh' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function handle_add_taxonomy() {
		$taxonomy_slug = sanitize_key( $_POST['taxonomy_slug'] );
		$singular_name = sanitize_text_field( $_POST['taxonomy_singular_name'] );
		$plural_name = sanitize_text_field( $_POST['taxonomy_plural_name'] );
		$post_type = sanitize_key( $_POST['post_type'] );

		if ( empty( $taxonomy_slug ) || empty( $singular_name ) || empty( $plural_name ) || empty( $post_type ) ) {
			add_settings_error( 'devonebc_portfolio', 'taxonomy_missing_fields', __( 'Please fill in all required fields.', 'wp-service-portfolio-manager' ) );
			return;
		}

		if ( ! isset( $this->custom_taxonomies[ $post_type ] ) ) {
			$this->custom_taxonomies[ $post_type ] = array();
		}

		// Check if taxonomy already exists for this post type
		if ( isset( $this->custom_taxonomies[ $post_type ][ $taxonomy_slug ] ) ) {
			add_settings_error( 'devonebc_portfolio', 'taxonomy_exists', __( 'A taxonomy with this slug already exists for the selected post type.', 'wp-service-portfolio-manager' ) );
			return;
		}

		$this->custom_taxonomies[ $post_type ][ $taxonomy_slug ] = array(
			'singular'          => $singular_name,
			'plural'            => $plural_name,
			'slug'              => ! empty( $_POST['taxonomy_slug_custom'] ) ? sanitize_title( $_POST['taxonomy_slug_custom'] ) : $taxonomy_slug,
			'hierarchical'      => isset( $_POST['hierarchical'] ),
			'public'            => isset( $_POST['taxonomy_public'] ),
			'show_in_nav_menus' => isset( $_POST['show_in_nav_menus'] ),
			'show_in_rest'      => isset( $_POST['taxonomy_show_in_rest'] ),
		);

		$this->save_settings();

		// Force immediate registration of the taxonomy
		$this->register_single_taxonomy( $post_type, $taxonomy_slug, $this->custom_taxonomies[ $post_type ][ $taxonomy_slug ] );

		add_settings_error( 'devonebc_portfolio', 'taxonomy_added',
			sprintf( __( 'Taxonomy "%s" has been added successfully to "%s".', 'wp-service-portfolio-manager' ), $plural_name, $this->custom_post_types[ $post_type ]['plural'] ),
			'success'
		);
	}

	private function handle_delete_post_type() {
		$post_type_slug = sanitize_key( $_GET['delete_post_type'] );

		if ( isset( $this->custom_post_types[ $post_type_slug ] ) ) {
			$post_type_name = $this->custom_post_types[ $post_type_slug ]['plural'];
			unset( $this->custom_post_types[ $post_type_slug ] );

			// Also remove associated taxonomies
			if ( isset( $this->custom_taxonomies[ $post_type_slug ] ) ) {
				unset( $this->custom_taxonomies[ $post_type_slug ] );
			}

			$this->save_settings();
			flush_rewrite_rules();

			add_settings_error( 'devonebc_portfolio', 'post_type_deleted',
				sprintf( __( 'Post type "%s" has been deleted successfully.', 'wp-service-portfolio-manager' ), $post_type_name ),
				'success'
			);
		}
	}

	private function handle_delete_taxonomy() {
		$post_type_slug = sanitize_key( $_GET['post_type'] );
		$taxonomy_slug = sanitize_key( $_GET['delete_taxonomy'] );

		if ( isset( $this->custom_taxonomies[ $post_type_slug ][ $taxonomy_slug ] ) ) {
			$taxonomy_name = $this->custom_taxonomies[ $post_type_slug ][ $taxonomy_slug ]['plural'];
			unset( $this->custom_taxonomies[ $post_type_slug ][ $taxonomy_slug ] );

			$this->save_settings();

			add_settings_error( 'devonebc_portfolio', 'taxonomy_deleted',
				sprintf( __( 'Taxonomy "%s" has been deleted successfully.', 'wp-service-portfolio-manager' ), $taxonomy_name ),
				'success'
			);
		}
	}

	public function admin_page() {
		// Check if we need to show a refresh message
		if ( isset( $_GET['refresh'] ) && $_GET['refresh'] === '1' ) {
			echo '<div class="notice notice-info is-dismissible"><p>' .
				 __( 'Admin menu updated. If you don\'t see your new post type in the menu, please refresh the page.', 'wp-service-portfolio-manager' ) .
				 '</p></div>';
		}
		?>
		<div class="wrap devonebc-portfolio-admin">
			<h1><?php _e( 'Portfolio Manager', 'wp-service-portfolio-manager' ); ?></h1>

			<?php settings_errors( 'devonebc_portfolio' ); ?>

			<div class="devonebc-admin-container">
				<div class="devonebc-admin-main">
					<!-- STEP 1: Add Post Type Form -->
					<div class="devonebc-section" id="add-post-type-form">
						<div class="devonebc-section-header">
							<h2><?php _e( 'Step 1: Add New Post Type', 'wp-service-portfolio-manager' ); ?></h2>
						</div>
						<p class="description"><?php _e( 'First, create your custom post type. After saving, it will appear in the WordPress admin menu and you can then add taxonomies to it.', 'wp-service-portfolio-manager' ); ?></p>
						<form method="post" action="">
							<?php wp_nonce_field( 'devonebc_add_post_type' ); ?>
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="post_type_slug"><?php _e( 'Post Type Slug', 'wp-service-portfolio-manager' ); ?> *</label>
									</th>
									<td>
										<input type="text" name="post_type_slug" id="post_type_slug" class="regular-text" required>
										<p class="description"><?php _e( 'Lowercase letters, numbers, and underscores only (e.g., portfolio_items)', 'wp-service-portfolio-manager' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="singular_name"><?php _e( 'Singular Name', 'wp-service-portfolio-manager' ); ?> *</label>
									</th>
									<td>
										<input type="text" name="singular_name" id="singular_name" class="regular-text" required>
										<p class="description"><?php _e( 'The singular name for this post type (e.g., Portfolio Item)', 'wp-service-portfolio-manager' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="plural_name"><?php _e( 'Plural Name', 'wp-service-portfolio-manager' ); ?> *</label>
									</th>
									<td>
										<input type="text" name="plural_name" id="plural_name" class="regular-text" required>
										<p class="description"><?php _e( 'The plural name for this post type (e.g., Portfolio Items)', 'wp-service-portfolio-manager' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="slug"><?php _e( 'Custom URL Slug', 'wp-service-portfolio-manager' ); ?></label>
									</th>
									<td>
										<input type="text" name="slug" id="slug" class="regular-text">
										<p class="description"><?php _e( 'Optional: Custom URL slug for this post type (defaults to post type slug)', 'wp-service-portfolio-manager' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="menu_icon"><?php _e( 'Menu Icon', 'wp-service-portfolio-manager' ); ?></label>
									</th>
									<td>
										<input type="text" name="menu_icon" id="menu_icon" class="regular-text" value="dashicons-portfolio">
										<p class="description">
											<?php _e( 'Dashicons class name or full URL to icon image', 'wp-service-portfolio-manager' ); ?>
											<br>
											<a href="https://developer.wordpress.org/resource/dashicons/" target="_blank"><?php _e( 'View available Dashicons', 'wp-service-portfolio-manager' ); ?></a>
										</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Supports', 'wp-service-portfolio-manager' ); ?></th>
									<td>
										<label><input type="checkbox" name="supports[]" value="title" checked> <?php _e( 'Title', 'wp-service-portfolio-manager' ); ?></label><br>
										<label><input type="checkbox" name="supports[]" value="editor" checked> <?php _e( 'Editor', 'wp-service-portfolio-manager' ); ?></label><br>
										<label><input type="checkbox" name="supports[]" value="thumbnail" checked> <?php _e( 'Featured Image', 'wp-service-portfolio-manager' ); ?></label><br>
										<label><input type="checkbox" name="supports[]" value="excerpt"> <?php _e( 'Excerpt', 'wp-service-portfolio-manager' ); ?></label><br>
										<label><input type="checkbox" name="supports[]" value="custom-fields"> <?php _e( 'Custom Fields', 'wp-service-portfolio-manager' ); ?></label><br>
										<label><input type="checkbox" name="supports[]" value="comments"> <?php _e( 'Comments', 'wp-service-portfolio-manager' ); ?></label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Settings', 'wp-service-portfolio-manager' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="public" value="1" checked>
											<?php _e( 'Public', 'wp-service-portfolio-manager' ); ?>
										</label>
										<br>
										<label>
											<input type="checkbox" name="has_archive" value="1" checked>
											<?php _e( 'Has Archive', 'wp-service-portfolio-manager' ); ?>
										</label>
										<br>
										<label>
											<input type="checkbox" name="show_in_rest" value="1" checked>
											<?php _e( 'Show in REST API', 'wp-service-portfolio-manager' ); ?>
										</label>
									</td>
								</tr>
							</table>
							<?php submit_button( __( 'Add Post Type', 'wp-service-portfolio-manager' ), 'primary', 'devonebc_add_post_type' ); ?>
						</form>
					</div>

					<!-- STEP 2: Post Types List -->
					<div class="devonebc-section">
						<div class="devonebc-section-header">
							<h2><?php _e( 'Step 2: Your Custom Post Types', 'wp-service-portfolio-manager' ); ?></h2>
						</div>
						<p class="description"><?php _e( 'After creating post types above, they will appear here. You can then add taxonomies to them in the next step.', 'wp-service-portfolio-manager' ); ?></p>

						<?php if ( empty( $this->custom_post_types ) ) : ?>
							<div class="notice notice-warning">
								<p><?php _e( 'No custom post types created yet. Start by adding your first post type using the form above.', 'wp-service-portfolio-manager' ); ?></p>
							</div>
						<?php else : ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php _e( 'Post Type', 'wp-service-portfolio-manager' ); ?></th>
										<th><?php _e( 'Slug', 'wp-service-portfolio-manager' ); ?></th>
										<th><?php _e( 'Taxonomies', 'wp-service-portfolio-manager' ); ?></th>
										<th><?php _e( 'Actions', 'wp-service-portfolio-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$position = 25;
									foreach ( $this->custom_post_types as $slug => $post_type ) :
										$menu_position = $position++;
									?>
										<tr>
											<td>
												<strong><?php echo esc_html( $post_type['plural'] ); ?></strong>
												<br><small><?php echo esc_html( $post_type['singular'] ); ?></small>
												<br><small><strong><?php _e( 'Menu Position:', 'wp-service-portfolio-manager' ); ?></strong> <?php echo $menu_position; ?></small>
											</td>
											<td>
												<code>devonebc_<?php echo esc_html( $slug ); ?></code>
												<br><small><?php _e( 'URL:', 'wp-service-portfolio-manager' ); ?> /<?php echo esc_html( $post_type['slug'] ); ?>/</small>
											</td>
											<td>
												<?php if ( isset( $this->custom_taxonomies[ $slug ] ) && ! empty( $this->custom_taxonomies[ $slug ] ) ) : ?>
													<ul style="margin: 0; padding-left: 1em;">
														<?php foreach ( $this->custom_taxonomies[ $slug ] as $tax_slug => $taxonomy ) : ?>
															<li>
																<strong><?php echo esc_html( $taxonomy['plural'] ); ?></strong>
																(<code><?php echo esc_html( $tax_slug ); ?></code>)
																<br><small><?php _e( 'URL:', 'wp-service-portfolio-manager' ); ?> /<?php echo esc_html( $taxonomy['slug'] ); ?>/</small>
																<a href="<?php echo wp_nonce_url(
																	add_query_arg( array(
																		'page' => 'devonebc-portfolio-manager',
																		'post_type' => $slug,
																		'delete_taxonomy' => $tax_slug
																	), admin_url( 'admin.php' ) ),
																	'devonebc_delete_taxonomy'
																); ?>" class="delete delete-taxonomy" style="color:#a00; margin-left: 10px;">
																	<?php _e( 'Delete', 'wp-service-portfolio-manager' ); ?>
																</a>
															</li>
														<?php endforeach; ?>
													</ul>
												<?php else : ?>
													<span class="description"><?php _e( 'No taxonomies', 'wp-service-portfolio-manager' ); ?></span>
												<?php endif; ?>
											</td>
											<td>
												<a href="<?php echo wp_nonce_url(
													add_query_arg( array(
														'page' => 'devonebc-portfolio-manager',
														'delete_post_type' => $slug
													), admin_url( 'admin.php' ) ),
													'devonebc_delete_post_type'
												); ?>" class="button button-secondary delete-post-type">
													<?php _e( 'Delete', 'wp-service-portfolio-manager' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<!-- STEP 3: Add Taxonomy Form -->
					<div class="devonebc-section">
						<div class="devonebc-section-header">
							<h2><?php _e( 'Step 3: Add New Taxonomy', 'wp-service-portfolio-manager' ); ?></h2>
						</div>
						<p class="description"><?php _e( 'After creating post types, you can add taxonomies (categories, tags, etc.) to organize your content.', 'wp-service-portfolio-manager' ); ?></p>

						<?php if ( empty( $this->custom_post_types ) ) : ?>
							<div class="notice notice-info">
								<p><?php _e( 'You need to create at least one post type before you can add taxonomies. Use the form in Step 1 above to create your first post type.', 'wp-service-portfolio-manager' ); ?></p>
							</div>
						<?php else : ?>
							<form method="post" action="">
								<?php wp_nonce_field( 'devonebc_add_taxonomy' ); ?>
								<table class="form-table">
									<tr>
										<th scope="row">
											<label for="taxonomy_slug"><?php _e( 'Taxonomy Slug', 'wp-service-portfolio-manager' ); ?> *</label>
										</th>
										<td>
											<input type="text" name="taxonomy_slug" id="taxonomy_slug" class="regular-text" required>
											<p class="description"><?php _e( 'Lowercase letters, numbers, and underscores only (e.g., project_categories)', 'wp-service-portfolio-manager' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="taxonomy_singular_name"><?php _e( 'Singular Name', 'wp-service-portfolio-manager' ); ?> *</label>
										</th>
										<td>
											<input type="text" name="taxonomy_singular_name" id="taxonomy_singular_name" class="regular-text" required>
											<p class="description"><?php _e( 'The singular name for this taxonomy (e.g., Category)', 'wp-service-portfolio-manager' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="taxonomy_plural_name"><?php _e( 'Plural Name', 'wp-service-portfolio-manager' ); ?> *</label>
										</th>
										<td>
											<input type="text" name="taxonomy_plural_name" id="taxonomy_plural_name" class="regular-text" required>
											<p class="description"><?php _e( 'The plural name for this taxonomy (e.g., Categories)', 'wp-service-portfolio-manager' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="post_type"><?php _e( 'Attach to Post Type', 'wp-service-portfolio-manager' ); ?> *</label>
										</th>
										<td>
											<select name="post_type" id="post_type" required>
												<option value=""><?php _e( 'Select a post type', 'wp-service-portfolio-manager' ); ?></option>
												<?php foreach ( $this->custom_post_types as $slug => $post_type ) : ?>
													<option value="<?php echo esc_attr( $slug ); ?>">
														<?php echo esc_html( $post_type['plural'] ); ?> (<?php echo esc_html( $slug ); ?>)
													</option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="taxonomy_slug_custom"><?php _e( 'Custom URL Slug', 'wp-service-portfolio-manager' ); ?></label>
										</th>
										<td>
											<input type="text" name="taxonomy_slug_custom" id="taxonomy_slug_custom" class="regular-text">
											<p class="description"><?php _e( 'Optional: Custom URL slug for this taxonomy (defaults to taxonomy slug)', 'wp-service-portfolio-manager' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php _e( 'Settings', 'wp-service-portfolio-manager' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="hierarchical" value="1" checked>
												<?php _e( 'Hierarchical (like categories)', 'wp-service-portfolio-manager' ); ?>
											</label>
											<br>
											<label>
												<input type="checkbox" name="taxonomy_public" value="1" checked>
												<?php _e( 'Public', 'wp-service-portfolio-manager' ); ?>
											</label>
											<br>
											<label>
												<input type="checkbox" name="show_in_nav_menus" value="1" checked>
												<?php _e( 'Show in navigation menus', 'wp-service-portfolio-manager' ); ?></label>
											<br>
											<label>
												<input type="checkbox" name="taxonomy_show_in_rest" value="1" checked>
												<?php _e( 'Show in REST API', 'wp-service-portfolio-manager' ); ?>
											</label>
										</td>
									</tr>
								</table>
								<?php submit_button( __( 'Add Taxonomy', 'wp-service-portfolio-manager' ), 'primary', 'devonebc_add_taxonomy' ); ?>
							</form>
						<?php endif; ?>
					</div>
				</div>

				<div class="devonebc-admin-sidebar">
					<!-- Quick Help -->
					<div class="devonebc-section">
						<h3><?php _e( 'How It Works', 'wp-service-portfolio-manager' ); ?></h3>
						<ol>
							<li><strong><?php _e( 'Step 1:', 'wp-service-portfolio-manager' ); ?></strong> <?php _e( 'Create a custom post type', 'wp-service-portfolio-manager' ); ?></li>
							<li><strong><?php _e( 'Step 2:', 'wp-service-portfolio-manager' ); ?></strong> <?php _e( 'View and manage your post types', 'wp-service-portfolio-manager' ); ?></li>
							<li><strong><?php _e( 'Step 3:', 'wp-service-portfolio-manager' ); ?></strong> <?php _e( 'Add taxonomies to organize content', 'wp-service-portfolio-manager' ); ?></li>
						</ol>
						<p><?php _e( 'Your custom post types will appear in the WordPress admin menu after creation.', 'wp-service-portfolio-manager' ); ?></p>
					</div>

					<!-- Shortcode Help -->
					<div class="devonebc-section">
						<h3><?php _e( 'Shortcode Usage', 'wp-service-portfolio-manager' ); ?></h3>
						<p><?php _e( 'Use this shortcode to display your custom post types:', 'wp-service-portfolio-manager' ); ?></p>
						<code>[display_portfolio type="post_type_slug" count="6" columns="3"]</code>
						<p><strong><?php _e( 'Parameters:', 'wp-service-portfolio-manager' ); ?></strong></p>
						<ul>
							<li><strong>type:</strong> <?php _e( 'The post type slug (required)', 'wp-service-portfolio-manager' ); ?></li>
							<li><strong>count:</strong> <?php _e( 'Number of items to show', 'wp-service-portfolio-manager' ); ?></li>
							<li><strong>columns:</strong> <?php _e( 'Number of grid columns', 'wp-service-portfolio-manager' ); ?></li>
							<li><strong>category:</strong> <?php _e( 'Filter by category slug', 'wp-service-portfolio-manager' ); ?></li>
							<li><strong>show_excerpt:</strong> <?php _e( 'Show excerpt (true/false)', 'wp-service-portfolio-manager' ); ?></li>
							<li><strong>show_date:</strong> <?php _e( 'Show date (true/false)', 'wp-service-portfolio-manager' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register a single post type immediately
	 */
	private function register_single_post_type( $post_type_slug, $args ) {
		$full_post_type_slug = 'devonebc_' . $post_type_slug;

		// Calculate unique menu position starting from 25 and incrementing by 5 for each
		$post_type_keys = array_keys( $this->custom_post_types );
		$position_index = array_search( $post_type_slug, $post_type_keys );
		$menu_position = 25 + ( $position_index * 5 );

		$post_type_args = array(
			'labels'              => array(
				'name'               => esc_html( $args['plural'] ),
				'singular_name'      => esc_html( $args['singular'] ),
				'add_new'            => sprintf( esc_html__( 'Add New %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'add_new_item'       => sprintf( esc_html__( 'Add New %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'edit_item'          => sprintf( esc_html__( 'Edit %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'new_item'           => sprintf( esc_html__( 'New %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'view_item'          => sprintf( esc_html__( 'View %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'search_items'       => sprintf( esc_html__( 'Search %s', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
				'not_found'          => sprintf( esc_html__( 'No %s found', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
				'not_found_in_trash' => sprintf( esc_html__( 'No %s found in Trash', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
				'all_items'          => esc_html( $args['plural'] ),
				'menu_name'          => esc_html( $args['plural'] ),
			),
			'public'              => $args['public'],
			'has_archive'         => $args['has_archive'],
			'show_in_rest'        => $args['show_in_rest'],
			'menu_icon'           => $args['menu_icon'],
			'supports'            => $args['supports'],
			'rewrite'             => array(
				'slug'       => $args['slug'],
				'with_front' => false,
			),
			'menu_position'       => $menu_position,
			'show_in_menu'        => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'publicly_queryable'  => $args['public'],
			'exclude_from_search' => ! $args['public'],
			'query_var'           => true,
		);

		register_post_type( $full_post_type_slug, $post_type_args );
	}

	/**
	 * Register all custom post types
	 */
	private function register_custom_post_types() {
		if ( empty( $this->custom_post_types ) ) {
			return;
		}

		$position = 25; // Start position below "Posts" (which is at 5)

		foreach ( $this->custom_post_types as $post_type_slug => $args ) {
			$full_post_type_slug = 'devonebc_' . $post_type_slug;

			$post_type_args = array(
				'labels'              => array(
					'name'               => esc_html( $args['plural'] ),
					'singular_name'      => esc_html( $args['singular'] ),
					'add_new'            => sprintf( esc_html__( 'Add New %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
					'add_new_item'       => sprintf( esc_html__( 'Add New %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
					'edit_item'          => sprintf( esc_html__( 'Edit %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
					'new_item'           => sprintf( esc_html__( 'New %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
					'view_item'          => sprintf( esc_html__( 'View %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
					'search_items'       => sprintf( esc_html__( 'Search %s', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
					'not_found'          => sprintf( esc_html__( 'No %s found', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
					'not_found_in_trash' => sprintf( esc_html__( 'No %s found in Trash', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
					'all_items'          => esc_html( $args['plural'] ),
					'menu_name'          => esc_html( $args['plural'] ),
				),
				'public'              => $args['public'],
				'has_archive'         => $args['has_archive'],
				'show_in_rest'        => $args['show_in_rest'],
				'menu_icon'           => $args['menu_icon'],
				'supports'            => $args['supports'],
				'rewrite'             => array(
					'slug'       => $args['slug'],
					'with_front' => false,
				),
				'menu_position'       => $position,
				'show_in_menu'        => true,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'publicly_queryable'  => $args['public'],
				'exclude_from_search' => ! $args['public'],
				'query_var'           => true,
			);

			register_post_type( $full_post_type_slug, $post_type_args );
			$position += 5; // Increment position by 5 for each post type
		}
	}

	/**
	 * Register a single taxonomy immediately
	 */
	private function register_single_taxonomy( $post_type_slug, $taxonomy_slug, $args ) {
		$full_post_type_slug = 'devonebc_' . $post_type_slug;
		$full_taxonomy_slug = 'devonebc_' . $taxonomy_slug;

		$taxonomy_args = array(
			'labels'            => array(
				'name'              => esc_html( $args['plural'] ),
				'singular_name'     => esc_html( $args['singular'] ),
				'search_items'      => sprintf( esc_html__( 'Search %s', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
				'all_items'         => sprintf( esc_html__( 'All %s', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
				'parent_item'       => sprintf( esc_html__( 'Parent %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'parent_item_colon' => sprintf( esc_html__( 'Parent %s:', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'edit_item'         => sprintf( esc_html__( 'Edit %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'update_item'       => sprintf( esc_html__( 'Update %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'add_new_item'      => sprintf( esc_html__( 'Add New %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'new_item_name'     => sprintf( esc_html__( 'New %s Name', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
				'menu_name'         => esc_html( $args['plural'] ),
			),
			'hierarchical'      => $args['hierarchical'],
			'public'            => $args['public'],
			'show_in_nav_menus' => $args['show_in_nav_menus'],
			'show_in_rest'      => $args['show_in_rest'],
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array(
				'slug' => $args['slug'],
			),
		);

		register_taxonomy( $full_taxonomy_slug, array( $full_post_type_slug ), $taxonomy_args );
	}

	/**
	 * Register all taxonomies
	 */
	private function register_taxonomies() {
		if ( empty( $this->custom_taxonomies ) ) {
			return;
		}

		foreach ( $this->custom_taxonomies as $post_type_slug => $taxonomies ) {
			$full_post_type_slug = 'devonebc_' . $post_type_slug;

			foreach ( $taxonomies as $taxonomy_slug => $args ) {
				$full_taxonomy_slug = 'devonebc_' . $taxonomy_slug;

				$taxonomy_args = array(
					'labels'            => array(
						'name'              => esc_html( $args['plural'] ),
						'singular_name'     => esc_html( $args['singular'] ),
						'search_items'      => sprintf( esc_html__( 'Search %s', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
						'all_items'         => sprintf( esc_html__( 'All %s', 'wp-service-portfolio-manager' ), esc_html( $args['plural'] ) ),
						'parent_item'       => sprintf( esc_html__( 'Parent %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
						'parent_item_colon' => sprintf( esc_html__( 'Parent %s:', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
						'edit_item'         => sprintf( esc_html__( 'Edit %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
						'update_item'       => sprintf( esc_html__( 'Update %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
						'add_new_item'      => sprintf( esc_html__( 'Add New %s', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
						'new_item_name'     => sprintf( esc_html__( 'New %s Name', 'wp-service-portfolio-manager' ), esc_html( $args['singular'] ) ),
						'menu_name'         => esc_html( $args['plural'] ),
					),
					'hierarchical'      => $args['hierarchical'],
					'public'            => $args['public'],
					'show_in_nav_menus' => $args['show_in_nav_menus'],
					'show_in_rest'      => $args['show_in_rest'],
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => array(
						'slug' => $args['slug'],
					),
				);

				register_taxonomy( $full_taxonomy_slug, array( $full_post_type_slug ), $taxonomy_args );
			}
		}
	}

	private function register_shortcodes() {
		add_shortcode( 'display_portfolio', array( $this, 'portfolio_shortcode' ) );
	}

	public function portfolio_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'type'          => '',
			'count'         => 6,
			'columns'       => 3,
			'category'      => '',
			'show_excerpt'  => 'true',
			'show_date'     => 'false',
		), $atts, 'display_portfolio' );

		if ( empty( $atts['type'] ) ) {
			return '<p>' . __( 'Please specify a post type using the type parameter.', 'wp-service-portfolio-manager' ) . '</p>';
		}

		$post_type_slug = 'devonebc_' . sanitize_key( $atts['type'] );

		// Check if post type exists
		if ( ! post_type_exists( $post_type_slug ) ) {
			return '<p>' . sprintf( __( 'Post type "%s" not found.', 'wp-service-portfolio-manager' ), esc_html( $atts['type'] ) ) . '</p>';
		}

		$query_args = array(
			'post_type'      => $post_type_slug,
			'posts_per_page' => intval( $atts['count'] ),
			'post_status'    => 'publish',
		);

		// Add category filter if specified
		if ( ! empty( $atts['category'] ) ) {
			$taxonomy_slug = 'devonebc_' . sanitize_key( $atts['type'] ) . '_categories';
			if ( taxonomy_exists( $taxonomy_slug ) ) {
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => $taxonomy_slug,
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $atts['category'] ),
					),
				);
			}
		}

		$portfolio_query = new WP_Query( $query_args );

		if ( ! $portfolio_query->have_posts() ) {
			return '<p>' . __( 'No items found.', 'wp-service-portfolio-manager' ) . '</p>';
		}

		$columns_class = 'devonebc-grid-' . intval( $atts['columns'] );
		$output = '<div class="devonebc-portfolio-grid ' . esc_attr( $columns_class ) . '">';

		while ( $portfolio_query->have_posts() ) {
			$portfolio_query->the_post();

			$output .= '<div class="devonebc-portfolio-item">';

			if ( has_post_thumbnail() ) {
				$output .= '<div class="devonebc-portfolio-image">';
				$output .= '<a href="' . get_permalink() . '">';
				$output .= get_the_post_thumbnail( get_the_ID(), 'medium' );
				$output .= '</a>';
				$output .= '</div>';
			}

			$output .= '<div class="devonebc-portfolio-content">';
			$output .= '<h3 class="devonebc-portfolio-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';

			if ( 'true' === $atts['show_date'] ) {
				$output .= '<span class="devonebc-portfolio-date">' . get_the_date() . '</span>';
			}

			if ( 'true' === $atts['show_excerpt'] && has_excerpt() ) {
				$output .= '<div class="devonebc-portfolio-excerpt">' . get_the_excerpt() . '</div>';
			}

			$output .= '</div>';
			$output .= '</div>';
		}

		$output .= '</div>';
		$output .= '<style>
			.devonebc-portfolio-grid { display: grid; gap: 20px; margin: 20px 0; }
			.devonebc-grid-1 { grid-template-columns: 1fr; }
			.devonebc-grid-2 { grid-template-columns: repeat(2, 1fr); }
			.devonebc-grid-3 { grid-template-columns: repeat(3, 1fr); }
			.devonebc-grid-4 { grid-template-columns: repeat(4, 1fr); }
			.devonebc-portfolio-item { border: 1px solid #ddd; padding: 15px; }
			.devonebc-portfolio-image { margin-bottom: 15px; }
			.devonebc-portfolio-image img { width: 100%; height: auto; }
			.devonebc-portfolio-title { margin: 0 0 10px 0; font-size: 1.2em; }
			.devonebc-portfolio-date { color: #666; font-size: 0.9em; }
			.devonebc-portfolio-excerpt { margin-top: 10px; }
			@media (max-width: 768px) {
				.devonebc-grid-2, .devonebc-grid-3, .devonebc-grid-4 { grid-template-columns: 1fr; }
			}
		</style>';

		wp_reset_postdata();

		return $output;
	}
}

// Initialize the plugin
DevOneBC_Portfolio_Manager::get_instance();
?>
