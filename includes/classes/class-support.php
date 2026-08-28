<?php
/**
 * Post type support management for gatherpress_statistics.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Post type support management class.
 *
 * @since 0.1.0
 */
class Support {

	use Core\Traits\Singleton;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Get the statistics support configuration for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Post type slug.
	 * @return array<string, bool> Configuration array.
	 */
	public function get_support_config( string $post_type = 'gatherpress_event' ): array {
		if ( ! post_type_supports( $post_type, 'gatherpress_statistics' ) ) {
			return array();
		}
		
		$supports = get_all_post_type_supports( $post_type );
		
		if ( isset( $supports['gatherpress_statistics'] ) && is_array( $supports['gatherpress_statistics'] ) ) {
			return reset( $supports['gatherpress_statistics'] );
		}
		
		return array(
			'total_events'               => true,
			'events_per_taxonomy'        => true,
			'events_multi_taxonomy'      => true,
			'total_taxonomy_terms'       => true,
			'taxonomy_terms_by_taxonomy' => true,
			'total_attendees'            => is_plugin_active( 'gatherpress-attendee-count/plugin.php' ),
		);
	}

	/**
	 * Check if a specific statistic type is supported.
	 *
	 * @since 0.1.0
	 *
	 * @param string $statistic_type The statistic type to check.
	 * @param string $post_type      Optional. Post type to check.
	 * @return bool True if supported.
	 */
	public function is_statistic_type_supported( string $statistic_type, string $post_type = 'gatherpress_event' ): bool {
		$config = $this->get_support_config( $post_type );
		
		if ( empty( $config ) ) {
			return false;
		}
		
		return ! empty( $config[ $statistic_type ] );
	}

	/**
	 * Get all supported statistic types for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Optional. Post type to check.
	 * @return array<int, string> Array of supported statistic type slugs.
	 */
	public function get_supported_statistic_types( string $post_type = 'gatherpress_event' ): array {
		$config = $this->get_support_config( $post_type );
		
		if ( empty( $config ) ) {
			return array();
		}
		
		$enabled_types = array();
		foreach ( $config as $type => $enabled ) {
			if ( $enabled ) {
				$enabled_types[] = $type;
			}
		}
		
		return $enabled_types;
	}

	/**
	 * Get all post types that support gatherpress_statistics.
	 *
	 * @since 0.1.0
	 *
	 * @return string[] Array of post type slugs.
	 */
	public function get_supported_post_types(): array {
		$post_types = get_post_types_by_support( 'gatherpress_statistics' );
		
		if ( empty( $post_types ) ) {
			return array();
		}
		
		return $post_types;
	}

	/**
	 * Check if any post types support gatherpress_statistics.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if at least one post type supports statistics.
	 */
	public function has_supported_post_types(): bool {
		$post_types = $this->get_supported_post_types();
		return ! empty( $post_types );
	}

	/**
	 * Check if a specific post is supported for statistics.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Post ID to check.
	 * @return bool True if supported.
	 */
	public function is_supported_post( int $post_id ): bool {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		
		return post_type_supports( $post->post_type, 'gatherpress_statistics' ) 
			&& $post->post_status === 'publish';
	}

	/**
	 * Get singular label for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Post type slug.
	 * @return string Singular label.
	 */
	public function get_post_type_singular_label( string $post_type ): string {
		$post_type_object = get_post_type_object( $post_type );
		
		if ( $post_type_object && isset( $post_type_object->labels->singular_name ) && is_string( $post_type_object->labels->singular_name ) ) {
			return $post_type_object->labels->singular_name;
		}
		
		return __( 'Item', 'gatherpress-statistics' );
	}

	/**
	 * Get plural label for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Post type slug.
	 * @return string Plural label.
	 */
	public function get_post_type_plural_label( string $post_type ): string {
		$post_type_object = get_post_type_object( $post_type );
		
		if ( $post_type_object && isset( $post_type_object->labels->name ) && is_string( $post_type_object->labels->name ) ) {
			return $post_type_object->labels->name;
		}
		
		return __( 'Items', 'gatherpress-statistics' );
	}
}
