<?php
/**
 * Statistic calculation dispatcher.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Statistics calculation class.
 *
 * @since 0.1.0
 */
class Statistics {

	use Core\Traits\Singleton;

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

		$statistic_type = ! empty( $statistic_type ) ? $statistic_type : 'total_events';
		$filters        = ! empty( $filters ) ? $filters : array();
		
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

		/* @phpstan-ignore-next-line */
		$result = is_numeric( $result ) ? absint( $result ) : 0;

		/**
		 * Filters a calculated statistic value before it's cached.
		 *
		 * The hook name is dynamic - one filter per statistic type:
		 *
		 * - `gatherpress_stats_calculate_total_events`
		 * - `gatherpress_stats_calculate_events_per_taxonomy`
		 * - `gatherpress_stats_calculate_events_multi_taxonomy`
		 * - `gatherpress_stats_calculate_total_taxonomy_terms`
		 * - `gatherpress_stats_calculate_taxonomy_terms_by_taxonomy`
		 * - `gatherpress_stats_calculate_total_attendees`
		 *
		 * @since 0.1.0
		 *
		 * @param int                  $result  The calculated statistic value.
		 * @param array<string, mixed> $filters The filters applied to this statistic.
		 *
		 * @example
		 * ```php
		 * // Round counts over 50 to the nearest 10.
		 * add_filter( 'gatherpress_stats_calculate_total_events', function ( int $count, array $filters ): int {
		 *     return $count > 50 ? (int) round( $count / 10 ) * 10 : $count;
		 * }, 10, 2 );
		 * ```
		 *
		 * @example
		 * ```php
		 * // Apply a 1.5x multiplier to all event counts.
		 * add_filter( 'gatherpress_stats_calculate_total_events', function ( int $count, array $filters ): int {
		 *     return (int) round( $count * 1.5 );
		 * }, 10, 2 );
		 * ```
		 */
		$return = apply_filters( 'gatherpress_stats_calculate_' . $statistic_type, $result, $filters );

		// A misbehaving callback could still return something that doesn't
		// match the documented type, so keep this defensive at runtime.
		// @phpstan-ignore-next-line
		return is_numeric( $return ) ? absint( $return ) : $result;
	}
}
