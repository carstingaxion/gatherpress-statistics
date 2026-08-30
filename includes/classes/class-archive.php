<?php
/**
 * Monthly archival of statistics into the archive table.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Archive management class.
 *
 * @since 0.1.0
 */
class Archive {

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
		add_action( 'gatherpress_statistics_monthly_archive', array( $this, 'archive_monthly_statistics' ) );
	}

	/**
	 * Archive monthly statistics.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function archive_monthly_statistics(): void {
		$current_year  = (int) date( 'Y' );
		$current_month = (int) date( 'n' );
		
		$this->archive_statistics_for_month( $current_year, $current_month );
	}

	/**
	 * Generate archive statistics for a specific month.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @param int $year  Year to generate statistics for.
	 * @param int $month Month to generate statistics for.
	 * @return bool True on success, false on failure.
	 */
	public function archive_statistics_for_month( int $year, int $month ): bool {
		/**
		 * Help phpstan understand $wpdb is global.
		 * 
		 * @var \wpdb  $wpdb WordPress database abstraction object.
		 */
		global $wpdb;
		
		$database     = Database::get_instance();
		$table_name   = sprintf( $database::TABLE_FORMAT, $wpdb->prefix );
		$current_time = current_time( 'mysql' );
		
		$configs = Cache::get_instance()->get_common_configs();
		
		if ( empty( $configs ) ) {
			return false;
		}

		$success_count = 0;
		$post_types    = Support::get_instance()->get_supported_post_types();
		$post_type     = ! empty( $post_types ) ? $post_types[0] : 'gatherpress_event';
		
		foreach ( $configs as $config ) {
			if ( ! isset( $config['type'] ) || ! isset( $config['filters'] ) ) {
				continue;
			}
			
			$config['filters']['event_query'] = 'past';
			
			$filters_with_date = array_merge(
				$config['filters'],
				array(
					'year'  => $year,
					'month' => $month,
				) 
			);
			
			$value        = Statistics::get_instance()->calculate( $config['type'], $filters_with_date );
			$filters_hash = md5( wp_json_encode( $config['filters'] ) );
			
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_name} 
					 WHERE post_type = %s
					 AND statistic_type = %s 
					 AND statistic_year = %d 
					 AND statistic_month = %d 
					 AND filters_hash = %s",
					$post_type,
					$config['type'],
					$year,
					$month,
					$filters_hash
				)
			);

			if ( $exists ) {
				$result = $wpdb->update(
					$table_name,
					array(
						'statistic_value' => $value,
						'archived_at'     => $current_time,
					),
					array( 'id' => $exists ),
					array( '%d', '%s' ),
					array( '%d' )
				);
			} else {
				$result = $wpdb->insert(
					$table_name,
					array(
						'post_type'       => $post_type,
						'statistic_type'  => $config['type'],
						'statistic_year'  => $year,
						'statistic_month' => $month,
						'filters_hash'    => $filters_hash,
						'filters_data'    => wp_json_encode( $config['filters'] ),
						'statistic_value' => $value,
						'archived_at'     => $current_time,
					),
					array( '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s' )
				);
			}

			if ( false !== $result ) {
				++$success_count;
			}
		}

		return $success_count > 0;
	}
}
