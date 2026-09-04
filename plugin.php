<?php
/**
 * Plugin Name:       GatherPress Statistics
 * Description:       Display dynamically calculated statistics about your GatherPress events with beautiful, cached counters.
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Requires Plugins:  gatherpress
 * Author:            WordPress Telex
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gatherpress-statistics
 *
 * @package GatherPressStatistics
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

// Constants.
define( 'GATHERPRESS_STATISTICS_VERSION', current( get_file_data( __FILE__, array( 'Version' ), 'plugin' ) ) );
define( 'GATHERPRESS_STATISTICS_CORE_PATH', __DIR__ );

/**
 * Adds the GatherPressStatistics namespace to the autoloader.
 *
 * This function hooks into the 'gatherpress_autoloader' filter and adds the
 * GatherPressStatistics namespace to the list of namespaces with its core path.
 *
 * @param array<string, string> $namespaces An associative array of namespaces and their paths.
 * @return array<string, string> Modified array of namespaces and their paths.
 */
function gatherpress_statistics_autoloader( array $namespaces ): array {
	$namespaces['GatherPressStatistics'] = GATHERPRESS_STATISTICS_CORE_PATH;

	return $namespaces;
}
add_filter( 'gatherpress_autoloader', 'gatherpress_statistics_autoloader' );

/**
 * Initialize the plugin.
 *
 * Bootstrap function that starts the plugin by initializing the main class.
 *
 * This function hooks into the 'plugins_loaded' action to ensure that
 * the instances are created once all plugins are loaded,
 * only if the GatherPress plugin is active.
 *
 * @since 0.1.0
 * @return void
 */
function gatherpress_statistics_setup(): void {
	if ( defined( 'GATHERPRESS_VERSION' ) ) {
		GatherPressStatistics\Plugin::get_instance();
	}
}
add_action( 'plugins_loaded', 'gatherpress_statistics_setup' );

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_statistics_activate_plugin(): void {
	if ( GatherPressStatistics\Plugin::get_instance()->is_archive_enabled() ) {
		GatherPressStatistics\Database::get_instance()->create_archive_table();
		
		if ( ! wp_next_scheduled( 'gatherpress_statistics_monthly_archive' ) ) {
			wp_schedule_event(
				strtotime( 'first day of next month midnight' ),
				'monthly',
				'gatherpress_statistics_monthly_archive'
			);
		}
	}
	
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
 * @since 0.1.0
 *
 * @global \wpdb $wpdb WordPress database object.
 * @return void
 */
function gatherpress_statistics_deactivate_plugin(): void {
	/**
	 * Help phpstan understand $wpdb is global.
	 * 
	 * @var \wpdb  $wpdb WordPress database abstraction object.
	 */
	global $wpdb;
	
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		"DELETE FROM {$wpdb->options} 
		WHERE option_name LIKE '_transient_gatherpress_stats_%' 
		OR option_name LIKE '_transient_timeout_gatherpress_stats_%'"
	);
	
	$scheduled = wp_next_scheduled( 'gatherpress_statistics_regenerate_cache' );
	if ( $scheduled ) {
		wp_unschedule_event( $scheduled, 'gatherpress_statistics_regenerate_cache' );
	}
	
	if ( GatherPressStatistics\Plugin::get_instance()->is_archive_enabled() ) {
		$scheduled_monthly = wp_next_scheduled( 'gatherpress_statistics_monthly_archive' );
		if ( $scheduled_monthly ) {
			wp_unschedule_event( $scheduled_monthly, 'gatherpress_statistics_monthly_archive' );
		}
	}
}
register_deactivation_hook( __FILE__, 'gatherpress_statistics_deactivate_plugin' );

/**
 * Get statistic with caching - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @param string               $statistic_type Statistic type to retrieve.
 * @param array<string, mixed> $filters        Filters to apply.
 * @return int Statistic value.
 */
function gatherpress_statistics_get_cached( string $statistic_type, array $filters = array() ): int {
	return GatherPressStatistics\Cache::get_instance()->get_cached( $statistic_type, $filters );
}

/**
 * Resolve a context term id - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @param int    $post_id  Context post id.
 * @param string $taxonomy Taxonomy slug to look up on the post.
 * @return int Resolved term id, or 0 when none found.
 */
function gatherpress_statistics_resolve_context_term( int $post_id, string $taxonomy ): int {
	return GatherPressStatistics\Query::get_instance()->resolve_context_term( $post_id, $taxonomy );
}

/**
 * Resolve the effective context post id - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @param int $post_id Context post id as originally resolved from block context
 *                      or the queried object.
 * @return int The (possibly filtered) context post id.
 */
function gatherpress_statistics_resolve_context_post( int $post_id ): int {
	return GatherPressStatistics\Query::get_instance()->resolve_context_post( $post_id );
}

/**
 * Clear cache - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_statistics_clear_cache(): void {
	GatherPressStatistics\Cache::get_instance()->clear_cache();
}

/**
 * Pre-generate cache - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @return void
 */
function gatherpress_statistics_pregenerate_cache(): void {
	GatherPressStatistics\Cache::get_instance()->pregenerate_cache();
}
