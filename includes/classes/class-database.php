<?php
/**
 * Database operations for archived statistics.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Database operations class.
 *
 * @since 0.1.0
 */
class Database {

	use Core\Traits\Singleton;

	/**
	 * Format for the database table name used by GatherPress statistics.
	 *
	 * @since 0.2.0
	 * @var string $TABLE_FORMAT
	 */
	const TABLE_FORMAT = '%sgatherpress_statistics_archive';

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
		add_action( 'init', array( $this, 'create_archive_table' ) );
	}

	/**
	 * Create database table for archival statistics.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return void
	 */
	public function create_archive_table(): void {
		/**
		 * Help phpstan understand $wpdb is global.
		 * 
		 * @var \wpdb  $wpdb WordPress database abstraction object.
		 */
		global $wpdb;

		$table_name      = sprintf( self::TABLE_FORMAT, $wpdb->prefix );
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = 
				"CREATE TABLE {$table_name} (
				id mediumint(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_type varchar(20) NOT NULL DEFAULT 'gatherpress_event',
				statistic_type varchar(100) NOT NULL,
				statistic_year smallint UNSIGNED NOT NULL,
				statistic_month tinyint UNSIGNED NOT NULL,
				filters_hash char(32) NOT NULL,
				filters_data longtext NOT NULL,
				statistic_value bigint(20) NOT NULL DEFAULT 0,
				archived_at datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY post_type (post_type),
				KEY statistic_lookup (statistic_type, statistic_year, statistic_month),
				KEY statistic_year_month (statistic_year, statistic_month),
				KEY filters_lookup (filters_hash)
				) {$charset_collate};";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
	
	/**
	 * Get archive data for a specific statistic.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @param string               $statistic_type Statistic type.
	 * @param array<string, mixed> $filters        Filters including year and month.
	 * @return int|null Statistic value or null if not found.
	 */
	public function get_archive_statistic( string $statistic_type, array $filters ): ?int {
		/**
		 * Help phpstan understand $wpdb is global.
		 * 
		 * @var \wpdb  $wpdb WordPress database abstraction object.
		 */
		global $wpdb;

		if ( empty( $filters['year'] ) || empty( $filters['month'] ) ) {
			return null;
		}
		
		$table_name = sprintf( self::TABLE_FORMAT, $wpdb->prefix );
		
		// Remove year and month from filters for hash calculation
		$filter_copy = $filters;
		unset( $filter_copy['year'], $filter_copy['month'] );
		$filters_hash = md5( (string) wp_json_encode( $filter_copy ) );
		
		$post_types = Support::get_instance()->get_supported_post_types();
		$post_type  = ! empty( $post_types ) ? $post_types[0] : 'gatherpress_event';
		
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT statistic_value FROM %i
				 WHERE post_type = %s
				 AND statistic_type = %s
				 AND statistic_year = %d
				 AND statistic_month = %d
				 AND filters_hash = %s
				 ORDER BY archived_at DESC
				 LIMIT 1",
				$table_name,
				$post_type,
				$statistic_type,
				$filters['year'],
				$filters['month'],
				$filters_hash
			)
		);
		
		return $result !== null ? (int) $result : null;
	}
}
