<?php
/**
 * WP_Query SQL filters for GatherPress event date lookups.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Query filters class for modifying SQL queries.
 *
 * @since 0.1.0
 */
class Query_Filters {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Query_Filters|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Query_Filters
	 */
	public static function get_instance(): Query_Filters {
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
	 * Filter SQL WHERE clause to use GatherPress event dates instead of post dates.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @param string    $where The WHERE clause of the query.
	 * @param \WP_Query $query The WP_Query instance.
	 * @return string Modified WHERE clause.
	 */
	public function filter_gatherpress_event_dates( string $where, \WP_Query $query ): string {
		global $wpdb;

		if ( empty( $query->query_vars['date_query'] ) ) {
			return $where;
		}

		$post_type = $query->get( 'post_type' );
		if ( 'gatherpress_event' !== $post_type && ! in_array( 'gatherpress_event', (array) $post_type, true ) ) {
			return $where;
		}

		$date_query = $query->query_vars['date_query'];
		if ( ! is_array( $date_query ) || empty( $date_query ) ) {
			return $where;
		}

		$date_filter = is_array( $date_query[0] ) ? $date_query[0] : array();

		$date_conditions = array();

		if ( ! empty( $date_filter['year'] ) ) {
			$year              = absint( $date_filter['year'] );
			$date_conditions[] = $wpdb->prepare( 'YEAR(ge.datetime_start_gmt) = %d', $year );
		}

		if ( ! empty( $date_filter['month'] ) ) {
			$month             = absint( $date_filter['month'] );
			$date_conditions[] = $wpdb->prepare( 'MONTH(ge.datetime_start_gmt) = %d', $month );
		}

		if ( empty( $date_conditions ) ) {
			return $where;
		}

		$where = preg_replace(
			'/AND\s*\(\s*\(\s*YEAR\(\s*[^)]+\s*\)\s*=\s*\d+(?:\s+AND\s+MONTH\(\s*[^)]+\s*\)\s*=\s*\d+)?\s*\)\s*\)/',
			'',
			$where
		);

		$events_table = $wpdb->prefix . 'gatherpress_events';
		$date_where   = implode( ' AND ', $date_conditions );

		$where .= " AND {$wpdb->posts}.ID IN (
			SELECT ge.post_id 
			FROM {$events_table} ge 
			WHERE {$date_where}
		)";

		return $where;
	}
}
