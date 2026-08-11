<?php
/**
 * Plugin initialization: post type support and block registration.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Setup class for plugin initialization.
 *
 * @since 0.1.0
 */
class Setup {

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
		add_action( 'registered_post_type_gatherpress_event', array( $this, 'register_post_type_support' ) );
		add_action( 'init', array( $this, 'block_init' ) );
	}

	/**
	 * Register post type support for gatherpress_statistics.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_post_type_support(): void {
		$default_config = array(
			'total_events'               => true,
			'events_per_taxonomy'        => true,
			'events_multi_taxonomy'      => false,
			'total_taxonomy_terms'       => false,
			'taxonomy_terms_by_taxonomy' => false,
			'total_attendees'            => is_plugin_active( 'gatherpress-attendee-count/plugin.php' ),
		);
		
		$config = apply_filters( 'gatherpress_statistics_support_config', $default_config );
		
		add_post_type_support( 'gatherpress_event', 'gatherpress_statistics', $config );
	}

	/**
	 * Registers the block.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function block_init(): void {
		register_block_type( GATHERPRESS_STATISTICS_CORE_PATH . '/build/editor/blocks/statistics/' );
	}
}
