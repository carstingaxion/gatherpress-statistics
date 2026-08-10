<?php
/**
 * Plugin initialization: post type support and block registration.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Setup class for plugin initialization.
 *
 * @since 0.1.0
 */
class Setup {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Setup|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Setup
	 */
	public static function get_instance(): Setup {
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
			'total_attendees'            => true,
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
		register_block_type( GATHERPRESS_STATISTICS_CORE_PATH . '/build/' );
	}
}
