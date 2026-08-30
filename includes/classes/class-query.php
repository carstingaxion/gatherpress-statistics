<?php
/**
 * WP_Query and term-lookup based statistic counting.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;
use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Query class for database operations.
 *
 * @since 0.1.0
 */
class Query {

	use Core\Traits\Singleton;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Build date query arguments from filters.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return array<string, mixed> Date query arguments.
	 */
	private function build_date_query( array $filters ): array {
		$date_query = array();
		
		if ( ! empty( $filters['year'] ) && is_string( $filters['year'] ) ) {
			$year = absint( $filters['year'] );
			if ( $year > 0 ) {
				$date_query['year'] = $year;
			}
		}
		
		if ( ! empty( $filters['month'] ) && is_string( $filters['month'] ) ) {
			$month = absint( $filters['month'] );
			if ( $month >= 1 && $month <= 12 ) {
				$date_query['month'] = $month;
			}
		}
		
		return $date_query;
	}

	/**
	 * Resolve a taxonomy term id from a context post instead of a manual selection.
	 *
	 * Used when a block is configured to derive its filter term from the
	 * post it is placed on (e.g. a Single Event template, or the current
	 * item inside a Query Loop) rather than from a hard-coded term id.
	 * When a post has more than one term in the taxonomy, the first one
	 * returned by `wp_get_post_terms()` is used.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $post_id  Context post id (0 when there is no context).
	 * @param string $taxonomy Taxonomy slug to look up on the post.
	 * @return int Term id, or 0 when the post has no term in that taxonomy.
	 */
	public function resolve_context_term( int $post_id, string $taxonomy ): int {
		if ( $post_id <= 0 || empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}

		$terms = wp_get_post_terms( $post_id, sanitize_key( $taxonomy ), array( 'fields' => 'ids' ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return 0;
		}

		return absint( reset( $terms ) );
	}

	/**
	 * Count events with filters.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return int Number of events.
	 */
	public function count_events( array $filters = array() ): int {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		
		if ( isset( $filters['event_query'] ) && is_string( $filters['event_query'] ) ) {
			$event_query = sanitize_key( $filters['event_query'] );
			if ( in_array( $event_query, array( 'upcoming', 'past' ), true ) ) {
				$args['gatherpress_event_query'] = $event_query;
			}
		}
		
		$date_query = $this->build_date_query( $filters );
		if ( ! empty( $date_query ) ) {
			$args['date_query'] = array( $date_query );
		}
		
		if ( ! empty( $filters['taxonomy'] ) && is_string( $filters['taxonomy'] ) && ! empty( $filters['term_id'] ) && is_numeric( $filters['term_id'] ) ) {
			if ( taxonomy_exists( $filters['taxonomy'] ) ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => sanitize_key( $filters['taxonomy'] ),
						'field'    => 'term_id',
						'terms'    => absint( $filters['term_id'] ),
					),
				);
			}
		} elseif ( ! empty( $filters['taxonomy_terms'] ) && is_array( $filters['taxonomy_terms'] ) ) {
			$tax_query = array( 'relation' => 'AND' );
			
			foreach ( $filters['taxonomy_terms'] as $taxonomy => $term_ids ) {
				if ( ! empty( $term_ids ) && is_array( $term_ids ) && taxonomy_exists( $taxonomy ) ) {
					$tax_query[] = array(
						'taxonomy' => sanitize_key( $taxonomy ),
						'field'    => 'term_id',
						'terms'    => $term_ids,
					);
				}
			}
			
			if ( count( $tax_query ) > 1 ) {
				$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}
		}
		
		$query = new WP_Query( $args );
		
