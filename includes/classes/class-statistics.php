<?php
/**
 * Statistic calculation dispatcher.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Statistics calculation class.
 *
 * @since 0.1.0
 */
class Statistics {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Statistics|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Statistics
	 */
	public static function get_instance(): Statistics {
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
	 * Calculate statistics based on type and filters.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $statistic_type The type of statistic to calculate.
	 * @param array<string, mixed> $filters        Filters to apply.
	 * @return int Calculated statistic value.
	 */
	public function calculate( string $statistic_type, array $filters = array() ): int {
		if ( ! Support::get_instance()->has_supported_post_types() ) {
			return 0;
		}
		
		if ( ! Support::get_instance()->is_statistic_type_supported( $statistic_type ) ) {
			return 0;
		}

		$statistic_type = is_string( $statistic_type ) ? $statistic_type : 'total_events';
		$filters        = is_array( $filters ) ? $filters : array();
		
		if ( empty( $filters['event_query'] ) || ! in_array( $filters['event_query'], array( 'upcoming', 'past' ), true ) ) {
			return 0;
		}
		
		$result = 0;
		
		switch ( $statistic_type ) {
			case 'total_events':
				$result = Query::get_instance()->count_events( $filters );
				break;
				
			case 'events_per_taxonomy':
				$result = Query::get_instance()->count_events( $filters );
				break;
				
			case 'events_multi_taxonomy':
				$result = Query::get_instance()->count_events( $filters );
				break;
				
			case 'total_taxonomy_terms':
				$result = Query::get_instance()->count_terms( $filters );
				break;
				
			case 'taxonomy_terms_by_taxonomy':
				$result = Query::get_instance()->terms_by_taxonomy( $filters );
				break;
				
			case 'total_attendees':
				$result = Query::get_instance()->count_attendees( $filters );
				break;
		}
		
		$result = is_numeric( $result ) ? absint( $result ) : 0;
		
		return apply_filters( 'gatherpress_stats_calculate_' . $statistic_type, $result, $filters );
	}
}
