<?php
/**
 * Main plugin bootstrap class.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Main plugin class using Singleton pattern.
 *
 * @since 0.1.0
 */
class Plugin {

	use Core\Traits\Singleton;

	/**
	 * Constructor.
	 *
	 * Initializes and sets up the plugin's singleton classes.
	 *
	 * @since 0.1.0
	 */
	protected function __construct() {
		$this->instantiate_classes();
	}

	/**
	 * Instantiate singleton classes.
	 *
	 * Each class registers its own hooks in its own constructor. Archive
	 * functionality is only instantiated when enabled via the
	 * 'gatherpress_statistics_enable_archive' filter.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function instantiate_classes(): void {
		Setup::get_instance();
		Cache::get_instance();
		Cache_Invalidation::get_instance();
		Query_Filters::get_instance();
		Rest_Api::get_instance();

		if ( $this->is_archive_enabled() ) {
			Database::get_instance();
			Admin_Page::get_instance();
			Archive::get_instance();
		}
	}

	/**
	 * Check if archive functionality is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if archive is enabled.
	 */
	public function is_archive_enabled(): bool {
		/**
		 * Filters whether the Statistics Archive Dashboard is enabled.
		 *
		 * Disabling this skips registering the archive database table, the
		 * Dashboard → Statistics Archive admin page, and the monthly archive
		 * cron job.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $enabled Whether archive is enabled. Default true.
		 *
		 * @example
		 * ```php
		 * add_filter( 'gatherpress_statistics_enable_archive', '__return_false' );
		 * ```
		 */
		return (bool) apply_filters( 'gatherpress_statistics_enable_archive', true );
	}
}