		return absint( $query->found_posts );
	}

	/**
	 * Count total terms in a taxonomy.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Filters including taxonomy.
	 * @return int Number of terms.
	 */
	public function count_terms( array $filters = array() ): int {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$taxonomy = isset( $filters['taxonomy'] ) && is_string( $filters['taxonomy'] ) ? $filters['taxonomy'] : '';
		
		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}
		
		$args = array(
			'taxonomy'   => sanitize_key( $taxonomy ),
			'hide_empty' => true,
			'object_ids' => array(), // Will be filled with post IDs later.
		);
		
		$query_args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		
		$date_query = $this->build_date_query( $filters );
		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = array( $date_query );
		}
		
		$post_query = new WP_Query( $query_args );
		
		if ( ! empty( $post_query->posts ) ) {

			/**
			 * This is for sure an array of int, because of 'fields' => 'ids'.
			 *
			 * @var int[] $postids
			 */
			$postids            = $post_query->posts;
			$args['object_ids'] = $postids;
		}
		
		$terms = get_terms( $args );
		
		if ( is_wp_error( $terms ) ) {
			return 0;
		}
		
		return absint( count( $terms ) );
	}

	/**
	 * Count terms of one taxonomy that have events in another taxonomy.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Filters for cross-taxonomy counting.
	 * @return int Number of unique terms.
	 */
	public function terms_by_taxonomy( array $filters = array() ): int {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$count_taxonomy  = isset( $filters['count_taxonomy'] ) && is_string( $filters['count_taxonomy'] ) ? $filters['count_taxonomy'] : '';
		$filter_taxonomy = isset( $filters['filter_taxonomy'] ) && is_string( $filters['filter_taxonomy'] ) ? $filters['filter_taxonomy'] : '';
		$term_id         = isset( $filters['term_id'] ) && is_numeric( $filters['term_id'] ) ? absint( $filters['term_id'] ) : 0;
		
		if ( empty( $count_taxonomy ) || empty( $filter_taxonomy ) || $term_id === 0 ) {
			return 0;
		}
		
		if ( ! taxonomy_exists( $count_taxonomy ) || ! taxonomy_exists( $filter_taxonomy ) ) {
			return 0;
		}
		
		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => sanitize_key( $filter_taxonomy ),
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		);
		
		$date_query = $this->build_date_query( $filters );
		if ( ! empty( $date_query ) ) {
			$args['date_query'] = array( $date_query );
		}
		
		$query = new WP_Query( $args );
		$terms = array();
		
		if ( ! empty( $query->posts ) ) {
			/**
			 * This is for sure an int, because of 'fields' => 'ids'.
			 *
			 * @var int $post_id
			 */
			foreach ( $query->posts as $post_id ) {
				$post_terms = wp_get_post_terms( $post_id, sanitize_key( $count_taxonomy ), array( 'fields' => 'ids' ) );
				
				if ( ! is_wp_error( $post_terms ) && ! empty( $post_terms ) ) {
					$terms = array_merge( $terms, $post_terms );
				}
			}
		}
		
		return absint( count( array_unique( $terms ) ) );
	}

	/**
	 * Count total attendees with filters.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return int Total number of attendees.
	 */
	public function count_attendees( array $filters = array() ): int {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		
		if ( isset( $filters['event_query'] ) && is_string( $filters['event_query'] ) ) {
			$event_query = sanitize_key( $filters['event_query'] );
			if ( in_array( $event_query, array( 'upcoming', 'past' ), true ) ) {
				$args['gatherpress_event_query'] = $event_query;
			}
		}
		
		$date_query = $this->build_date_query( $filters );
		if ( ! empty( $date_query ) ) {
			$args['date_query'] = array( $date_query );
		}
		
		if ( ! empty( $filters['taxonomy'] ) && is_string( $filters['taxonomy'] ) && ! empty( $filters['term_id'] ) && is_numeric( $filters['term_id'] ) ) {
			if ( taxonomy_exists( $filters['taxonomy'] ) ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => sanitize_key( $filters['taxonomy'] ),
						'field'    => 'term_id',
						'terms'    => absint( $filters['term_id'] ),
					),
				);
			}
		} elseif ( ! empty( $filters['taxonomy_terms'] ) && is_array( $filters['taxonomy_terms'] ) ) {
			$tax_query = array( 'relation' => 'AND' );
			
			foreach ( $filters['taxonomy_terms'] as $taxonomy => $term_ids ) {
				if ( ! empty( $term_ids ) && is_array( $term_ids ) && taxonomy_exists( $taxonomy ) ) {
					$tax_query[] = array(
						'taxonomy' => sanitize_key( $taxonomy ),
						'field'    => 'term_id',
						'terms'    => $term_ids,
					);
				}
			}
			
			if ( count( $tax_query ) > 1 ) {
				$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}
		}
		
		$query           = new WP_Query( $args );
		$total_attendees = 0;
		
		if ( ! empty( $query->posts ) ) {
			/**
			 * This is for sure an int, because of 'fields' => 'ids'.
			 *
			 * @var int $post_id
			 */
			foreach ( $query->posts as $post_id ) {
				$attendee_count = get_post_meta( $post_id, 'gatherpress_attendee_count', true );
				
				if ( is_numeric( $attendee_count ) ) {
					$total_attendees += absint( $attendee_count );
				}
			}
		}
		
		return absint( $total_attendees );
	}
}
