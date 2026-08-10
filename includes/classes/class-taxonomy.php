<?php
/**
 * Taxonomy discovery and filtering for supported post types.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Taxonomy management class.
 *
 * @since 0.1.0
 */
class Taxonomy {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Taxonomy|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Taxonomy
	 */
	public static function get_instance(): Taxonomy {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Get all taxonomies registered for supported post types.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, \WP_Taxonomy> Array of taxonomy objects.
	 */
	public function get_taxonomies(): array {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return array();
		}
		
		$all_taxonomies = array();
		
		foreach ( $post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}
			
			$taxonomies = \get_object_taxonomies( $post_type, 'objects' );
			
			if ( ! empty( $taxonomies ) && is_array( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					if ( isset( $taxonomy->name ) ) {
						$all_taxonomies[ $taxonomy->name ] = $taxonomy;
					}
				}
			}
		}
		
		return array_values( $all_taxonomies );
	}

	/**
	 * Get filtered taxonomies.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $for_editor Optional. Whether this is for editor selection.
	 * @return array<int, \WP_Taxonomy> Array of taxonomy objects.
	 */
	public function get_filtered_taxonomies( bool $for_editor = false ): array {
		$taxonomies = $this->get_taxonomies();
		
		if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
			return array();
		}
		
		$excluded_taxonomies = apply_filters(
			'gatherpress_statistics_excluded_taxonomies',
			array( '_gatherpress_venue' ),
			$for_editor
		);
		
		if ( ! is_array( $excluded_taxonomies ) ) {
			$excluded_taxonomies = array();
		}
		
		$filtered_taxonomies = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $taxonomy->name ) ) {
				continue;
			}
			
			if ( in_array( $taxonomy->name, $excluded_taxonomies, true ) ) {
				continue;
			}
			
			$filtered_taxonomies[] = $taxonomy;
		}
		
		return $filtered_taxonomies;
	}
}
