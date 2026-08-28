<?php
/**
 * REST API endpoints for the block editor.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * REST API endpoints class.
 *
 * @since 0.1.0
 */
class Rest_Api {

	use Core\Traits\Singleton;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up hooks for various purposes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		\register_rest_route(
			'gatherpress-statistics/v1',
			'/taxonomies',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_taxonomies_endpoint' ),
				'permission_callback' => function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		
		\register_rest_route(
			'gatherpress-statistics/v1',
			'/supported-types',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_supported_types_endpoint' ),
				'permission_callback' => function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * REST API endpoint to get filtered taxonomies.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response List of taxonomies.
	 */
	public function get_taxonomies_endpoint(): \WP_REST_Response {
		$taxonomies = Taxonomy::get_instance()->get_filtered_taxonomies( true );
		
		if ( empty( $taxonomies ) ) {
			return new \WP_REST_Response( array(), 200 );
		}
		
		$formatted_taxonomies = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( isset( $taxonomy->labels->name ) ) {
				$formatted_taxonomies[] = array(
					'slug' => $taxonomy->name,
					'name' => $taxonomy->labels->name,
				);
			}
		}
		
		return new \WP_REST_Response( $formatted_taxonomies, 200 );
	}

	/**
	 * REST API endpoint to get supported statistic types.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response List of supported types.
	 */
	public function get_supported_types_endpoint(): \WP_REST_Response {
		$supported_types = Support::get_instance()->get_supported_statistic_types();
		
		return new \WP_REST_Response( $supported_types, 200 );
	}
}
