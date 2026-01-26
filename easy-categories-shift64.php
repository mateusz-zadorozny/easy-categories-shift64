<?php
/**
 * Plugin Name:     Easy Categories Shift64
 * Description:     Drag & drop WooCommerce product category ordering with hierarchy management
 * Author:          SHIFT64
 * Author URI:      https://shift64.com
 * Text Domain:     easy-categories-shift64
 * Domain Path:     /languages
 * Version:         1.0.0
 * Requires PHP:    8.0
 * Requires at least: 6.8
 * WC requires at least: 10.0
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Easy_Categories_Shift64
 */

declare(strict_types=1);

namespace EasyCategoriesShift64;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'ECS64_VERSION', '0.0.1' );
define( 'ECS64_PLUGIN_FILE', __FILE__ );
define( 'ECS64_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECS64_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
function ecs64_check_woocommerce(): bool {
	return class_exists( 'WooCommerce' );
}

/**
 * Admin notice if WooCommerce is not active
 */
function ecs64_woocommerce_missing_notice(): void {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Easy Categories Shift64 wymaga aktywnego WooCommerce.', 'easy-categories-shift64' ); ?></p>
	</div>
	<?php
}

/**
 * Initialize plugin
 */
function ecs64_init(): void {
	// Check WooCommerce dependency.
	if ( ! ecs64_check_woocommerce() ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\ecs64_woocommerce_missing_notice' );
		return;
	}

	// Load plugin classes.
	require_once ECS64_PLUGIN_DIR . 'includes/class-category-manager.php';
	require_once ECS64_PLUGIN_DIR . 'includes/class-admin-page.php';

	// Initialize admin page.
	new Admin_Page();

	// Register REST API endpoints.
	add_action( 'rest_api_init', __NAMESPACE__ . '\\ecs64_register_rest_routes' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\ecs64_init' );

/**
 * Register REST API routes
 */
function ecs64_register_rest_routes(): void {
	register_rest_route(
		'ecs64/v1',
		'/update-order',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\ecs64_handle_update_order',
			'permission_callback' => function () {
				return current_user_can( 'manage_woocommerce' );
			},
			'args'                => array(
				'category_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'action'      => array(
					'required'          => true,
					'type'              => 'string',
					'enum'              => array( 'move_up', 'move_down', 'move_left', 'move_right', 'set_order', 'set_parent' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'new_order'   => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'new_parent'  => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'ecs64/v1',
		'/get-categories',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\ecs64_handle_get_categories',
			'permission_callback' => function () {
				return current_user_can( 'manage_woocommerce' );
			},
			'args'                => array(
				'parent_only' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);
}

/**
 * Handle update order REST request.
 *
 * @param \WP_REST_Request $request The REST request object.
 * @return \WP_REST_Response The REST response.
 */
function ecs64_handle_update_order( \WP_REST_Request $request ): \WP_REST_Response {
	$category_id = $request->get_param( 'category_id' );
	$action      = $request->get_param( 'action' );
	$new_order   = $request->get_param( 'new_order' );
	$new_parent  = $request->get_param( 'new_parent' );

	$manager = new Category_Manager();

	try {
		switch ( $action ) {
			case 'move_up':
				$result = $manager->move_category_up( $category_id );
				break;
			case 'move_down':
				$result = $manager->move_category_down( $category_id );
				break;
			case 'move_left':
				$result = $manager->move_category_left( $category_id );
				break;
			case 'move_right':
				$result = $manager->move_category_right( $category_id );
				break;
			case 'set_order':
				$result = $manager->set_category_order( $category_id, $new_order );
				break;
			case 'set_parent':
				$result = $manager->set_category_parent( $category_id, $new_parent ?? 0 );
				break;
			default:
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Unknown action',
					),
					400
				);
		}

		if ( $result ) {
			return new \WP_REST_Response(
				array(
					'success'    => true,
					'categories' => $manager->get_category_tree(),
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => 'Failed to update',
			),
			500
		);
	} catch ( \Exception $e ) {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => $e->getMessage(),
			),
			500
		);
	}
}

/**
 * Handle get categories REST request.
 *
 * @param \WP_REST_Request $request The REST request object.
 * @return \WP_REST_Response The REST response.
 */
function ecs64_handle_get_categories( \WP_REST_Request $request ): \WP_REST_Response {
	$parent_only = $request->get_param( 'parent_only' );

	$manager = new Category_Manager();

	if ( $parent_only ) {
		$categories = $manager->get_root_categories();
	} else {
		$categories = $manager->get_category_tree();
	}

	return new \WP_REST_Response(
		array(
			'success'    => true,
			'categories' => $categories,
		),
		200
	);
}
