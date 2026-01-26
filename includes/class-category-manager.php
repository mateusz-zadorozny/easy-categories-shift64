<?php
/**
 * Category Manager class
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
 * Manages WooCommerce product categories ordering.
 */
class Category_Manager {

	/**
	 * Taxonomy name.
	 */
	private const TAXONOMY = 'product_cat';

	/**
	 * Meta key for mega menu column position.
	 */
	private const POSITION_META_KEY = 'merida_mega_menu_column_position';

	/**
	 * Get full category tree with hierarchy.
	 *
	 * @return array Category tree.
	 */
	public function get_category_tree(): array {
		$categories = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'meta_value_num',
				'meta_key'   => 'order',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $categories ) ) {
			return array();
		}

		// Build hierarchical tree.
		return $this->build_tree( $categories );
	}

	/**
	 * Get only root categories (parent = 0).
	 *
	 * @return array Root categories.
	 */
	public function get_root_categories(): array {
		$categories = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'parent'     => 0,
				'orderby'    => 'meta_value_num',
				'meta_key'   => 'order',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $categories ) ) {
			return array();
		}

		return array_map( array( $this, 'format_category' ), $categories );
	}

	/**
	 * Get categories without children.
	 *
	 * @return array Childless category IDs.
	 */
	public function get_categories_without_children(): array {
		$all_categories = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $all_categories ) ) {
			return array();
		}

		$parent_ids = array();
		foreach ( $all_categories as $cat ) {
			if ( $cat->parent > 0 ) {
				$parent_ids[] = $cat->parent;
			}
		}

		$parent_ids = array_unique( $parent_ids );

		$childless = array();
		foreach ( $all_categories as $cat ) {
			if ( ! in_array( $cat->term_id, $parent_ids, true ) ) {
				$childless[] = $cat->term_id;
			}
		}

		return $childless;
	}

	/**
	 * Build hierarchical tree from flat categories.
	 *
	 * @param array $categories Flat list of categories.
	 * @param int   $parent_id  Parent ID to start from.
	 * @return array Category tree.
	 */
	private function build_tree( array $categories, int $parent_id = 0 ): array {
		$tree          = array();
		$childless_ids = $this->get_categories_without_children();

		foreach ( $categories as $category ) {
			if ( (int) $category->parent === $parent_id ) {
				$children = $this->build_tree( $categories, $category->term_id );

				$tree[] = array(
					'id'           => $category->term_id,
					'name'         => $category->name,
					'slug'         => $category->slug,
					'parent'       => $category->parent,
					'count'        => $category->count,
					'order'        => (int) get_term_meta( $category->term_id, 'order', true ),
					'has_children' => ! empty( $children ),
					'is_childless' => in_array( $category->term_id, $childless_ids, true ),
					'children'     => $children,
				);
			}
		}

		// Sort by order.
		usort( $tree, fn( $a, $b ) => $a['order'] <=> $b['order'] );

		return $tree;
	}

	/**
	 * Format single category for output.
	 *
	 * @param \WP_Term $category Category term object.
	 * @return array Formatted category data.
	 */
	private function format_category( \WP_Term $category ): array {
		$childless_ids = $this->get_categories_without_children();

		return array(
			'id'           => $category->term_id,
			'name'         => $category->name,
			'slug'         => $category->slug,
			'parent'       => $category->parent,
			'count'        => $category->count,
			'order'        => (int) get_term_meta( $category->term_id, 'order', true ),
			'is_childless' => in_array( $category->term_id, $childless_ids, true ),
			'has_children' => false,
			'children'     => array(),
		);
	}

	/**
	 * Move category up in the list.
	 *
	 * @param int $category_id Category ID to move.
	 * @return bool Success status.
	 */
	public function move_category_up( int $category_id ): bool {
		$category = get_term( $category_id, self::TAXONOMY );
		if ( ! $category || is_wp_error( $category ) ) {
			return false;
		}

		$siblings      = $this->get_siblings( $category_id, (int) $category->parent );
		$current_index = $this->find_index( $siblings, $category_id );

		if ( false === $current_index || 0 === $current_index ) {
			return false; // Already at top.
		}

		// Swap with previous.
		$prev_id       = $siblings[ $current_index - 1 ]['id'];
		$current_order = $siblings[ $current_index ]['order'];
		$prev_order    = $siblings[ $current_index - 1 ]['order'];

		update_term_meta( $category_id, 'order', $prev_order );
		update_term_meta( $prev_id, 'order', $current_order );

		return true;
	}

	/**
	 * Move category down in the list.
	 *
	 * @param int $category_id Category ID to move.
	 * @return bool Success status.
	 */
	public function move_category_down( int $category_id ): bool {
		$category = get_term( $category_id, self::TAXONOMY );
		if ( ! $category || is_wp_error( $category ) ) {
			return false;
		}

		$siblings      = $this->get_siblings( $category_id, (int) $category->parent );
		$current_index = $this->find_index( $siblings, $category_id );

		if ( false === $current_index || count( $siblings ) - 1 === $current_index ) {
			return false; // Already at bottom.
		}

		// Swap with next.
		$next_id       = $siblings[ $current_index + 1 ]['id'];
		$current_order = $siblings[ $current_index ]['order'];
		$next_order    = $siblings[ $current_index + 1 ]['order'];

		update_term_meta( $category_id, 'order', $next_order );
		update_term_meta( $next_id, 'order', $current_order );

		return true;
	}

	/**
	 * Move category left (promote to parent level).
	 *
	 * @param int $category_id Category ID to move.
	 * @return bool Success status.
	 */
	public function move_category_left( int $category_id ): bool {
		$category = get_term( $category_id, self::TAXONOMY );
		if ( ! $category || is_wp_error( $category ) ) {
			return false;
		}

		$current_parent = (int) $category->parent;
		if ( 0 === $current_parent ) {
			return false; // Already a root category.
		}

		// Get grandparent.
		$parent_term = get_term( $current_parent, self::TAXONOMY );
		if ( ! $parent_term || is_wp_error( $parent_term ) ) {
			return false;
		}

		$new_parent = (int) $parent_term->parent;

		// Update parent.
		wp_update_term(
			$category_id,
			self::TAXONOMY,
			array(
				'parent' => $new_parent,
			)
		);

		// Set order to end of new sibling list.
		$new_siblings = $this->get_siblings( $category_id, $new_parent );
		$max_order    = $this->get_max_order( $new_siblings );
		update_term_meta( $category_id, 'order', $max_order + 1 );

		return true;
	}

	/**
	 * Move category right (make child of previous sibling).
	 *
	 * @param int $category_id Category ID to move.
	 * @return bool Success status.
	 */
	public function move_category_right( int $category_id ): bool {
		$category = get_term( $category_id, self::TAXONOMY );
		if ( ! $category || is_wp_error( $category ) ) {
			return false;
		}

		$siblings      = $this->get_siblings( $category_id, (int) $category->parent );
		$current_index = $this->find_index( $siblings, $category_id );

		if ( false === $current_index || 0 === $current_index ) {
			return false; // No previous sibling to become parent.
		}

		// Previous sibling becomes new parent.
		$new_parent = $siblings[ $current_index - 1 ]['id'];

		// Update parent.
		wp_update_term(
			$category_id,
			self::TAXONOMY,
			array(
				'parent' => $new_parent,
			)
		);

		// Set order to end of new parent's children.
		$new_siblings = $this->get_children( $new_parent );
		$max_order    = $this->get_max_order( $new_siblings );
		update_term_meta( $category_id, 'order', $max_order + 1 );

		return true;
	}

	/**
	 * Set category order directly.
	 *
	 * @param int $category_id Category ID.
	 * @param int $new_order   New order value.
	 * @return bool Success status.
	 */
	public function set_category_order( int $category_id, int $new_order ): bool {
		$result = update_term_meta( $category_id, 'order', $new_order );
		return false !== $result;
	}

	/**
	 * Set category mega menu column position.
	 *
	 * @param int    $category_id Category ID.
	 * @param string $position    Position value ('left', 'right', or '' for not set).
	 * @return bool Success status.
	 */
	public function set_category_position( int $category_id, string $position ): bool {
		$category = get_term( $category_id, self::TAXONOMY );
		if ( ! $category || is_wp_error( $category ) ) {
			return false;
		}

		// Validate position value.
		if ( ! in_array( $position, array( 'left', 'right', '' ), true ) ) {
			return false;
		}

		// Delete meta if empty, otherwise update.
		if ( '' === $position ) {
			delete_term_meta( $category_id, self::POSITION_META_KEY );
		} else {
			update_term_meta( $category_id, self::POSITION_META_KEY, $position );
		}

		return true;
	}

	/**
	 * Get category mega menu column position.
	 *
	 * @param int $category_id Category ID.
	 * @return string Position value ('left', 'right', or '' for not set).
	 */
	public function get_category_position( int $category_id ): string {
		$position = get_term_meta( $category_id, self::POSITION_META_KEY, true );
		return is_string( $position ) ? $position : '';
	}

	/**
	 * Set category parent.
	 *
	 * @param int $category_id Category ID.
	 * @param int $new_parent  New parent ID.
	 * @return bool Success status.
	 */
	public function set_category_parent( int $category_id, int $new_parent ): bool {
		// Prevent setting category as its own parent.
		if ( $category_id === $new_parent ) {
			return false;
		}

		// Prevent circular reference.
		if ( $this->is_descendant( $new_parent, $category_id ) ) {
			return false;
		}

		$result = wp_update_term(
			$category_id,
			self::TAXONOMY,
			array(
				'parent' => $new_parent,
			)
		);

		if ( is_wp_error( $result ) ) {
			return false;
		}

		// Set order to end of new sibling list.
		$new_siblings = $this->get_siblings( $category_id, $new_parent );
		$max_order    = $this->get_max_order( $new_siblings );
		update_term_meta( $category_id, 'order', $max_order + 1 );

		return true;
	}

	/**
	 * Get siblings of a category.
	 *
	 * @param int $category_id Category ID.
	 * @param int $parent_id   Parent ID.
	 * @return array Sibling categories.
	 */
	private function get_siblings( int $category_id, int $parent_id ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'parent'     => $parent_id,
				'orderby'    => 'meta_value_num',
				'meta_key'   => 'order',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		return array_map(
			function ( $term ) {
				return array(
					'id'    => $term->term_id,
					'order' => (int) get_term_meta( $term->term_id, 'order', true ),
				);
			},
			$terms
		);
	}

	/**
	 * Get children of a category.
	 *
	 * @param int $parent_id Parent category ID.
	 * @return array Child categories.
	 */
	private function get_children( int $parent_id ): array {
		return $this->get_siblings( 0, $parent_id );
	}

	/**
	 * Find index of category in list.
	 *
	 * @param array $items       List of categories.
	 * @param int   $category_id Category ID to find.
	 * @return int|false Index or false if not found.
	 */
	private function find_index( array $items, int $category_id ): int|false {
		foreach ( $items as $index => $item ) {
			if ( $category_id === $item['id'] ) {
				return $index;
			}
		}
		return false;
	}

	/**
	 * Get maximum order value from list.
	 *
	 * @param array $items List of categories with order.
	 * @return int Maximum order value.
	 */
	private function get_max_order( array $items ): int {
		if ( empty( $items ) ) {
			return 0;
		}

		return max( array_column( $items, 'order' ) );
	}

	/**
	 * Check if term is descendant of potential ancestor.
	 *
	 * @param int $term_id            Term ID to check.
	 * @param int $potential_ancestor Potential ancestor ID.
	 * @return bool True if descendant.
	 */
	private function is_descendant( int $term_id, int $potential_ancestor ): bool {
		if ( 0 === $term_id ) {
			return false;
		}

		$term = get_term( $term_id, self::TAXONOMY );
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		if ( (int) $term->parent === $potential_ancestor ) {
			return true;
		}

		if ( 0 === (int) $term->parent ) {
			return false;
		}

		return $this->is_descendant( (int) $term->parent, $potential_ancestor );
	}

	/**
	 * Reorder all categories after drag and drop.
	 *
	 * @param array $order_data Array of [ 'id' => term_id, 'parent' => parent_id, 'order' => position ].
	 * @return bool Success status.
	 */
	public function bulk_reorder( array $order_data ): bool {
		foreach ( $order_data as $item ) {
			if ( ! isset( $item['id'] ) ) {
				continue;
			}

			$category_id = (int) $item['id'];
			$new_parent  = isset( $item['parent'] ) ? (int) $item['parent'] : null;
			$new_order   = isset( $item['order'] ) ? (int) $item['order'] : 0;

			// Update parent if provided.
			if ( null !== $new_parent ) {
				wp_update_term(
					$category_id,
					self::TAXONOMY,
					array(
						'parent' => $new_parent,
					)
				);
			}

			// Update order.
			update_term_meta( $category_id, 'order', $new_order );
		}

		return true;
	}
}
