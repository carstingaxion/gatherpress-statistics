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
		
		/**
		 * Filters the default gatherpress_statistics support configuration.
		 *
		 * Runs once, when support for `gatherpress_statistics` is registered on
		 * the `gatherpress_event` post type. Use this to change which statistic
		 * types are enabled by default without re-registering post type support
		 * yourself.
		 *
		 * @since 0.1.0
		 *
		 * @param array<string, bool> $default_config Default statistic types and whether each is enabled.
		 *
		 * @example
		 * ```php
		 * add_filter( 'gatherpress_statistics_support_config', function ( array $config ): array {
		 *     // Disable the more expensive cross-taxonomy statistics.
		 *     $config['events_multi_taxonomy']      = false;
		 *     $config['taxonomy_terms_by_taxonomy'] = false;
		 *     return $config;
		 * } );
		 * ```
		 */
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
