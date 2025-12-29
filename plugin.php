<?php
/**
 * Plugin Name:       GatherPress Statistics
 * Plugin URI:   
 * Description:       Display dynamically calculated statistics about your GatherPress events with beautiful, cached counters.
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Requires Plugins:  gatherpress
 * Author:            carstenbach & WordPress Telex
 * Author URI:        https://carsten-bach.de
 * Text Domain:       gatherpress-statistics
 * Domain Path:       /languages
 * License:           GNU General Public License v2.0 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 *
 * @package GatherPress_Statistics
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

// Constants.
define( 'GATHERPRESS_STATISTICS_VERSION', current( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
define( 'GATHERPRESS_STATISTICS_CORE_PATH', __DIR__ );

/**
 * Adds the GatherPress_Statistics namespace to the autoloader.
 *
 * This function hooks into the 'gatherpress_autoloader' filter and adds the
 * GatherPress_Statistics namespace to the list of namespaces with its core path.
 *
 * @param array<string, string> $namespace An associative array of namespaces and their paths.
 * @return array<string, string> Modified array of namespaces and their paths.
 */
function gatherpress_statistics_autoloader( array $namespace ): array {
	$namespace['GatherPress_Statistics'] = GATHERPRESS_STATISTICS_CORE_PATH;

	return $namespace;
}
add_filter( 'gatherpress_autoloader', 'gatherpress_statistics_autoloader' );

/**
 * Initializes the GatherPress Statistics setup.
 *
 * This function hooks into the 'plugins_loaded' action to ensure that
 * the GatherPress_Statistics\Setup instance is created once all plugins are loaded,
 * only if the GatherPress plugin is active.
 *
 * @return void
 */
function gatherpress_statistics_setup(): void {
	if ( defined( 'GATHERPRESS_VERSION' ) ) {
		GatherPress_Statistics\Setup::get_instance();
	}

}
add_action( 'plugins_loaded', 'gatherpress_statistics_setup' );

/**
 * Plugin activation hook.
 *
 * Runs when the plugin is activated. Schedules immediate cache regeneration
 * via a cron job to ensure statistics are available right away.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_statistics_activate_plugin(): void {
	if ( ! wp_next_scheduled( 'gatherpress_statistics_regenerate_cache' ) ) {
		wp_schedule_single_event(
			time() + 5,
			'gatherpress_statistics_regenerate_cache'
		);
	}
}
register_activation_hook( __FILE__, 'gatherpress_statistics_activate_plugin' );

/**
 * Plugin deactivation hook.
 *
 * Runs when the plugin is deactivated. Cleans up all statistics transients
 * and any scheduled cron jobs to avoid leaving orphaned data.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_statistics_deactivate_plugin(): void {
	/**
	 * @var \wpdb  $wpdb WordPress database abstraction object.
	 */
	global $wpdb;
	
	// Delete all statistics transients from options table.
	$wpdb->query(
		"DELETE FROM {$wpdb->options} 
		WHERE option_name LIKE '_transient_gatherpress_stats_%' 
		OR option_name LIKE '_transient_timeout_gatherpress_stats_%'"
	);
	
	// Clear any scheduled regeneration jobs.
	$scheduled = wp_next_scheduled( 'gatherpress_statistics_regenerate_cache' );
	if ( $scheduled ) {
		wp_unschedule_event( $scheduled, 'gatherpress_statistics_regenerate_cache' );
	}
}
register_deactivation_hook( __FILE__, 'gatherpress_statistics_deactivate_plugin' );
