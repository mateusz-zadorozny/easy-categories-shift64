<?php
/**
 * Admin Page class
 *
 * @package Easy_Categories_Shift64
 */

declare(strict_types=1);

namespace EasyCategoriesShift64;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin page rendering and assets.
 */
class Admin_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add submenu page under Products.
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Kolejność kategorii', 'easy-categories-shift64' ),
			__( 'Kolejność kategorii', 'easy-categories-shift64' ),
			'manage_woocommerce',
			'ecs64-category-order',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'product_page_ecs64-category-order' !== $hook ) {
			return;
		}

		// jQuery UI Sortable.
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Plugin CSS.
		wp_enqueue_style(
			'ecs64-admin',
			ECS64_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ECS64_VERSION
		);

		// Plugin JS.
		wp_enqueue_script(
			'ecs64-admin',
			ECS64_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-api-fetch' ),
			ECS64_VERSION,
			true
		);

		// Localize script with data.
		$manager = new Category_Manager();

		wp_localize_script(
			'ecs64-admin',
			'ecs64Data',
			array(
				'restUrl'      => rest_url( 'ecs64/v1/' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'categories'   => $manager->get_category_tree(),
				'childlessIds' => $manager->get_categories_without_children(),
				'i18n'         => array(
					'loading'   => __( 'Ładowanie...', 'easy-categories-shift64' ),
					'saving'    => __( 'Zapisywanie...', 'easy-categories-shift64' ),
					'saved'     => __( 'Zapisano!', 'easy-categories-shift64' ),
					'error'     => __( 'Błąd podczas zapisywania', 'easy-categories-shift64' ),
					'moveUp'    => __( 'Przesuń w górę', 'easy-categories-shift64' ),
					'moveDown'  => __( 'Przesuń w dół', 'easy-categories-shift64' ),
					'moveLeft'  => __( 'Przesuń w lewo (wyższy poziom)', 'easy-categories-shift64' ),
					'moveRight' => __( 'Przesuń w prawo (niższy poziom)', 'easy-categories-shift64' ),
					'products'  => __( 'produktów', 'easy-categories-shift64' ),
				),
			)
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_page(): void {
		?>
		<div class="wrap ecs64-wrap">
			<h1><?php esc_html_e( 'Kolejność kategorii produktów', 'easy-categories-shift64' ); ?></h1>

			<div class="ecs64-toolbar">
				<div class="ecs64-filters">
					<label class="ecs64-filter">
						<input type="checkbox" id="ecs64-parent-only" />
						<?php esc_html_e( 'Pokaż tylko kategorie główne', 'easy-categories-shift64' ); ?>
					</label>
					<label class="ecs64-filter">
						<input type="checkbox" id="ecs64-highlight-childless" checked />
						<?php esc_html_e( 'Wyróżnij kategorie główne bez podkategorii', 'easy-categories-shift64' ); ?>
					</label>
				</div>

				<div class="ecs64-legend">
					<span class="ecs64-legend-item">
						<span class="ecs64-legend-color ecs64-childless"></span>
						<?php esc_html_e( 'Kategoria główna bez podkategorii', 'easy-categories-shift64' ); ?>
					</span>
				</div>

				<div class="ecs64-status" id="ecs64-status"></div>
			</div>

			<div class="ecs64-container">
				<div class="ecs64-category-tree" id="ecs64-tree">
					<div class="ecs64-loading">
						<span class="spinner is-active"></span>
						<?php esc_html_e( 'Ładowanie kategorii...', 'easy-categories-shift64' ); ?>
					</div>
				</div>
			</div>

			<div class="ecs64-help">
				<h3><?php esc_html_e( 'Instrukcja', 'easy-categories-shift64' ); ?></h3>
				<ul>
					<li><strong><?php esc_html_e( 'Przeciągnij i upuść', 'easy-categories-shift64' ); ?></strong> - <?php esc_html_e( 'Złap kategorię za uchwyt i przeciągnij w nowe miejsce', 'easy-categories-shift64' ); ?></li>
					<li><strong>▲ ▼</strong> - <?php esc_html_e( 'Przesuń kategorię w górę lub w dół w obrębie poziomu', 'easy-categories-shift64' ); ?></li>
					<li><strong>◀ ▶</strong> - <?php esc_html_e( 'Zmień poziom hierarchii (lewo = wyższy poziom, prawo = podkategoria poprzedniej)', 'easy-categories-shift64' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}
}
